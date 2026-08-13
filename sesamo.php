<?php
/**
 * Sesamo — Secret Key Sequences.
 *
 * @package NetmilkStudio\Sesamo
 * @author  Netmilk Studio sagl
 * @license GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Sesamo — Secret Key Sequences
 * Plugin URI:        https://github.com/enuzzo/sesamo
 * Description:       Unlock a hidden WordPress page with the Konami Code, IDDQD, or another classic secret sequence.
 * Version:           0.1.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Netmilk Studio sagl
 * Author URI:        https://netmilk.ch
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sesamo
 * Domain Path:       /languages
 */

namespace NetmilkStudio\Sesamo;

defined( 'ABSPATH' ) || exit;

define( 'NETMILK_SESAMO_VERSION', '0.1.0' );
define( 'NETMILK_SESAMO_FILE', __FILE__ );
define( 'NETMILK_SESAMO_DIR', plugin_dir_path( __FILE__ ) );
define( 'NETMILK_SESAMO_URL', plugin_dir_url( __FILE__ ) );

require_once NETMILK_SESAMO_DIR . 'includes/class-presets.php';
require_once NETMILK_SESAMO_DIR . 'includes/class-settings.php';
require_once NETMILK_SESAMO_DIR . 'includes/class-frontend.php';

register_activation_hook(
	__FILE__,
	static function (): void {
		Settings::activate();
	}
);

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'sesamo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Settings::init();
		Frontend::init();
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	static function ( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=sesamo' ) ),
			esc_html__( 'Settings', 'sesamo' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}
);
