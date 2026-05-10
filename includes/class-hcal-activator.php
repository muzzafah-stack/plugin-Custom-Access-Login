<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Activator {

	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hipnolink_access_users';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			username varchar(100) NOT NULL,
			password_hash varchar(255) NOT NULL,
			redirect_url text NOT NULL,
			status varchar(20) DEFAULT 'active' NOT NULL,
			last_login datetime DEFAULT NULL,
			login_count bigint(20) unsigned DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY username (username)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		// Set default options
		$default_options = array(
			'login_page_url'        => '',
			'default_redirect_url'  => home_url(),
			'session_lifetime'      => 8, // hours
			'enable_login_limit'    => 'yes',
			'max_login_attempts'    => 5,
			'lockout_duration'      => 15, // minutes
			'enable_basic_styling'  => 'yes',
		);

		add_option( 'hcal_settings', $default_options );
	}

}
