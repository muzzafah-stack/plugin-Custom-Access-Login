<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Shortcodes {

	public function __construct() {
		add_shortcode( 'hipnolink_access_login', array( $this, 'login_shortcode' ) );
		add_shortcode( 'hipnolink_access_logout', array( $this, 'logout_shortcode' ) );
		add_shortcode( 'hipnolink_access_protected', array( $this, 'protected_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

    public function enqueue_styles() {
        if ( HCAL_Helpers::get_setting( 'enable_basic_styling', 'yes' ) === 'yes' ) {
            wp_register_style( 'hcal-public-style', HCAL_PLUGIN_URL . 'assets/css/public.css', array(), HCAL_VERSION );
        }
    }

	public function login_shortcode( $atts ) {
		if ( HCAL_Helpers::get_setting( 'enable_basic_styling', 'yes' ) === 'yes' ) {
			wp_enqueue_style( 'hcal-public-style' );
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( HCAL_Auth::is_user_logged_in() ) {
            $logout_url = add_query_arg( 'hcal_action', 'logout' );
            $logout_url = wp_nonce_url( $logout_url, 'hcal_logout_nonce' );
			return '<div class="hcal-message hcal-success">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span>Anda sudah login ke sistem.</span>
                    <a href="' . esc_url( $logout_url ) . '" class="hcal-logout-link" style="margin-left: 15px; text-decoration: none;">Logout</a>
                </div>
            </div>';
		}

		$error_message = '';

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['hcal_login_submit'] ) ) {
			if ( ! isset( $_POST['hcal_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['hcal_login_nonce'] ), 'hcal_login_action' ) ) {
				$error_message = 'Keamanan validasi gagal. Silakan coba lagi.';
			} else {
				$username = isset( $_POST['hcal_username'] ) ? sanitize_text_field( $_POST['hcal_username'] ) : '';
				$password = isset( $_POST['hcal_password'] ) ? $_POST['hcal_password'] : '';
                $remember = isset( $_POST['hcal_remember'] ) ? true : false;
				$ip = $_SERVER['REMOTE_ADDR'];

				if ( empty( $username ) || empty( $password ) ) {
					$error_message = 'Username dan Password wajib diisi.';
				} else if ( ! HCAL_Auth::check_login_limit( $username, $ip ) ) {
                    $lockout_duration = HCAL_Helpers::get_setting( 'lockout_duration', 15 );
					$error_message = 'Login dikunci sementara karena terlalu banyak percobaan gagal. Silakan coba lagi setelah ' . esc_html( $lockout_duration ) . ' menit.';
				} else {
					$login = HCAL_Auth::login( $username, $password, $remember );

					if ( is_wp_error( $login ) ) {
						HCAL_Auth::record_failed_login( $username, $ip );
						$error_message = $login->get_error_message();
					} else {
						HCAL_Auth::clear_failed_login( $username, $ip );
						
						$redirect_url = $login->redirect_url;
						if ( empty( $redirect_url ) ) {
							$redirect_url = HCAL_Helpers::get_setting( 'default_redirect_url', home_url() );
						}
						wp_safe_redirect( $redirect_url );
						exit;
					}
				}
			}
		}

		ob_start();
		?>
		<div class="hcal-login-wrapper">
			<div class="hcal-login-header" style="text-align: center; margin-bottom: 24px;">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--hcal-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
				<h3 style="margin: 0; font-size: 20px; font-weight: 600; color: var(--hcal-text);">Secure Login</h3>
				<p style="margin: 6px 0 0; font-size: 14px; color: var(--hcal-text-muted);">Silakan masuk ke akun Anda</p>
			</div>
			<?php if ( $error_message ) : ?>
				<div class="hcal-message hcal-error"><?php echo esc_html( $error_message ); ?></div>
			<?php endif; ?>
			<form method="post" action="" class="hcal-login-form">
				<?php wp_nonce_field( 'hcal_login_action', 'hcal_login_nonce' ); ?>
				<div class="hcal-form-group">
					<label for="hcal_username">Username</label>
					<input type="text" name="hcal_username" id="hcal_username" required>
				</div>
				<div class="hcal-form-group">
					<label for="hcal_password">Password</label>
					<input type="password" name="hcal_password" id="hcal_password" required>
				</div>
                <div class="hcal-form-group hcal-remember-me">
                    <label>
                        <input type="checkbox" name="hcal_remember" value="1"> Remember me
                    </label>
                </div>
				<div class="hcal-form-group">
					<button type="submit" name="hcal_login_submit" class="hcal-btn">Login</button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function logout_shortcode( $atts ) {
        if ( isset( $_GET['hcal_action'] ) && $_GET['hcal_action'] === 'logout' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'hcal_logout_nonce' ) ) {
            HCAL_Auth::logout();
            $login_url = HCAL_Helpers::get_setting( 'login_page_url', home_url() );
            wp_safe_redirect( $login_url );
            exit;
        }

        if ( ! HCAL_Auth::is_user_logged_in() ) {
            return '';
        }

        $logout_url = add_query_arg( 'hcal_action', 'logout' );
        $logout_url = wp_nonce_url( $logout_url, 'hcal_logout_nonce' );

		return '<a href="' . esc_url( $logout_url ) . '" class="hcal-logout-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>';
	}

	public function protected_shortcode( $atts, $content = null ) {
		if ( HCAL_Helpers::get_setting( 'enable_basic_styling', 'yes' ) === 'yes' ) {
			wp_enqueue_style( 'hcal-public-style' );
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( HCAL_Auth::is_user_logged_in() ) {
			return do_shortcode( $content );
		}

		return '<div class="hcal-message hcal-warning"><strong>Akses Dibatasi</strong><br>Silakan login terlebih dahulu untuk mengakses konten ini.</div>';
	}
}
