<?php
/*
Plugin Name: WP Login Redirect & Security
Description: Redirects the login page and allows custom login redirects.
Version: 1.2
Author: Raihan | ELVO Web Studio
Author URI: https://www.elvoweb.com
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WPLR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPLR_PLUGIN_URL', plugin_dir_url(__FILE__));

define('WPLR_OPTION_NAME', 'wplr_settings');

take_plugin_action();
function take_plugin_action(){
    register_activation_hook(__FILE__, 'wplr_activate');
    register_deactivation_hook(__FILE__, 'wplr_deactivate');
    add_action('admin_menu', 'wplr_create_menu');
    add_action('init', 'wplr_redirect_login_page');
}

// Plugin activation: Set default options
function wplr_activate() {
    $default_settings = array(
        'enable_redirect' => false,
        'custom_login_url' => 'my-login',
        'redirect_after_login' => home_url()
    );
    if (!get_option(WPLR_OPTION_NAME)) {
        update_option(WPLR_OPTION_NAME, $default_settings);
    }
}

// Plugin deactivation: Cleanup settings
function wplr_deactivate() {
    delete_option(WPLR_OPTION_NAME);
}

// Create admin menu
function wplr_create_menu() {
    add_menu_page('Login Redirect Settings', 'Login Redirect', 'manage_options', 'wplr-settings', 'wplr_settings_page', 'dashicons-lock');
}

// Admin settings page
function wplr_settings_page() {
    $settings = get_option(WPLR_OPTION_NAME);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        check_admin_referer('wplr_settings_nonce');
        $settings['enable_redirect'] = isset($_POST['enable_redirect']) ? true : false;
        $settings['custom_login_url'] = sanitize_text_field($_POST['custom_login_url']);
        $settings['redirect_after_login'] = esc_url_raw($_POST['redirect_after_login']);
        update_option(WPLR_OPTION_NAME, $settings);
        echo '<div class="updated"><p>Settings updated.</p></div>';
    }
    ?>
    <div class="wrap">
        <h2><span class="dashicons dashicons-lock"></span> Login Redirect & Security - By ELVO Web Studio</h2>
        <form method="post" style="max-width: 500px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <?php wp_nonce_field('wplr_settings_nonce'); ?>
            <label><strong>Enable Custom Login URL:</strong></label>
            <input type="checkbox" name="enable_redirect" value="1" <?php checked($settings['enable_redirect'], true); ?>>
            <br><br>
            <label><strong>Custom Login URL:</strong></label>
            <input type="text" name="custom_login_url" value="<?php echo esc_attr($settings['custom_login_url']); ?>" class="regular-text">
            <br><br>
            <label><strong>Redirect After Login:</strong></label>
            <input type="text" name="redirect_after_login" value="<?php echo esc_url($settings['redirect_after_login']); ?>" class="regular-text">
            <br><br>
            <input type="submit" value="Save Settings" class="button button-primary">
        </form>
        <p style="margin-top: 20px; font-size: 14px; color: #666;">Developed by <a href="https://www.elvoweb.com" target="_blank">ELVO Web Studio</a></p>
    </div>
    <?php
}

// Redirect login page, but exclude logout requests
function wplr_redirect_login_page() {
    $settings = get_option(WPLR_OPTION_NAME);
    
    // Check if redirection is enabled
    if ($settings['enable_redirect']) {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Allow logout to work properly
        if (strpos($request_uri, 'wp-login.php?action=logout') !== false) {
            return;
        }
        
        // Redirect login page
        if (strpos($request_uri, 'wp-login.php') !== false) {
            wp_redirect(home_url('/' . $settings['custom_login_url']));
            exit;
        }
    }
}
?>
