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
	private const PAGE_SLUG         = 'sesamo';
	private const GROUP             = 'netmilk_sesamo';

	/** Register admin hooks. */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'add_page' ) );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/** Create or migrate the one plugin option on activation. */
	public static function activate(): void {
		if ( false !== get_option( self::OPTION_NAME, false ) ) {
			return;
		}

		$legacy = get_option( self::LEGACY_OPTION_NAME, false );
		add_option( self::OPTION_NAME, false === $legacy ? self::defaults() : self::normalize( $legacy ) );
	}

	/** Return safe defaults. */
	public static function defaults(): array {
		return array(
			'enabled_presets' => Presets::default_ids(),
			'destination_url' => self::safe_home_destination( '/iddqd/' ),
			'max_pause'       => 1500,
		);
	}

	/**
	 * Read settings through the same trust boundary used for form input.
	 *
	 * The options table is not trusted: another plugin, an import, or direct
	 * database access can bypass this plugin's save callback.
	 */
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
		add_options_page(
			__( 'Sesamo', 'sesamo' ),
			__( 'Sesamo', 'sesamo' ),
			'manage_options',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Normalize arbitrary option data without emitting UI side effects.
	 *
	 * @param mixed $candidate Candidate option data.
	 */
	public static function normalize( $candidate ): array {
		$defaults = self::defaults();
		$input    = is_array( $candidate ) ? $candidate : array();

		$requested = isset( $input['enabled_presets'] ) && is_array( $input['enabled_presets'] )
			? $input['enabled_presets']
			: array();
		$requested = array_filter( $requested, 'is_string' );
		$requested = array_map( 'sanitize_key', $requested );
		$enabled   = array_values(
			array_intersect( array_keys( Presets::all() ), array_unique( $requested ) )
		);

		$destination = isset( $input['destination_url'] ) && is_string( $input['destination_url'] )
			? trim( $input['destination_url'] )
			: '';
		$destination = self::normalize_destination( $destination );

		$pause_candidate = isset( $input['max_pause'] ) && is_scalar( $input['max_pause'] )
			? $input['max_pause']
			: $defaults['max_pause'];
		$max_pause       = max( 250, min( 5000, absint( $pause_candidate ) ) );

		return array(
			'enabled_presets' => $enabled,
			'destination_url' => '' === $destination ? $defaults['destination_url'] : $destination,
			'max_pause'       => $max_pause,
		);
	}

	/**
	 * Sanitize a settings form payload and record actionable recovery notices.
	 *
	 * @param mixed $input Raw Settings API input.
	 */
	public static function sanitize( $input ): array {
		$raw        = is_array( $input ) ? $input : array();
		$normalized = self::normalize( self::unslash_form_input( $raw ) );

		$raw_url = isset( $raw['destination_url'] ) && is_string( $raw['destination_url'] )
			? trim( wp_unslash( $raw['destination_url'] ) )
			: '';
		if ( '' === self::normalize_destination( $raw_url ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'invalid_destination',
				__( 'Destination must be an HTTP(S) URL on this WordPress site. The safe default was restored.', 'sesamo' ),
				'error'
			);
		}

		if ( isset( $raw['max_pause'] ) && is_scalar( $raw['max_pause'] ) ) {
			$requested_pause = absint( $raw['max_pause'] );
			if ( $requested_pause < 250 || $requested_pause > 5000 ) {
				add_settings_error(
					self::OPTION_NAME,
					'adjusted_pause',
					__( 'Maximum pause was adjusted to the supported 250–5,000 ms range.', 'sesamo' ),
					'warning'
				);
			}
		}

		return $normalized;
	}

	/** Enqueue assets only on the Sesamo page. */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'netmilk-sesamo-admin',
			NETMILK_SESAMO_URL . 'assets/css/admin.css',
			array(),
			NETMILK_SESAMO_VERSION
		);
		wp_enqueue_script(
			'netmilk-sesamo-admin',
			NETMILK_SESAMO_URL . 'assets/js/admin.js',
			array(),
			NETMILK_SESAMO_VERSION,
			true
		);
	}

	/** Render the configuration page. */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to configure Sesamo.', 'sesamo' ) );
		}

		$settings     = self::get();
		$presets      = Presets::all();
		$active_count = count( $settings['enabled_presets'] );
		$errors       = get_settings_errors( self::OPTION_NAME );
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
						<small data-sesamo-status-count data-singular="<?php esc_attr_e( '1 sequence active', 'sesamo' ); ?>" data-plural="<?php esc_attr_e( '%d sequences active', 'sesamo' ); ?>" data-off="<?php esc_attr_e( 'No frontend script will load', 'sesamo' ); ?>">
							<?php echo 0 === $active_count ? esc_html__( 'No frontend script will load', 'sesamo' ) : esc_html( sprintf( _n( '%d sequence active', '%d sequences active', $active_count, 'sesamo' ), $active_count ) ); ?>
						</small>
					</span>
				</div>
			</header>

			<?php foreach ( $errors as $error ) : ?>
				<div class="notice notice-<?php echo esc_attr( 'warning' === $error['type'] ? 'warning' : 'error' ); ?> is-dismissible"><p><?php echo esc_html( $error['message'] ); ?></p></div>
			<?php endforeach; ?>

			<form action="options.php" method="post">
				<?php settings_fields( self::GROUP ); ?>

				<section class="sesamo-panel" aria-labelledby="sesamo-sequences-heading">
					<div class="sesamo-panel__heading">
						<div>
							<h2 id="sesamo-sequences-heading"><?php esc_html_e( '1. Secret sequences', 'sesamo' ); ?></h2>
							<p><?php esc_html_e( 'Choose one or more classic sequences. Sesamo listens only outside typing fields.', 'sesamo' ); ?></p>
						</div>
						<div class="sesamo-bulk-actions">
							<button class="button-link" type="button" data-sesamo-select-all><?php esc_html_e( 'Select all', 'sesamo' ); ?></button>
							<span aria-hidden="true">·</span>
							<button class="button-link" type="button" data-sesamo-clear><?php esc_html_e( 'Clear', 'sesamo' ); ?></button>
						</div>
					</div>

					<fieldset class="sesamo-presets">
						<legend class="screen-reader-text"><?php esc_html_e( 'Available secret sequences', 'sesamo' ); ?></legend>
						<div class="sesamo-presets__header" aria-hidden="true">
							<span><?php esc_html_e( 'Active', 'sesamo' ); ?></span>
							<span><?php esc_html_e( 'Preset', 'sesamo' ); ?></span>
							<span><?php esc_html_e( 'Key sequence', 'sesamo' ); ?></span>
						</div>
						<?php foreach ( $presets as $id => $preset ) : ?>
							<label class="sesamo-preset">
								<span class="sesamo-preset__check">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled_presets][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $settings['enabled_presets'], true ) ); ?> />
									<span class="screen-reader-text"><?php esc_html_e( 'Activate', 'sesamo' ); ?></span>
								</span>
								<span class="sesamo-preset__identity">
									<strong><?php echo esc_html( $preset['label'] ); ?></strong>
									<small><?php echo esc_html( $preset['origin'] ); ?></small>
								</span>
								<span class="sesamo-keycaps" aria-label="<?php echo esc_attr( self::accessible_sequence( $preset['sequence'] ) ); ?>">
									<?php foreach ( $preset['sequence'] as $key ) : ?>
										<kbd aria-hidden="true"><?php echo esc_html( self::display_key( $key ) ); ?></kbd>
									<?php endforeach; ?>
								</span>
							</label>
						<?php endforeach; ?>
					</fieldset>

					<div class="sesamo-off-note" data-sesamo-off-note <?php echo 0 === $active_count ? '' : 'hidden'; ?>>
						<?php esc_html_e( 'Detection is off. Sesamo will not load anything on public pages until you activate a sequence.', 'sesamo' ); ?>
					</div>
				</section>

				<section class="sesamo-panel" aria-labelledby="sesamo-destination-heading">
					<div class="sesamo-panel__heading">
						<div>
							<h2 id="sesamo-destination-heading"><?php esc_html_e( '2. Unlock destination', 'sesamo' ); ?></h2>
							<p><?php esc_html_e( 'Choose the page visitors reach after a valid sequence.', 'sesamo' ); ?></p>
						</div>
					</div>

					<div class="sesamo-fields">
						<label class="sesamo-field" for="sesamo-destination-url">
							<span><?php esc_html_e( 'Destination URL', 'sesamo' ); ?></span>
							<input class="regular-text code" id="sesamo-destination-url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[destination_url]" type="url" value="<?php echo esc_attr( $settings['destination_url'] ); ?>" aria-describedby="sesamo-destination-help" required />
							<small id="sesamo-destination-help"><?php esc_html_e( 'Enter an HTTP(S) URL on this WordPress site. Relative paths such as /secret/ are accepted when saving.', 'sesamo' ); ?></small>
						</label>

						<label class="sesamo-field sesamo-field--compact" for="sesamo-max-pause">
							<span><?php esc_html_e( 'Maximum pause between keys', 'sesamo' ); ?></span>
							<span class="sesamo-number">
								<input id="sesamo-max-pause" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_pause]" type="number" min="250" max="5000" step="50" value="<?php echo esc_attr( (string) $settings['max_pause'] ); ?>" aria-describedby="sesamo-pause-help" />
								<span><?php esc_html_e( 'ms', 'sesamo' ); ?></span>
							</span>
							<small id="sesamo-pause-help"><?php esc_html_e( 'A partial sequence resets after this period of inactivity.', 'sesamo' ); ?></small>
						</label>
					</div>

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

	/** Unslash supported scalar fields without recursively trusting the payload. */
	private static function unslash_form_input( array $input ): array {
		if ( isset( $input['enabled_presets'] ) && is_array( $input['enabled_presets'] ) ) {
			$input['enabled_presets'] = array_map(
				static function ( $value ) {
					return is_string( $value ) ? wp_unslash( $value ) : $value;
				},
				$input['enabled_presets']
			);
		}
		if ( isset( $input['destination_url'] ) && is_string( $input['destination_url'] ) ) {
			$input['destination_url'] = wp_unslash( $input['destination_url'] );
		}
		return $input;
	}

	/** Return a same-origin HTTP(S) URL or an empty string. */
	private static function normalize_destination( string $destination ): string {
		if ( '' === $destination || 0 === strpos( $destination, '//' ) ) {
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

	/**
	 * Build a fail-closed default without feeding an invalid value back through
	 * normalize_destination(), which itself depends on the default.
	 */
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

	/** Render KeyboardEvent.key values as compact keycap labels. */
	private static function display_key( string $key ): string {
		$labels = array(
			'ArrowUp'    => '↑',
			'ArrowDown'  => '↓',
			'ArrowLeft'  => '←',
			'ArrowRight' => '→',
		);
		return isset( $labels[ $key ] ) ? $labels[ $key ] : strtoupper( $key );
	}

	/** Render a sequence as speech-friendly English text for assistive technology. */
	private static function accessible_sequence( array $sequence ): string {
		$labels = array(
			'ArrowUp'    => __( 'Up', 'sesamo' ),
			'ArrowDown'  => __( 'Down', 'sesamo' ),
			'ArrowLeft'  => __( 'Left', 'sesamo' ),
			'ArrowRight' => __( 'Right', 'sesamo' ),
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
