<?php
/**
 * Plugin Name: Hipnolink Custom Access Login
 * Plugin URI:  https://www.hipnolink.com
 * Description: Sistem login halaman custom untuk WordPress dengan user mandiri, session khusus, dan redirect berbeda untuk setiap user. Dibuat oleh Hipnolink Team.
 * Version:     1.0.0
 * Author:      Hipnolink Team
 * Author URI:  https://www.hipnolink.com
 * Text Domain: hipnolink-custom-access-login
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HCAL_VERSION', '1.0.0' );
define( 'HCAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HCAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_hipnolink_custom_access_login() {
	require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-activator.php';
	HCAL_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_hipnolink_custom_access_login' );

/**
 * Include core files.
 */
require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-helpers.php';
require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-auth.php';
require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-shortcodes.php';
require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-page-protection.php';

if ( is_admin() ) {
	require_once HCAL_PLUGIN_DIR . 'includes/class-hcal-admin.php';
	new HCAL_Admin();
}

/**
 * Initialize plugin
 */
function hcal_init() {
	new HCAL_Shortcodes();
	new HCAL_Page_Protection();
}
add_action( 'plugins_loaded', 'hcal_init' );
