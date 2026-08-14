<?php
/**
 * Settings registration, validation, migration, and admin UI.
 *
 * @package NetmilkStudio\Sesamo
 */

namespace NetmilkStudio\Sesamo;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION_NAME        = 'netmilk_sesamo_settings';
	public const LEGACY_OPTION_NAME = 'konami_code_activator_settings';
	public const SCHEMA_VERSION     = 2;
	private const PAGE_SLUG         = 'sesamo';
	private const GROUP             = 'netmilk_sesamo';

	/** Register admin hooks. */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_action( 'admin_init', array( self::class, 'maybe_migrate' ), 5 );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/** Create or migrate the one plugin option on activation. */
	public static function activate(): void {
		$current = get_option( self::OPTION_NAME, false );
		if ( false !== $current ) {
			update_option( self::OPTION_NAME, self::normalize( $current ), false );
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION_NAME, false );
		add_option( self::OPTION_NAME, false === $legacy ? self::defaults() : self::normalize( $legacy ), '', 'no' );
	}

	/** Persist an old schema after an administrator loads WordPress. */
	public static function maybe_migrate(): void {
		$current = get_option( self::OPTION_NAME, false );
		if ( false === $current ) {
			return;
		}

		$version = is_array( $current ) && isset( $current['schema_version'] ) && is_scalar( $current['schema_version'] )
			? (int) $current['schema_version']
			: 0;
		if ( self::SCHEMA_VERSION !== $version ) {
			update_option( self::OPTION_NAME, self::normalize( $current ), false );
		}
	}

	/** Return safe schema-versioned defaults. */
	public static function defaults(): array {
		$destination = self::safe_home_destination( '/iddqd/' );

		return array(
			'schema_version'      => self::SCHEMA_VERSION,
			'enabled_presets'     => Presets::default_ids(),
			'preset_destinations' => array(
				'konami' => $destination,
				'iddqd'  => $destination,
			),
			'custom_combinations' => array(),
			'max_pause'           => 1500,
			// Kept as a rollback bridge for the 0.1 reader.
			'destination_url'     => $destination,
		);
	}

	/** Read settings through the same trust boundary used for form input. */
	public static function get(): array {
		return self::normalize( get_option( self::OPTION_NAME, array() ) );
	}

	/** Register the Settings API option. */
	public static function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/** Add the settings page. */
	public static function add_page(): void {
		add_options_page( __( 'Sesamo', 'sesamo' ), __( 'Sesamo', 'sesamo' ), 'manage_options', self::PAGE_SLUG, array( self::class, 'render_page' ) );
	}

	/**
	 * Normalize arbitrary option data without emitting UI side effects.
	 *
	 * @param mixed $candidate Candidate option data.
	 */
	public static function normalize( $candidate ): array {
		$defaults = self::defaults();
		$input    = is_array( $candidate ) ? $candidate : array();
		$presets  = Presets::all();
		$schema_version = isset( $input['schema_version'] ) && is_scalar( $input['schema_version'] ) ? (int) $input['schema_version'] : 0;
		$is_legacy_schema = $schema_version < self::SCHEMA_VERSION;

		$requested = isset( $input['enabled_presets'] ) && is_array( $input['enabled_presets'] ) ? $input['enabled_presets'] : array();
		$requested = array_filter( $requested, 'is_string' );
		$requested = array_map( 'sanitize_key', $requested );
		$enabled   = array_values( array_intersect( array_keys( $presets ), array_unique( $requested ) ) );

		$legacy_destination = isset( $input['destination_url'] ) && is_string( $input['destination_url'] )
			? self::normalize_destination( trim( $input['destination_url'] ) )
			: '';
		if ( '' === $legacy_destination ) {
			$legacy_destination = $defaults['destination_url'];
		}

		$raw_destinations = isset( $input['preset_destinations'] ) && is_array( $input['preset_destinations'] ) ? $input['preset_destinations'] : array();
		$preset_destinations = array();
		foreach ( array_keys( $presets ) as $id ) {
			$has_raw_destination = array_key_exists( $id, $raw_destinations );
			$destination = isset( $raw_destinations[ $id ] ) && is_string( $raw_destinations[ $id ] )
				? self::normalize_destination( trim( $raw_destinations[ $id ] ) )
				: '';
			if ( '' === $destination && $is_legacy_schema && ! $has_raw_destination && in_array( $id, $enabled, true ) ) {
				$destination = $legacy_destination;
			}
			if ( '' !== $destination ) {
				$preset_destinations[ $id ] = $destination;
			}
		}

		$active_sequences = array();
		foreach ( $enabled as $id ) {
			if ( isset( $preset_destinations[ $id ] ) ) {
				$active_sequences[] = $presets[ $id ]['sequence'];
			}
		}

		$raw_customs = isset( $input['custom_combinations'] ) && is_array( $input['custom_combinations'] )
			? array_slice( array_values( $input['custom_combinations'] ), 0, Combinations::MAX_CUSTOM )
			: array();
		$customs     = array();
		$used_ids    = array();
		foreach ( $raw_customs as $index => $raw_custom ) {
			if ( ! is_array( $raw_custom ) ) {
				continue;
			}

			$name = isset( $raw_custom['name'] ) && is_string( $raw_custom['name'] )
				? self::limit_text( sanitize_text_field( $raw_custom['name'] ), 64 )
				: '';
			$sequence    = Combinations::normalize_sequence( $raw_custom['sequence'] ?? array() );
			$destination = isset( $raw_custom['destination_url'] ) && is_string( $raw_custom['destination_url'] )
				? self::normalize_destination( trim( $raw_custom['destination_url'] ) )
				: '';
			$requested_enabled = isset( $raw_custom['enabled'] ) && in_array( $raw_custom['enabled'], array( true, 1, '1', 'on' ), true );

			if ( '' === $name && array() === $sequence && '' === $destination && ! $requested_enabled ) {
				continue;
			}

			$id = isset( $raw_custom['id'] ) && is_string( $raw_custom['id'] ) ? sanitize_key( $raw_custom['id'] ) : '';
			if ( 1 !== preg_match( '/^custom_[a-z0-9]{8,40}$/', $id ) || isset( $used_ids[ $id ] ) ) {
				$id = self::custom_id( $name, $sequence, $destination, $index );
			}
			while ( isset( $used_ids[ $id ] ) ) {
				$id = self::custom_id( $name, $sequence, $destination, $index + count( $used_ids ) + 1 );
			}
			$used_ids[ $id ] = true;

			$complete       = '' !== $name && array() !== $sequence && '' !== $destination;
			$enabled_custom = $requested_enabled && $complete;
			if ( $enabled_custom ) {
				if ( Combinations::conflicts_with_any( $sequence, $active_sequences ) ) {
					$enabled_custom = false;
				} else {
					$active_sequences[] = $sequence;
				}
			}

			$customs[] = array(
				'id'              => $id,
				'enabled'         => $enabled_custom,
				'name'            => $name,
				'sequence'        => $sequence,
				'destination_url' => $destination,
			);
		}

		$pause_candidate = isset( $input['max_pause'] ) && is_scalar( $input['max_pause'] ) ? $input['max_pause'] : $defaults['max_pause'];
		$max_pause       = max( 250, min( 5000, absint( $pause_candidate ) ) );

		$rollback_destination = self::first_destination( $enabled, $preset_destinations, $customs );
		if ( '' === $rollback_destination ) {
			$rollback_destination = $defaults['destination_url'];
		}

		return array(
			'schema_version'      => self::SCHEMA_VERSION,
			'enabled_presets'     => $enabled,
			'preset_destinations' => $preset_destinations,
			'custom_combinations' => $customs,
			'max_pause'           => $max_pause,
			'destination_url'     => $rollback_destination,
		);
	}

	/** Sanitize a settings form payload and record actionable recovery notices. */
	public static function sanitize( $input ): array {
		$raw        = is_array( $input ) ? self::unslash_form_input( $input ) : array();
		$normalized = self::normalize( $raw );

		self::validate_preset_destinations( $raw, $normalized );
		self::validate_customs( $raw, $normalized );

		if ( isset( $raw['max_pause'] ) && is_scalar( $raw['max_pause'] ) ) {
			$requested_pause = absint( $raw['max_pause'] );
			if ( $requested_pause < 250 || $requested_pause > 5000 ) {
				add_settings_error( self::OPTION_NAME, 'adjusted_pause', __( 'Maximum pause was adjusted to the supported 250–5,000 ms range.', 'sesamo' ), 'warning' );
			}
		}

		return $normalized;
	}

	/** Return the safe browser configuration for every active combination. */
	public static function public_combinations( array $settings ): array {
		$settings = self::normalize( $settings );
		$presets  = Presets::all();
		$config   = array();

		foreach ( $settings['enabled_presets'] as $id ) {
			if ( ! isset( $presets[ $id ], $settings['preset_destinations'][ $id ] ) ) {
				continue;
			}
			$config[] = array(
				'id'             => $id,
				'label'          => $presets[ $id ]['label'],
				'source'         => 'preset',
				'sequence'       => $presets[ $id ]['sequence'],
				'destinationUrl' => $settings['preset_destinations'][ $id ],
			);
		}

		foreach ( $settings['custom_combinations'] as $custom ) {
			if ( ! $custom['enabled'] || array() === $custom['sequence'] || '' === $custom['destination_url'] ) {
				continue;
			}
			$config[] = array(
				'id'             => $custom['id'],
				'label'          => $custom['name'],
				'source'         => 'custom',
				'sequence'       => $custom['sequence'],
				'destinationUrl' => $custom['destination_url'],
			);
		}

		return $config;
	}

	/** Enqueue assets only on the Sesamo page. */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'netmilk-sesamo-admin', NETMILK_SESAMO_URL . 'assets/css/admin.css', array(), NETMILK_SESAMO_VERSION );
		wp_enqueue_script( 'netmilk-sesamo-admin', NETMILK_SESAMO_URL . 'assets/js/admin.js', array(), NETMILK_SESAMO_VERSION, true );
	}

	/** Render the configuration page. */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to configure Sesamo.', 'sesamo' ) );
		}

		$settings     = self::get();
		$presets      = Presets::all();
		$active_count = count( self::public_combinations( $settings ) );
		$errors       = get_settings_errors( self::OPTION_NAME );
		$custom_rows  = $settings['custom_combinations'];
		if ( count( $custom_rows ) < Combinations::MAX_CUSTOM ) {
			$custom_rows[] = self::blank_custom();
		}
		?>
		<div class="wrap sesamo-wrap">
			<header class="sesamo-header">
				<img class="sesamo-mark" src="<?php echo esc_url( NETMILK_SESAMO_URL . 'assets/images/sesamo-icon.png' ); ?>" alt="" width="64" height="64" />
				<div class="sesamo-header__copy">
					<h1><?php esc_html_e( 'Sesamo', 'sesamo' ); ?></h1>
					<p><?php esc_html_e( 'Secret sequence in. Hidden page out.', 'sesamo' ); ?></p>
				</div>
				<div class="sesamo-status <?php echo 0 === $active_count ? 'is-off' : 'is-armed'; ?>" data-sesamo-status data-title-armed="<?php esc_attr_e( 'Detector armed', 'sesamo' ); ?>" data-title-off="<?php esc_attr_e( 'Detection off', 'sesamo' ); ?>" aria-live="polite">
					<span class="sesamo-status__icon" aria-hidden="true"><?php echo 0 === $active_count ? '○' : '✓'; ?></span>
					<span>
						<strong data-sesamo-status-title><?php echo 0 === $active_count ? esc_html__( 'Detection off', 'sesamo' ) : esc_html__( 'Detector armed', 'sesamo' ); ?></strong>
						<small data-sesamo-status-count data-singular="<?php esc_attr_e( '1 combination active', 'sesamo' ); ?>" data-plural="<?php esc_attr_e( '%d combinations active', 'sesamo' ); ?>" data-off="<?php esc_attr_e( 'No frontend script will load', 'sesamo' ); ?>">
							<?php echo 0 === $active_count ? esc_html__( 'No frontend script will load', 'sesamo' ) : esc_html( sprintf( _n( '%d combination active', '%d combinations active', $active_count, 'sesamo' ), $active_count ) ); ?>
						</small>
					</span>
				</div>
			</header>

			<?php foreach ( $errors as $error ) : ?>
				<div class="notice notice-<?php echo esc_attr( 'warning' === $error['type'] ? 'warning' : 'error' ); ?> is-dismissible"><p><?php echo esc_html( $error['message'] ); ?></p></div>
			<?php endforeach; ?>

			<form action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<section class="sesamo-panel" aria-labelledby="sesamo-built-ins-heading">
					<div class="sesamo-panel__heading">
						<div>
							<h2 id="sesamo-built-ins-heading"><?php esc_html_e( '1. Built-in combinations', 'sesamo' ); ?></h2>
							<p><?php esc_html_e( 'Enable any classic sequence and choose where it leads.', 'sesamo' ); ?></p>
						</div>
						<div class="sesamo-bulk-actions">
							<button class="button-link" type="button" data-sesamo-select-all><?php esc_html_e( 'Select all', 'sesamo' ); ?></button>
							<span aria-hidden="true">·</span>
							<button class="button-link" type="button" data-sesamo-clear-presets><?php esc_html_e( 'Clear', 'sesamo' ); ?></button>
						</div>
					</div>

					<fieldset class="sesamo-presets">
						<legend class="screen-reader-text"><?php esc_html_e( 'Built-in key combinations', 'sesamo' ); ?></legend>
						<div class="sesamo-presets__header" aria-hidden="true">
							<span><?php esc_html_e( 'Active', 'sesamo' ); ?></span>
							<span><?php esc_html_e( 'Preset', 'sesamo' ); ?></span>
							<span><?php esc_html_e( 'Key sequence', 'sesamo' ); ?></span>
							<span><?php esc_html_e( 'Destination', 'sesamo' ); ?></span>
						</div>
						<?php foreach ( $presets as $id => $preset ) : ?>
							<div class="sesamo-preset" data-sesamo-route>
								<span class="sesamo-preset__check">
									<label>
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_presets][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $settings['enabled_presets'], true ) ); ?> data-sesamo-enabled />
										<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Activate %s', 'sesamo' ), $preset['label'] ) ); ?></span>
									</label>
								</span>
								<span class="sesamo-preset__identity">
									<strong><?php echo esc_html( $preset['label'] ); ?></strong>
									<small><?php echo esc_html( $preset['origin'] ); ?></small>
								</span>
								<span class="sesamo-keycaps" aria-label="<?php echo esc_attr( self::accessible_sequence( $preset['sequence'] ) ); ?>">
									<?php self::render_keycaps( $preset['sequence'] ); ?>
								</span>
								<span class="sesamo-preset__destination">
									<?php /* translators: %s is a built-in sequence name. */ ?>
									<input class="regular-text code sesamo-route-url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[preset_destinations][<?php echo esc_attr( $id ); ?>]" type="text" inputmode="url" value="<?php echo esc_attr( $settings['preset_destinations'][ $id ] ?? '' ); ?>" placeholder="/secret/" aria-label="<?php echo esc_attr( sprintf( __( '%s destination', 'sesamo' ), $preset['label'] ) ); ?>" data-sesamo-destination />
								</span>
							</div>
						<?php endforeach; ?>
					</fieldset>
				</section>

				<section class="sesamo-panel" aria-labelledby="sesamo-custom-heading">
					<div class="sesamo-panel__heading">
						<div>
							<h2 id="sesamo-custom-heading"><?php esc_html_e( '2. Custom combinations', 'sesamo' ); ?></h2>
							<p><?php esc_html_e( 'Create your own key sequences and assign a same-site destination to each.', 'sesamo' ); ?></p>
						</div>
					</div>

					<div class="sesamo-custom-list" data-sesamo-custom-list data-max="<?php echo esc_attr( (string) Combinations::MAX_CUSTOM ); ?>">
						<?php foreach ( $custom_rows as $index => $custom ) : ?>
							<?php self::render_custom_row( $custom, (string) $index ); ?>
						<?php endforeach; ?>
					</div>
					<template data-sesamo-custom-template><?php self::render_custom_row( self::blank_custom(), '__INDEX__' ); ?></template>
					<div class="sesamo-custom-actions">
						<button class="button" type="button" data-sesamo-add><?php esc_html_e( 'Add combination', 'sesamo' ); ?></button>
						<small data-sesamo-custom-count><?php echo esc_html( sprintf( __( 'Up to %d custom combinations.', 'sesamo' ), Combinations::MAX_CUSTOM ) ); ?></small>
					</div>
					<p class="description sesamo-custom-help" id="sesamo-sequence-help">
						<?php esc_html_e( 'Record keys or enter whitespace-separated KeyboardEvent.key values. Use Space for the space bar. Modifier-only keys are rejected.', 'sesamo' ); ?>
					</p>
				</section>

				<section class="sesamo-panel" aria-labelledby="sesamo-timing-heading">
					<div class="sesamo-panel__heading">
						<div>
							<h2 id="sesamo-timing-heading"><?php esc_html_e( '3. Timing and safety', 'sesamo' ); ?></h2>
							<p><?php esc_html_e( 'Adjust how quickly keys must be entered to register a combination.', 'sesamo' ); ?></p>
						</div>
					</div>

					<label class="sesamo-field sesamo-field--compact" for="sesamo-max-pause">
						<span><?php esc_html_e( 'Maximum pause between keys', 'sesamo' ); ?></span>
						<span class="sesamo-number">
							<input id="sesamo-max-pause" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_pause]" type="number" min="250" max="5000" step="50" value="<?php echo esc_attr( (string) $settings['max_pause'] ); ?>" aria-describedby="sesamo-pause-help" />
							<span><?php esc_html_e( 'ms', 'sesamo' ); ?></span>
						</span>
						<small id="sesamo-pause-help"><?php esc_html_e( 'A partial combination resets after this period of inactivity.', 'sesamo' ); ?></small>
					</label>

					<aside class="sesamo-safety">
						<strong><?php esc_html_e( 'Good citizen mode', 'sesamo' ); ?></strong>
						<p><?php esc_html_e( 'No tracking, cookies, remote requests, or typing-field interception. Sesamo is an easter egg—not access control. Protect sensitive content with real authentication.', 'sesamo' ); ?></p>
					</aside>
				</section>

				<div class="sesamo-actions">
					<?php submit_button( __( 'Save settings', 'sesamo' ), 'primary', 'submit', false ); ?>
					<span><?php esc_html_e( 'Netmilk Studio sagl · no telemetry, no nonsense.', 'sesamo' ); ?></span>
				</div>
			</form>
		</div>
		<?php
	}

	/** Render one custom-combination editor row. */
	private static function render_custom_row( array $custom, string $index ): void {
		$prefix = self::OPTION_NAME . '[custom_combinations][' . $index . ']';
		$row_id = 'sesamo-custom-' . $index;
		?>
		<fieldset class="sesamo-custom" data-sesamo-custom data-sesamo-route>
			<legend class="screen-reader-text"><?php esc_html_e( 'Custom combination', 'sesamo' ); ?></legend>
			<input name="<?php echo esc_attr( $prefix ); ?>[id]" type="hidden" value="<?php echo esc_attr( $custom['id'] ); ?>" />
			<label class="sesamo-custom__enabled">
				<input name="<?php echo esc_attr( $prefix ); ?>[enabled]" type="checkbox" value="1" <?php checked( $custom['enabled'] ); ?> data-sesamo-enabled />
				<span class="screen-reader-text"><?php esc_html_e( 'Enable custom combination', 'sesamo' ); ?></span>
			</label>
			<label class="sesamo-custom__name" for="<?php echo esc_attr( $row_id . '-name' ); ?>">
				<span><?php esc_html_e( 'Name', 'sesamo' ); ?></span>
				<input id="<?php echo esc_attr( $row_id . '-name' ); ?>" name="<?php echo esc_attr( $prefix ); ?>[name]" type="text" maxlength="64" value="<?php echo esc_attr( $custom['name'] ); ?>" placeholder="<?php esc_attr_e( 'Open the vault', 'sesamo' ); ?>" data-sesamo-name />
			</label>
			<div class="sesamo-custom__sequence">
				<label for="<?php echo esc_attr( $row_id . '-sequence' ); ?>"><?php esc_html_e( 'Key sequence', 'sesamo' ); ?></label>
				<input class="code sesamo-sequence-input" id="<?php echo esc_attr( $row_id . '-sequence' ); ?>" name="<?php echo esc_attr( $prefix ); ?>[sequence]" type="text" value="<?php echo esc_attr( implode( ' ', $custom['sequence'] ) ); ?>" placeholder="s e s a m o" aria-describedby="sesamo-sequence-help" autocomplete="off" spellcheck="false" data-sesamo-sequence />
				<div class="sesamo-keycaps sesamo-custom__preview" aria-live="polite" data-sesamo-preview><?php self::render_keycaps( $custom['sequence'] ); ?></div>
				<div class="sesamo-recorder-actions">
					<button class="button" type="button" data-sesamo-record data-label-record="<?php esc_attr_e( 'Record sequence', 'sesamo' ); ?>" data-label-stop="<?php esc_attr_e( 'Stop recording', 'sesamo' ); ?>"><?php esc_html_e( 'Record sequence', 'sesamo' ); ?></button>
					<button class="button-link" type="button" data-sesamo-clear-sequence><?php esc_html_e( 'Clear', 'sesamo' ); ?></button>
				</div>
			</div>
			<label class="sesamo-custom__destination" for="<?php echo esc_attr( $row_id . '-destination' ); ?>">
				<span><?php esc_html_e( 'Destination URL', 'sesamo' ); ?></span>
				<input class="code sesamo-route-url" id="<?php echo esc_attr( $row_id . '-destination' ); ?>" name="<?php echo esc_attr( $prefix ); ?>[destination_url]" type="text" inputmode="url" value="<?php echo esc_attr( $custom['destination_url'] ); ?>" placeholder="/secret/" data-sesamo-destination />
			</label>
			<button class="button-link-delete sesamo-custom__remove" type="button" data-sesamo-remove><?php esc_html_e( 'Remove', 'sesamo' ); ?></button>
		</fieldset>
		<?php
	}

	/** Render a sequence as visible keycaps. */
	private static function render_keycaps( array $sequence ): void {
		foreach ( $sequence as $key ) {
			?>
			<kbd aria-hidden="true"><?php echo esc_html( self::display_key( $key ) ); ?></kbd>
			<?php
		}
	}

	/** Return a blank custom editor row. */
	private static function blank_custom(): array {
		return array(
			'id'              => '',
			'enabled'         => false,
			'name'            => '',
			'sequence'        => array(),
			'destination_url' => '',
		);
	}

	/** Unslash supported nested scalar fields without recursively trusting the payload. */
	private static function unslash_form_input( array $input ): array {
		if ( isset( $input['enabled_presets'] ) && is_array( $input['enabled_presets'] ) ) {
			$input['enabled_presets'] = array_map(
				static function ( $value ) {
					return is_string( $value ) ? wp_unslash( $value ) : $value;
				},
				$input['enabled_presets']
			);
		}
		if ( isset( $input['preset_destinations'] ) && is_array( $input['preset_destinations'] ) ) {
			foreach ( $input['preset_destinations'] as $id => $value ) {
				if ( is_string( $value ) ) {
					$input['preset_destinations'][ $id ] = wp_unslash( $value );
				}
			}
		}
		if ( isset( $input['destination_url'] ) && is_string( $input['destination_url'] ) ) {
			$input['destination_url'] = wp_unslash( $input['destination_url'] );
		}
		if ( isset( $input['custom_combinations'] ) && is_array( $input['custom_combinations'] ) ) {
			foreach ( $input['custom_combinations'] as $index => $custom ) {
				if ( ! is_array( $custom ) ) {
					continue;
				}
				foreach ( array( 'id', 'name', 'sequence', 'destination_url' ) as $field ) {
					if ( isset( $custom[ $field ] ) && is_string( $custom[ $field ] ) ) {
						$input['custom_combinations'][ $index ][ $field ] = wp_unslash( $custom[ $field ] );
					}
				}
			}
		}
		return $input;
	}

	/** Add one warning when an enabled built-in has no safe destination. */
	private static function validate_preset_destinations( array $raw, array $normalized ): void {
		$requested = isset( $raw['enabled_presets'] ) && is_array( $raw['enabled_presets'] ) ? $raw['enabled_presets'] : array();
		foreach ( $requested as $id ) {
			if ( ! is_string( $id ) ) {
				continue;
			}
			$id = sanitize_key( $id );
			if ( ! isset( $normalized['preset_destinations'][ $id ] ) ) {
				add_settings_error( self::OPTION_NAME, 'invalid_preset_destination', __( 'Every active built-in combination needs an HTTP(S) destination on this WordPress site. Invalid routes were left inactive.', 'sesamo' ), 'error' );
				return;
			}
		}
	}

	/** Add bounded, non-sensitive validation feedback for custom rows. */
	private static function validate_customs( array $raw, array $normalized ): void {
		$customs = isset( $raw['custom_combinations'] ) && is_array( $raw['custom_combinations'] ) ? array_values( $raw['custom_combinations'] ) : array();
		if ( count( $customs ) > Combinations::MAX_CUSTOM ) {
			add_settings_error( self::OPTION_NAME, 'too_many_customs', sprintf( __( 'Only the first %d custom combinations were saved.', 'sesamo' ), Combinations::MAX_CUSTOM ), 'warning' );
		}

		$presets = Presets::all();
		$active_sequences = array();
		foreach ( $normalized['enabled_presets'] as $id ) {
			if ( isset( $presets[ $id ], $normalized['preset_destinations'][ $id ] ) ) {
				$active_sequences[] = $presets[ $id ]['sequence'];
			}
		}

		$invalid   = false;
		$duplicate = false;
		foreach ( array_slice( $customs, 0, Combinations::MAX_CUSTOM ) as $custom ) {
			if ( ! is_array( $custom ) ) {
				$invalid = true;
				continue;
			}
			$name = isset( $custom['name'] ) && is_string( $custom['name'] ) ? trim( $custom['name'] ) : '';
			$sequence = Combinations::normalize_sequence( $custom['sequence'] ?? array() );
			$destination = isset( $custom['destination_url'] ) && is_string( $custom['destination_url'] ) ? self::normalize_destination( trim( $custom['destination_url'] ) ) : '';
			$requested_enabled = isset( $custom['enabled'] ) && in_array( $custom['enabled'], array( true, 1, '1', 'on' ), true );
			$has_content = '' !== $name || array() !== $sequence || '' !== $destination || $requested_enabled;
			if ( ! $has_content ) {
				continue;
			}
			if ( '' === $name || array() === $sequence || '' === $destination ) {
				$invalid = true;
				continue;
			}
			if ( $requested_enabled ) {
				if ( Combinations::conflicts_with_any( $sequence, $active_sequences ) ) {
					$duplicate = true;
				} else {
					$active_sequences[] = $sequence;
				}
			}
		}

		if ( $invalid ) {
			add_settings_error( self::OPTION_NAME, 'invalid_custom', __( 'Incomplete or invalid custom combinations were saved disabled. Add a name, 2–64 supported keys, and a same-site destination.', 'sesamo' ), 'error' );
		}
		if ( $duplicate ) {
			add_settings_error( self::OPTION_NAME, 'duplicate_custom', __( 'A conflicting active sequence was saved disabled. Active combinations cannot duplicate, prefix, or suffix one another.', 'sesamo' ), 'warning' );
		}
	}

	/** Return a same-origin HTTP(S) URL or an empty string. */
	private static function normalize_destination( string $destination ): string {
		if ( strlen( $destination ) > 2048 || '' === $destination || 0 === strpos( $destination, '//' ) ) {
			return '';
		}
		if ( '/' === substr( $destination, 0, 1 ) ) {
			$destination = home_url( $destination );
		}

		$destination = esc_url_raw( $destination, array( 'http', 'https' ) );
		if ( '' === $destination ) {
			return '';
		}

		$target = wp_parse_url( $destination );
		$home   = wp_parse_url( home_url( '/' ) );
		if ( ! is_array( $target ) || ! is_array( $home ) || isset( $target['user'] ) || isset( $target['pass'] ) ) {
			return '';
		}

		$target_scheme = isset( $target['scheme'] ) ? strtolower( $target['scheme'] ) : '';
		$target_host   = isset( $target['host'] ) ? strtolower( $target['host'] ) : '';
		$home_scheme   = isset( $home['scheme'] ) ? strtolower( $home['scheme'] ) : '';
		$home_host     = isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
		$target_port   = isset( $target['port'] ) ? (int) $target['port'] : self::default_port( $target_scheme );
		$home_port     = isset( $home['port'] ) ? (int) $home['port'] : self::default_port( $home_scheme );

		if ( ! in_array( $target_scheme, array( 'http', 'https' ), true ) || $target_scheme !== $home_scheme || $target_host !== $home_host || $target_port !== $home_port ) {
			return '';
		}

		return $destination;
	}

	/** Build a fail-closed default without recursive normalization. */
	private static function safe_home_destination( string $path ): string {
		$destination = esc_url_raw( home_url( $path ), array( 'http', 'https' ) );
		$parts       = wp_parse_url( $destination );
		if ( ! is_array( $parts ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return '';
		}
		$scheme = isset( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';
		$host   = isset( $parts['host'] ) ? $parts['host'] : '';
		return in_array( $scheme, array( 'http', 'https' ), true ) && '' !== $host ? $destination : '';
	}

	/** Return the implicit port for an HTTP scheme. */
	private static function default_port( string $scheme ): int {
		return 'https' === $scheme ? 443 : 80;
	}

	/** Return the first active route as the 0.1 rollback bridge. */
	private static function first_destination( array $enabled, array $preset_destinations, array $customs ): string {
		foreach ( $enabled as $id ) {
			if ( isset( $preset_destinations[ $id ] ) ) {
				return $preset_destinations[ $id ];
			}
		}
		foreach ( $customs as $custom ) {
			if ( $custom['enabled'] && '' !== $custom['destination_url'] ) {
				return $custom['destination_url'];
			}
		}
		return '';
	}

	/** Create a deterministic stable ID for a newly saved custom row. */
	private static function custom_id( string $name, array $sequence, string $destination, int $index ): string {
		return 'custom_' . substr( hash( 'sha256', $name . "\x00" . implode( "\x1F", $sequence ) . "\x00" . $destination . "\x00" . (string) $index ), 0, 16 );
	}

	/** Limit admin labels while preserving UTF-8 when mbstring is available. */
	private static function limit_text( string $text, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $length, 'UTF-8' ) : substr( $text, 0, $length );
	}

	/** Render KeyboardEvent.key values as compact keycap labels. */
	private static function display_key( string $key ): string {
		$labels = array(
			'ArrowUp'    => '↑',
			'ArrowDown'  => '↓',
			'ArrowLeft'  => '←',
			'ArrowRight' => '→',
			'Enter'      => '↵',
			'Escape'     => 'Esc',
			'Backspace'  => '⌫',
			'Delete'     => 'Del',
			'PageUp'     => 'PgUp',
			'PageDown'   => 'PgDn',
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : ( 1 === preg_match( '/^[a-z]$/', $key ) ? strtoupper( $key ) : $key );
	}

	/** Render a sequence as speech-friendly English text. */
	private static function accessible_sequence( array $sequence ): string {
		$labels = array(
			'ArrowUp'    => __( 'Up', 'sesamo' ),
			'ArrowDown'  => __( 'Down', 'sesamo' ),
			'ArrowLeft'  => __( 'Left', 'sesamo' ),
			'ArrowRight' => __( 'Right', 'sesamo' ),
			'Space'      => __( 'Space', 'sesamo' ),
		);
		return implode(
			', ',
			array_map(
				static function ( string $key ) use ( $labels ): string {
					return isset( $labels[ $key ] ) ? $labels[ $key ] : strtoupper( $key );
				},
				$sequence
			)
		);
	}
}
