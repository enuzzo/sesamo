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

function absint( $value ) {
	return abs( (int) $value );
}

require_once dirname( __DIR__ ) . '/includes/class-presets.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';

use NetmilkStudio\Sesamo\Presets;
use NetmilkStudio\Sesamo\Settings;

$GLOBALS['sesamo_home_url'] = 'https://example.test';

function sesamo_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

sesamo_assert( 10 === count( Presets::all() ), 'The registry exposes ten presets.' );
sesamo_assert( array( 'konami', 'iddqd' ) === Presets::default_ids(), 'Default preset IDs stay stable.' );

$sanitized = Settings::sanitize(
	array(
		'enabled_presets' => array( 'iddqd', 'unknown', 'konami', 'iddqd', array( 'nested' ) ),
		'destination_url' => '/custom-easter-egg/',
		'max_pause'       => 9999,
	)
);

sesamo_assert( array( 'konami', 'iddqd' ) === $sanitized['enabled_presets'], 'Preset IDs are allowlisted and canonicalised.' );
sesamo_assert( 'https://example.test/custom-easter-egg/' === $sanitized['destination_url'], 'Relative destinations become site URLs.' );
sesamo_assert( 5000 === $sanitized['max_pause'], 'Timeout is clamped to the maximum.' );

$external = Settings::sanitize(
	array(
		'enabled_presets' => array(),
		'destination_url' => 'https://evil.example/phish',
		'max_pause'       => 1,
	)
);

sesamo_assert( 'https://example.test/iddqd/' === $external['destination_url'], 'Cross-origin destinations fall back safely.' );
sesamo_assert( 250 === $external['max_pause'], 'Timeout is clamped to the minimum.' );

$malformed = Settings::normalize(
	array(
		'enabled_presets' => 'konami',
		'destination_url' => array( 'https://example.test/' ),
		'max_pause'       => array( 1500 ),
	)
);

sesamo_assert( array() === $malformed['enabled_presets'], 'Malformed preset data cannot escape normalization.' );
sesamo_assert( 'https://example.test/iddqd/' === $malformed['destination_url'], 'Malformed URLs cannot reach the browser.' );
sesamo_assert( 1500 === $malformed['max_pause'], 'Malformed timeout data falls back safely.' );
sesamo_assert( 3 === count( $GLOBALS['sesamo_test_errors'] ), 'Invalid values register actionable settings warnings.' );

$GLOBALS['sesamo_home_url'] = 'https://user:password@example.test';
$unsafe_home = Settings::normalize( array() );
sesamo_assert( '' === $unsafe_home['destination_url'], 'Credential-bearing home URLs fail closed instead of reaching public config.' );
$GLOBALS['sesamo_home_url'] = 'https://example.test';

echo "PHP smoke tests passed.\n";
