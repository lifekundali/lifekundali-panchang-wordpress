<?php
/**
 * Removes the plugin's one stored option when the plugin is deleted.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'lk_panchang_options' );
