<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Auth {

	public static function login( $username, $password, $remember = false ) {
		$user = HCAL_Helpers::get_user_by_username( $username );

		if ( ! $user ) {
			return new WP_Error( 'invalid_username', 'Username tidak ditemukan.' );
		}

		if ( $user->status !== 'active' ) {
			return new WP_Error( 'account_inactive', 'Akun Anda tidak aktif. Silakan hubungi administrator.' );
		}

		if ( ! password_verify( $password, $user->password_hash ) ) {
			return new WP_Error( 'invalid_password', 'Password salah.' );
		}

		// Success
		self::create_session( $user, $remember );
        
        // Update user stats
        global $wpdb;
        $table_name = HCAL_Helpers::get_table_name();
        $wpdb->query( $wpdb->prepare( "UPDATE $table_name SET last_login = %s, login_count = login_count + 1 WHERE id = %d", current_time('mysql'), $user->id ) );

		return $user;
	}

	private static function create_session( $user, $remember ) {
		$token = wp_generate_password( 64, false );
		$session_lifetime = HCAL_Helpers::get_setting( 'session_lifetime', 8 );
		
        if ( $remember ) {
            $expiration = time() + ( 14 * DAY_IN_SECONDS ); // 14 days if remember me
        } else {
            $expiration = time() + ( $session_lifetime * HOUR_IN_SECONDS );
        }

		$session_data = array(
			'user_id'    => $user->id,
			'username'   => $user->username,
			'created_at' => time(),
			'expires_at' => $expiration,
		);

		set_transient( 'hcal_session_' . $token, $session_data, $expiration - time() );

		$secure = is_ssl();
		setcookie( 'hcal_access_token', $token, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
	}

	public static function logout() {
		if ( isset( $_COOKIE['hcal_access_token'] ) ) {
			$token = sanitize_text_field( $_COOKIE['hcal_access_token'] );
			delete_transient( 'hcal_session_' . $token );
			setcookie( 'hcal_access_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	public static function get_current_user() {
		if ( ! isset( $_COOKIE['hcal_access_token'] ) ) {
			return false;
		}

		$token = sanitize_text_field( $_COOKIE['hcal_access_token'] );
		$session_data = get_transient( 'hcal_session_' . $token );

		if ( ! $session_data || ! isset( $session_data['user_id'] ) ) {
			return false;
		}

		$user = HCAL_Helpers::get_user( $session_data['user_id'] );

		if ( ! $user || $user->status !== 'active' ) {
			return false;
		}

		return $user;
	}

	public static function is_user_logged_in() {
		return self::get_current_user() !== false;
	}

	public static function check_login_limit( $username, $ip ) {
		if ( HCAL_Helpers::get_setting( 'enable_login_limit', 'yes' ) !== 'yes' ) {
			return true; // Not limited
		}

		$max_attempts = (int) HCAL_Helpers::get_setting( 'max_login_attempts', 5 );
		$transient_key = 'hcal_login_attempts_' . md5( $username . '_' . $ip );
		$attempts = get_transient( $transient_key );

		if ( $attempts && $attempts >= $max_attempts ) {
			return false; // Locked out
		}

		return true;
	}

	public static function record_failed_login( $username, $ip ) {
		if ( HCAL_Helpers::get_setting( 'enable_login_limit', 'yes' ) !== 'yes' ) {
			return;
		}

		$lockout_duration = (int) HCAL_Helpers::get_setting( 'lockout_duration', 15 );
		$transient_key = 'hcal_login_attempts_' . md5( $username . '_' . $ip );
		
		$attempts = get_transient( $transient_key );
		if ( ! $attempts ) {
			$attempts = 0;
		}
		
		$attempts++;
		set_transient( $transient_key, $attempts, $lockout_duration * MINUTE_IN_SECONDS );
	}

	public static function clear_failed_login( $username, $ip ) {
		$transient_key = 'hcal_login_attempts_' . md5( $username . '_' . $ip );
		delete_transient( $transient_key );
	}
}
