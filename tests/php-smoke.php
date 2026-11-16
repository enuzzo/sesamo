<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ );

function __( $text ) {
	return $text;
}

function home_url( $path = '' ) {
	return $GLOBALS['sesamo_home_url'] . $path;
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_text_field( $text ) {
	return trim( strip_tags( (string) $text ) );
}

function wp_unslash( $value ) {
	return stripslashes( $value );
}

function esc_url_raw( $url, $protocols = null ) {
	if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return '';
	}
	$scheme = parse_url( $url, PHP_URL_SCHEME );
	return in_array( $scheme, $protocols ?: array( 'http', 'https' ), true ) ? $url : '';
}

function wp_parse_url( $url ) {
	return parse_url( $url );
}

function add_settings_error( $setting, $code, $message, $type = 'error' ) {
	$GLOBALS['sesamo_test_errors'][] = compact( 'setting', 'code', 'message', 'type' );
}

function get_settings_errors( $setting = '' ) {
	$errors = $GLOBALS['sesamo_test_errors'];
	if ( '' === $setting ) {
		return $errors;
	}
	return array_values( array_filter( $errors, static function ( $error ) use ( $setting ) { return $error['setting'] === $setting; } ) );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_html__( $text ) {
	return esc_html( $text );
}

define( 'MINUTE_IN_SECONDS', 60 );

function get_current_user_id() {
	return 7;
}

function set_transient( $key, $value, $expiration = 0 ) {
	$GLOBALS['sesamo_transients'][ $key ] = $value;
	return true;
}

function get_transient( $key ) {
	return $GLOBALS['sesamo_transients'][ $key ] ?? false;
}

function delete_transient( $key ) {
	unset( $GLOBALS['sesamo_transients'][ $key ] );
	return true;
}

function absint( $value ) {
	return abs( (int) $value );
}

require_once dirname( __DIR__ ) . '/includes/class-presets.php';
require_once dirname( __DIR__ ) . '/includes/class-combinations.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';

use NetmilkStudio\Sesamo\Combinations;
use NetmilkStudio\Sesamo\Presets;
use NetmilkStudio\Sesamo\Settings;

$GLOBALS['sesamo_home_url']   = 'https://example.test';
$GLOBALS['sesamo_test_errors'] = array();

function sesamo_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

sesamo_assert( 10 === count( Presets::all() ), 'The registry exposes ten presets.' );
sesamo_assert( array( 'konami', 'iddqd' ) === Presets::default_ids(), 'Default preset IDs stay stable.' );
sesamo_assert( 2 === Settings::SCHEMA_VERSION, 'The option schema is explicitly versioned.' );
sesamo_assert( array( 'ArrowUp', 'a', 'Space' ) === array_map( array( Combinations::class, 'normalize_token' ), array( 'arrowup', 'A', 'space' ) ), 'Supported key tokens are canonicalised.' );
sesamo_assert( array() === Combinations::normalize_sequence( 'a Control b' ), 'One unsafe token rejects the complete sequence.' );
sesamo_assert( array() === Combinations::normalize_sequence( 'x' ), 'Custom sequences require at least two keys.' );
sesamo_assert( array() === Combinations::normalize_sequence( str_repeat( 'x ', 2000 ) ), 'Oversized sequence strings are rejected before tokenization.' );
sesamo_assert( Combinations::conflict( array( 'i', 'd' ), array( 'i', 'd', 'd', 'q', 'd' ) ), 'Prefix collisions are rejected.' );
sesamo_assert( Combinations::conflict( array( 'd', 'q', 'd' ), array( 'i', 'd', 'd', 'q', 'd' ) ), 'Suffix collisions are rejected.' );
sesamo_assert( ! Combinations::conflict( array( 's', 'e', 's' ), array( 'i', 'd', 'd', 'q', 'd' ) ), 'Unrelated sequences remain valid.' );

$migrated = Settings::normalize(
	array(
		'enabled_presets' => array( 'iddqd', 'unknown', 'konami', 'iddqd', array( 'nested' ) ),
		'destination_url' => '/custom-easter-egg/',
		'max_pause'       => 9999,
	)
);

sesamo_assert( 2 === $migrated['schema_version'], 'Legacy option data migrates to schema 2.' );
sesamo_assert( array( 'konami', 'iddqd' ) === $migrated['enabled_presets'], 'Preset IDs are allowlisted and canonicalised.' );
sesamo_assert( 'https://example.test/custom-easter-egg/' === $migrated['preset_destinations']['konami'], 'Legacy destinations are copied to every enabled preset.' );
sesamo_assert( 'https://example.test/custom-easter-egg/' === $migrated['preset_destinations']['iddqd'], 'Migration preserves the old shared-route behaviour.' );
sesamo_assert( array() === $migrated['custom_combinations'], 'Migration does not invent custom combinations.' );
sesamo_assert( 5000 === $migrated['max_pause'], 'Timeout is clamped to the maximum.' );

$configured = Settings::sanitize(
	array(
		'schema_version'      => 2,
		'enabled_presets'     => array( 'konami', 'iddqd' ),
		'preset_destinations' => array(
			'konami' => '/konami/',
			'iddqd'  => '/godmode/',
		),
		'custom_combinations' => array(
			array(
				'enabled'         => '1',
				'name'            => 'Open the <b>vault</b>',
				'sequence'        => 'S E S A M O',
				'destination_url' => '/vault/',
			),
			array(
				'enabled'         => '1',
				'name'            => 'Duplicate IDDQD',
				'sequence'        => 'i d d q d',
				'destination_url' => '/duplicate/',
			),
			array(
				'enabled'         => '1',
				'name'            => 'Broken token',
				'sequence'        => 'a Control b',
				'destination_url' => 'https://evil.example/',
			),
		),
		'max_pause'           => 1500,
	)
);

sesamo_assert( 'https://example.test/konami/' === $configured['preset_destinations']['konami'], 'Each preset keeps its own destination.' );
sesamo_assert( 'https://example.test/godmode/' === $configured['preset_destinations']['iddqd'], 'Preset routes remain independent.' );
sesamo_assert( 3 === count( $configured['custom_combinations'] ), 'Non-empty custom rows are retained for recovery.' );
sesamo_assert( true === $configured['custom_combinations'][0]['enabled'], 'A complete unique custom combination is enabled.' );
sesamo_assert( 'Open the vault' === $configured['custom_combinations'][0]['name'], 'Custom labels are sanitized.' );
sesamo_assert( array( 's', 'e', 's', 'a', 'm', 'o' ) === $configured['custom_combinations'][0]['sequence'], 'Custom keys are normalized.' );
sesamo_assert( false === $configured['custom_combinations'][1]['enabled'], 'A sequence colliding with an active preset is disabled.' );
sesamo_assert( false === $configured['custom_combinations'][2]['enabled'], 'Incomplete or unsafe custom rows fail closed.' );
sesamo_assert( '' === $configured['custom_combinations'][2]['destination_url'], 'Cross-origin custom destinations never persist.' );

$public = Settings::public_combinations( $configured );
sesamo_assert( 3 === count( $public ), 'Two presets and one valid custom route reach the browser.' );
sesamo_assert( 'https://example.test/vault/' === $public[2]['destinationUrl'], 'The custom route owns its destination.' );
sesamo_assert( 'custom' === $public[2]['source'], 'Public configuration identifies custom sources.' );
sesamo_assert( preg_match( '/^custom_[a-f0-9]{16}$/', $public[2]['id'] ) === 1, 'Custom routes receive bounded stable IDs.' );
$renormalized = Settings::normalize( $configured );
sesamo_assert( $configured['custom_combinations'][0]['id'] === $renormalized['custom_combinations'][0]['id'], 'Custom IDs remain stable after subsequent reads.' );

$error_codes = array_column( $GLOBALS['sesamo_test_errors'], 'code' );
sesamo_assert( in_array( 'duplicate_custom', $error_codes, true ), 'Duplicate sequences register a recovery warning.' );
sesamo_assert( in_array( 'invalid_custom', $error_codes, true ), 'Invalid custom rows register a recovery error.' );

$invalid_route = Settings::sanitize(
	array(
		'schema_version'      => 2,
		'enabled_presets'     => array( 'konami' ),
		'preset_destinations' => array( 'konami' => 'https://evil.example/phish' ),
		'max_pause'           => 1,
	)
);
sesamo_assert( ! isset( $invalid_route['preset_destinations']['konami'] ), 'An explicitly invalid schema-2 route does not fall back silently.' );
sesamo_assert( array() === Settings::public_combinations( $invalid_route ), 'No valid route means no public detector configuration.' );
sesamo_assert( 250 === $invalid_route['max_pause'], 'Timeout is clamped to the minimum.' );

// Destination feedback: explain cross-site rejections and keep what the administrator typed.
sesamo_assert( '' === Settings::destination_problem( '/secret/' ), 'Same-site paths are usable.' );
sesamo_assert( '' === Settings::destination_problem( 'https://example.test/secret/' ), 'Same-site URLs are usable.' );
sesamo_assert( 'empty' === Settings::destination_problem( '  ' ), 'Blank destinations are reported as empty.' );
sesamo_assert( 'other_site' === Settings::destination_problem( 'https://pharma.example/iddqd/' ), 'Another host is reported as another website.' );
sesamo_assert( 'other_site' === Settings::destination_problem( '//pharma.example/iddqd/' ), 'Protocol-relative hosts are reported as another website.' );
sesamo_assert( 'other_scheme' === Settings::destination_problem( 'http://example.test/secret/' ), 'A different scheme on this host is reported as such.' );
sesamo_assert( 'invalid' === Settings::destination_problem( 'javascript:alert(1)' ), 'Script URLs are unusable, not another website.' );
sesamo_assert( 'invalid' === Settings::destination_problem( 'secret' ), 'Bare words are unusable.' );
sesamo_assert( '/iddqd/' === Settings::suggested_path( 'https://pharma.example/iddqd/', '/fallback/' ), 'The suggestion reuses the external path.' );
sesamo_assert( '/fallback/' === Settings::suggested_path( 'https://pharma.example/', '/fallback/' ), 'A bare external host falls back to a route path.' );
sesamo_assert( '/fallback/' === Settings::suggested_path( 'https://pharma.example/<b>/', '/fallback/' ), 'Unsafe external paths are never suggested.' );

$GLOBALS['sesamo_test_errors'] = array();
$GLOBALS['sesamo_transients']  = array();
// Mirrors the settings form: no schema_version, every preset destination posted.
$form_post = array(
	'enabled_presets'     => array( 'konami', 'iddqd' ),
	'preset_destinations' => array(
		'konami' => '',
		'iddqd'  => 'https://pharma.example/iddqd/',
		'idkfa'  => 'https://pharma.example/idkfa/',
	),
	'custom_combinations' => array(
		array( 'name' => 'Vault', 'sequence' => 'v a u l t', 'destination_url' => 'https://pharma.example/vault/', 'enabled' => '1' ),
	),
);
$first_pass = Settings::sanitize( $form_post );
// update_option() sanitizes the stored value a second time when it creates the option.
Settings::sanitize( $first_pass );
$codes = array_column( $GLOBALS['sesamo_test_errors'], 'code' );
sesamo_assert( 1 === count( array_keys( $codes, 'other_site_destination', true ) ), 'Cross-site destinations register one summary notice.' );
sesamo_assert( count( $codes ) === count( array_unique( $codes ) ), 'Repeated sanitize passes register each notice once.' );
$summary = $GLOBALS['sesamo_test_errors'][ array_search( 'other_site_destination', $codes, true ) ]['message'];
sesamo_assert( false !== strpos( $summary, 'example.test' ), 'The summary names the site Sesamo can open.' );
sesamo_assert( false !== strpos( $summary, 'redirect' ), 'The summary explains the redirect route.' );

$rejected = $GLOBALS['sesamo_transients']['netmilk_sesamo_rejected_7'] ?? null;
sesamo_assert( is_array( $rejected ), 'Rejected destinations are kept for the next screen.' );
sesamo_assert( 'https://pharma.example/iddqd/' === $rejected['preset']['iddqd'], 'An enabled cross-site preset keeps its typed value.' );
sesamo_assert( 'https://pharma.example/idkfa/' === $rejected['preset']['idkfa'], 'A disabled preset keeps its typed value too.' );
sesamo_assert( '' === $rejected['preset']['konami'], 'An enabled preset without a destination is flagged.' );
sesamo_assert( 'https://pharma.example/vault/' === $rejected['custom'][ $first_pass['custom_combinations'][0]['id'] ], 'A custom row keeps its typed value under its stable ID.' );

$hint = Settings::destination_message( 'other_site', 'https://pharma.example/iddqd/', '/iddqd/' );
sesamo_assert( false !== strpos( $hint, '/iddqd/' ) && false !== strpos( $hint, 'https://pharma.example/iddqd/' ) && false !== strpos( $hint, 'example.test' ), 'The field hint gives the exact path and redirect target.' );
sesamo_assert( '' !== Settings::destination_message( 'empty', '', '/secret/' ), 'Empty destinations get a hint.' );
sesamo_assert( '' !== Settings::destination_message( 'invalid', 'secret', '/secret/' ), 'Unusable destinations get a hint.' );

$GLOBALS['sesamo_transients'] = array();
Settings::sanitize( array( 'enabled_presets' => array( 'konami' ), 'preset_destinations' => array( 'konami' => '/konami/' ) ) );
sesamo_assert( ! isset( $GLOBALS['sesamo_transients']['netmilk_sesamo_rejected_7'] ), 'A clean save keeps nothing.' );
sesamo_assert( array() === Settings::public_combinations( Settings::normalize( array( 'schema_version' => 2, 'enabled_presets' => array( 'konami' ), 'preset_destinations' => array( 'konami' => 'https://example.test/' . str_repeat( 'x', 3000 ) ) ) ) ), 'Oversized destinations fail closed.' );

$malformed = Settings::normalize(
	array(
		'enabled_presets'     => 'konami',
		'preset_destinations' => 'https://example.test/',
		'custom_combinations' => array( new stdClass(), array( 'sequence' => array( array( 'nested' ) ) ) ),
		'max_pause'           => array( 1500 ),
	)
);
sesamo_assert( array() === $malformed['enabled_presets'], 'Malformed preset data cannot escape normalization.' );
sesamo_assert( array() === $malformed['custom_combinations'], 'Malformed custom data cannot escape normalization.' );
sesamo_assert( 1500 === $malformed['max_pause'], 'Malformed timeout data falls back safely.' );

$many = array();
for ( $index = 0; $index < 25; $index++ ) {
	$many[] = array(
		'name'            => 'Combo ' . $index,
		'sequence'        => 'x ' . ( $index % 10 ),
		'destination_url' => '/combo-' . $index . '/',
	);
}
$bounded = Settings::normalize( array( 'custom_combinations' => $many ) );
sesamo_assert( 20 === count( $bounded['custom_combinations'] ), 'Stored custom combinations are hard-capped.' );

$GLOBALS['sesamo_home_url'] = 'https://user:password@example.test';
$unsafe_home = Settings::normalize( array() );
sesamo_assert( '' === $unsafe_home['destination_url'], 'Credential-bearing home URLs fail closed.' );
sesamo_assert( array() === Settings::public_combinations( $unsafe_home ), 'Unsafe canonical homes never enqueue a detector.' );

echo "PHP smoke tests passed.\n";
