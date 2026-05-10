<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Page_Protection {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
		add_action( 'template_redirect', array( $this, 'check_page_protection' ) );
	}

	public function add_meta_box() {
		add_meta_box(
			'hcal_page_protection',
			'Hipnolink Access Protection',
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'hcal_save_meta_box', 'hcal_meta_box_nonce' );
		$value = get_post_meta( $post->ID, '_hcal_require_login', true );
		?>
		<p>
			<label>
				<input type="checkbox" name="hcal_require_login" value="1" <?php checked( $value, '1' ); ?> />
				Require Hipnolink Access Login
			</label>
		</p>
		<p class="description">Halaman ini hanya bisa diakses oleh user yang sudah login melalui sistem Hipnolink Access.</p>
		<?php
	}

	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['hcal_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['hcal_meta_box_nonce'] ), 'hcal_save_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['hcal_require_login'] ) ) {
			update_post_meta( $post_id, '_hcal_require_login', '1' );
		} else {
			delete_post_meta( $post_id, '_hcal_require_login' );
		}
	}

	public function check_page_protection() {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		// Bypass cache if user has login cookie (for dynamic content integration)
		if ( isset( $_COOKIE['hcal_access_token'] ) && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( is_page() ) {
			$post_id = get_queried_object_id();
			$require_login = get_post_meta( $post_id, '_hcal_require_login', true );

			if ( $require_login === '1' ) {
				// Never cache protected pages
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', true );
				}

				if ( ! HCAL_Auth::is_user_logged_in() ) {
					$login_url = HCAL_Helpers::get_setting( 'login_page_url', home_url() );
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}
	}
}
