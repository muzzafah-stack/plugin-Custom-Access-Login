<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Helpers {

	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'hipnolink_access_users';
	}

	public static function get_setting( $key, $default = '' ) {
		$settings = get_option( 'hcal_settings', array() );
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function get_user( $id ) {
		global $wpdb;
		$cache_key = 'hcal_user_' . $id;
		$user = wp_cache_get( $cache_key, 'hcal_users' );
		if ( false === $user ) {
			$table_name = self::get_table_name();
			$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
			if ( $user ) {
				wp_cache_set( $cache_key, $user, 'hcal_users', 3600 );
			}
		}
		return $user;
	}

	public static function get_user_by_username( $username ) {
		global $wpdb;
		$cache_key = 'hcal_user_name_' . md5( $username );
		$user = wp_cache_get( $cache_key, 'hcal_users' );
		if ( false === $user ) {
			$table_name = self::get_table_name();
			$user = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE username = %s", $username ) );
			if ( $user ) {
				wp_cache_set( $cache_key, $user, 'hcal_users', 3600 );
			}
		}
		return $user;
	}
    
    public static function get_users() {
        global $wpdb;
		$table_name = self::get_table_name();
		return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
    }

	public static function insert_user( $data ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$data['created_at'] = current_time( 'mysql' );
		$data['updated_at'] = current_time( 'mysql' );
		$wpdb->insert( $table_name, $data );
		return $wpdb->insert_id;
	}

	public static function update_user( $id, $data ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$data['updated_at'] = current_time( 'mysql' );
		
		$user = self::get_user( $id );
		if ( $user ) {
			wp_cache_delete( 'hcal_user_name_' . md5( $user->username ), 'hcal_users' );
		}
		wp_cache_delete( 'hcal_user_' . $id, 'hcal_users' );
		
		return $wpdb->update( $table_name, $data, array( 'id' => $id ) );
	}

	public static function delete_user( $id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		
		$user = self::get_user( $id );
		if ( $user ) {
			wp_cache_delete( 'hcal_user_name_' . md5( $user->username ), 'hcal_users' );
		}
		wp_cache_delete( 'hcal_user_' . $id, 'hcal_users' );
		
		return $wpdb->delete( $table_name, array( 'id' => $id ) );
	}
}
