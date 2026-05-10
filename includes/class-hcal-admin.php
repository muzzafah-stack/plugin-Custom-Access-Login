<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HCAL_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'hcal-' ) !== false || $hook === 'toplevel_page_hcal-users' ) {
            wp_enqueue_style( 'hcal-admin-style', HCAL_PLUGIN_URL . 'assets/css/admin.css', array(), HCAL_VERSION );
        }
    }

	public function add_admin_menu() {
		add_menu_page(
			'Hipnolink Access',
			'Hipnolink Access',
			'manage_options',
			'hcal-users',
			array( $this, 'render_users_page' ),
			'dashicons-admin-users',
			30
		);

		add_submenu_page(
			'hcal-users',
			'Users',
			'Users',
			'manage_options',
			'hcal-users',
			array( $this, 'render_users_page' )
		);

		add_submenu_page(
			'hcal-users',
			'Add New User',
			'Add New User',
			'manage_options',
			'hcal-add-user',
			array( $this, 'render_add_user_page' )
		);

		add_submenu_page(
			'hcal-users',
			'Settings',
			'Settings',
			'manage_options',
			'hcal-settings',
			array( $this, 'render_settings_page' )
		);
        
        // Hidden submenu for editing
        add_submenu_page(
            null,
            'Edit User',
            'Edit User',
            'manage_options',
            'hcal-edit-user',
            array( $this, 'render_edit_user_page' )
        );
	}

	public function handle_form_submissions() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle Save Settings
		if ( isset( $_POST['hcal_action'] ) && $_POST['hcal_action'] === 'save_settings' ) {
			check_admin_referer( 'hcal_save_settings', 'hcal_settings_nonce' );

			$settings = array(
				'login_page_url'       => esc_url_raw( $_POST['login_page_url'] ),
				'default_redirect_url' => esc_url_raw( $_POST['default_redirect_url'] ),
				'session_lifetime'     => absint( $_POST['session_lifetime'] ),
				'enable_login_limit'   => sanitize_text_field( $_POST['enable_login_limit'] ),
				'max_login_attempts'   => absint( $_POST['max_login_attempts'] ),
				'lockout_duration'     => absint( $_POST['lockout_duration'] ),
				'enable_basic_styling' => sanitize_text_field( $_POST['enable_basic_styling'] ),
			);

			update_option( 'hcal_settings', $settings );
            wp_redirect( add_query_arg( array( 'page' => 'hcal-settings', 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
            exit;
		}

		// Handle Add User
		if ( isset( $_POST['hcal_action'] ) && $_POST['hcal_action'] === 'add_user' ) {
			check_admin_referer( 'hcal_add_user', 'hcal_add_user_nonce' );

			$username = sanitize_text_field( $_POST['username'] );
			$password = $_POST['password'];
			$confirm_password = $_POST['confirm_password'];
			$redirect_url = sanitize_text_field( $_POST['redirect_url'] ); // Keep relative path if needed
			$status = sanitize_text_field( $_POST['status'] );

			$errors = array();

			if ( empty( $username ) ) {
				$errors[] = 'Username wajib diisi.';
			} elseif ( HCAL_Helpers::get_user_by_username( $username ) ) {
				$errors[] = 'Username sudah digunakan.';
			}

			if ( empty( $password ) ) {
				$errors[] = 'Password wajib diisi.';
			} elseif ( strlen( $password ) < 8 ) {
				$errors[] = 'Password minimal 8 karakter.';
			} elseif ( $password !== $confirm_password ) {
				$errors[] = 'Konfirmasi password tidak sama.';
			}

			if ( empty( $redirect_url ) ) {
				$errors[] = 'Redirect URL wajib diisi.';
			}

			if ( empty( $errors ) ) {
				$data = array(
					'username'      => $username,
					'password_hash' => password_hash( $password, PASSWORD_DEFAULT ),
					'redirect_url'  => $redirect_url,
					'status'        => $status,
				);

				HCAL_Helpers::insert_user( $data );
				wp_redirect( add_query_arg( array( 'page' => 'hcal-users', 'added' => 'true' ), admin_url( 'admin.php' ) ) );
				exit;
			} else {
                set_transient( 'hcal_add_user_errors', $errors, 60 );
            }
		}

        // Handle Edit User
        if ( isset( $_POST['hcal_action'] ) && $_POST['hcal_action'] === 'edit_user' ) {
			check_admin_referer( 'hcal_edit_user', 'hcal_edit_user_nonce' );

            $id = absint( $_POST['user_id'] );
			$username = sanitize_text_field( $_POST['username'] );
			$password = $_POST['password'];
			$redirect_url = sanitize_text_field( $_POST['redirect_url'] );
			$status = sanitize_text_field( $_POST['status'] );

			$errors = array();

			if ( empty( $username ) ) {
				$errors[] = 'Username wajib diisi.';
			} else {
                $existing = HCAL_Helpers::get_user_by_username( $username );
                if ( $existing && $existing->id != $id ) {
                    $errors[] = 'Username sudah digunakan oleh user lain.';
                }
            }

			if ( ! empty( $password ) && strlen( $password ) < 8 ) {
				$errors[] = 'Password minimal 8 karakter.';
			}

			if ( empty( $redirect_url ) ) {
				$errors[] = 'Redirect URL wajib diisi.';
			}

			if ( empty( $errors ) ) {
				$data = array(
					'username'      => $username,
					'redirect_url'  => $redirect_url,
					'status'        => $status,
				);
                
                if ( ! empty( $password ) ) {
                    $data['password_hash'] = password_hash( $password, PASSWORD_DEFAULT );
                }

				HCAL_Helpers::update_user( $id, $data );
				wp_redirect( add_query_arg( array( 'page' => 'hcal-users', 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
				exit;
			} else {
                set_transient( 'hcal_edit_user_errors_' . $id, $errors, 60 );
            }
		}

        // Handle Delete User
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'delete_user_' . intval( $_GET['id'] ) ) ) {
                HCAL_Helpers::delete_user( intval( $_GET['id'] ) );
                wp_redirect( add_query_arg( array( 'page' => 'hcal-users', 'deleted' => 'true' ), admin_url( 'admin.php' ) ) );
				exit;
            }
        }
        
        // Handle Activate/Deactivate
        if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array('activate', 'deactivate') ) && isset( $_GET['id'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), $_GET['action'] . '_user_' . intval( $_GET['id'] ) ) ) {
                $status = $_GET['action'] === 'activate' ? 'active' : 'inactive';
                HCAL_Helpers::update_user( intval( $_GET['id'] ), array( 'status' => $status ) );
                wp_redirect( add_query_arg( array( 'page' => 'hcal-users', 'status_changed' => 'true' ), admin_url( 'admin.php' ) ) );
				exit;
            }
        }
	}

	public function render_users_page() {
		$users = HCAL_Helpers::get_users();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">Hipnolink Access Users</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hcal-add-user' ) ); ?>" class="page-title-action">Add New User</a>
            
            <?php if ( isset( $_GET['added'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>User berhasil ditambahkan.</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>User berhasil diperbarui.</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>User berhasil dihapus.</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['status_changed'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>Status user berhasil diubah.</p></div>
            <?php endif; ?>

			<table class="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<th>Username</th>
						<th>Redirect URL</th>
						<th>Status</th>
						<th>Last Login</th>
						<th>Login Count</th>
						<th>Created At</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $users ) ) : ?>
						<tr><td colspan="7">Belum ada user.</td></tr>
					<?php else : ?>
						<?php foreach ( $users as $user ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $user->username ); ?></strong></td>
								<td><?php echo esc_html( $user->redirect_url ); ?></td>
								<td>
                                    <?php if ( $user->status === 'active' ) : ?>
                                        <span class="hcal-badge hcal-badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="hcal-badge hcal-badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </td>
								<td><?php echo $user->last_login ? esc_html( $user->last_login ) : 'Belum pernah'; ?></td>
								<td><?php echo esc_html( $user->login_count ); ?></td>
								<td><?php echo esc_html( $user->created_at ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=hcal-edit-user&id=' . $user->id ) ); ?>">Edit</a> | 
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hcal-users&action=delete&id=' . $user->id ), 'delete_user_' . $user->id ) ); ?>" onclick="return confirm('Yakin ingin menghapus user ini?');" class="hcal-text-danger">Delete</a> | 
                                    <?php if ( $user->status === 'active' ) : ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hcal-users&action=deactivate&id=' . $user->id ), 'deactivate_user_' . $user->id ) ); ?>">Deactivate</a>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=hcal-users&action=activate&id=' . $user->id ), 'activate_user_' . $user->id ) ); ?>">Activate</a>
                                    <?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_add_user_page() {
        $errors = get_transient( 'hcal_add_user_errors' );
        if ( $errors !== false ) {
            delete_transient( 'hcal_add_user_errors' );
        }
		?>
		<div class="wrap">
			<h1>Add New User</h1>
            <?php if ( ! empty( $errors ) ) : ?>
                <div class="notice notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'hcal_add_user', 'hcal_add_user_nonce' ); ?>
				<input type="hidden" name="hcal_action" value="add_user">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="username">Username</label></th>
						<td><input name="username" type="text" id="username" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="password">Password</label></th>
						<td>
                            <input name="password" type="password" id="password" class="regular-text" required minlength="8">
                            <p class="description">Minimal 8 karakter.</p>
                        </td>
					</tr>
					<tr>
						<th scope="row"><label for="confirm_password">Confirm Password</label></th>
						<td><input name="confirm_password" type="password" id="confirm_password" class="regular-text" required minlength="8"></td>
					</tr>
					<tr>
						<th scope="row"><label for="redirect_url">Redirect URL</label></th>
						<td>
                            <input name="redirect_url" type="text" id="redirect_url" class="regular-text" required>
                            <p class="description">URL redirect setelah login sukses. Bisa full URL atau relative path seperti /client-area/.</p>
                        </td>
					</tr>
					<tr>
						<th scope="row"><label for="status">Status</label></th>
						<td>
							<select name="status" id="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Add User' ); ?>
			</form>
		</div>
		<?php
	}

    public function render_edit_user_page() {
        if ( ! isset( $_GET['id'] ) ) {
            wp_die( 'Invalid user ID' );
        }

        $id = absint( $_GET['id'] );
        $user = HCAL_Helpers::get_user( $id );

        if ( ! $user ) {
            wp_die( 'User not found' );
        }

        $errors = get_transient( 'hcal_edit_user_errors_' . $id );
        if ( $errors !== false ) {
            delete_transient( 'hcal_edit_user_errors_' . $id );
        }
		?>
		<div class="wrap">
			<h1>Edit User</h1>
            <?php if ( ! empty( $errors ) ) : ?>
                <div class="notice notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'hcal_edit_user', 'hcal_edit_user_nonce' ); ?>
				<input type="hidden" name="hcal_action" value="edit_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr( $id ); ?>">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="username">Username</label></th>
						<td><input name="username" type="text" id="username" class="regular-text" value="<?php echo esc_attr( $user->username ); ?>" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="password">New Password</label></th>
						<td>
                            <input name="password" type="password" id="password" class="regular-text" minlength="8">
                            <p class="description">Kosongkan jika tidak ingin mengubah password. Minimal 8 karakter.</p>
                        </td>
					</tr>
					<tr>
						<th scope="row"><label for="redirect_url">Redirect URL</label></th>
						<td>
                            <input name="redirect_url" type="text" id="redirect_url" class="regular-text" value="<?php echo esc_attr( $user->redirect_url ); ?>" required>
                        </td>
					</tr>
					<tr>
						<th scope="row"><label for="status">Status</label></th>
						<td>
							<select name="status" id="status">
								<option value="active" <?php selected( $user->status, 'active' ); ?>>Active</option>
								<option value="inactive" <?php selected( $user->status, 'inactive' ); ?>>Inactive</option>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Update User' ); ?>
			</form>
		</div>
		<?php
	}

	public function render_settings_page() {
		$settings = get_option( 'hcal_settings', array() );
		?>
		<div class="wrap">
			<h1>Hipnolink Access Settings</h1>
            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
            <?php endif; ?>
			<form method="post" action="">
				<?php wp_nonce_field( 'hcal_save_settings', 'hcal_settings_nonce' ); ?>
				<input type="hidden" name="hcal_action" value="save_settings">
				<table class="form-table">
					<tr>
						<th scope="row"><label for="login_page_url">Login Page URL</label></th>
						<td>
							<input name="login_page_url" type="url" id="login_page_url" class="regular-text" value="<?php echo esc_attr( isset( $settings['login_page_url'] ) ? $settings['login_page_url'] : '' ); ?>">
							<p class="description">URL halaman tempat Anda memasang shortcode [hipnolink_access_login].</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="default_redirect_url">Default Redirect URL</label></th>
						<td>
							<input name="default_redirect_url" type="text" id="default_redirect_url" class="regular-text" value="<?php echo esc_attr( isset( $settings['default_redirect_url'] ) ? $settings['default_redirect_url'] : home_url() ); ?>">
							<p class="description">URL default jika redirect user kosong.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="session_lifetime">Session Lifetime (Hours)</label></th>
						<td>
							<input name="session_lifetime" type="number" id="session_lifetime" value="<?php echo esc_attr( isset( $settings['session_lifetime'] ) ? $settings['session_lifetime'] : 8 ); ?>" min="1">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="enable_login_limit">Enable Login Attempt Limit</label></th>
						<td>
							<select name="enable_login_limit" id="enable_login_limit">
								<option value="yes" <?php selected( isset( $settings['enable_login_limit'] ) ? $settings['enable_login_limit'] : 'yes', 'yes' ); ?>>Yes</option>
								<option value="no" <?php selected( isset( $settings['enable_login_limit'] ) ? $settings['enable_login_limit'] : 'yes', 'no' ); ?>>No</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="max_login_attempts">Max Login Attempts</label></th>
						<td>
							<input name="max_login_attempts" type="number" id="max_login_attempts" value="<?php echo esc_attr( isset( $settings['max_login_attempts'] ) ? $settings['max_login_attempts'] : 5 ); ?>" min="1">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lockout_duration">Lockout Duration (Minutes)</label></th>
						<td>
							<input name="lockout_duration" type="number" id="lockout_duration" value="<?php echo esc_attr( isset( $settings['lockout_duration'] ) ? $settings['lockout_duration'] : 15 ); ?>" min="1">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="enable_basic_styling">Enable Basic Styling</label></th>
						<td>
							<select name="enable_basic_styling" id="enable_basic_styling">
								<option value="yes" <?php selected( isset( $settings['enable_basic_styling'] ) ? $settings['enable_basic_styling'] : 'yes', 'yes' ); ?>>Yes</option>
								<option value="no" <?php selected( isset( $settings['enable_basic_styling'] ) ? $settings['enable_basic_styling'] : 'yes', 'no' ); ?>>No</option>
							</select>
                            <p class="description">Muat CSS bawaan untuk form login di frontend.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings' ); ?>
			</form>
		</div>
		<?php
	}
}
