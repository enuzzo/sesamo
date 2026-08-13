<?php
/** Remove only options owned by Sesamo. */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'netmilk_sesamo_settings' );
delete_option( 'konami_code_activator_settings' );
