<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'hcal_settings' );

// Hapus transient sessions
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hcal_session_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hcal_session_%'" );

// Hapus data (table) hanya jika defined
if ( defined( 'HCAL_REMOVE_DATA_ON_UNINSTALL' ) && HCAL_REMOVE_DATA_ON_UNINSTALL ) {
	$table_name = $wpdb->prefix . 'hipnolink_access_users';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}
