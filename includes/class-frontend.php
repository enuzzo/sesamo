<?php
/**
 * Frontend asset loading.
 *
 * @package NetmilkStudio\Sesamo
 */

namespace NetmilkStudio\Sesamo;

defined( 'ABSPATH' ) || exit;

final class Frontend {
	/** Register frontend hooks. */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/** Load the detector only when at least one valid sequence is enabled. */
	public static function enqueue(): void {
		$settings = Settings::get();
		$presets  = Presets::public_config( $settings['enabled_presets'] );

		if ( array() === $presets || '' === $settings['destination_url'] ) {
			return;
		}

		wp_enqueue_script(
			'netmilk-sesamo',
			NETMILK_SESAMO_URL . 'assets/js/sesamo.js',
			array(),
			NETMILK_SESAMO_VERSION,
			true
		);

		$config = array(
			'destinationUrl' => $settings['destination_url'],
			'maxPause'       => (int) $settings['max_pause'],
			'presets'        => $presets,
		);

		wp_add_inline_script(
			'netmilk-sesamo',
			'window.NETMILK_SESAMO_CONFIG = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}
}
