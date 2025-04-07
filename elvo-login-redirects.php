<?php
/**
 * Plugin Name: ELVO Login Redirects
 * Description: Redirects the default login page to a custom page. Allows admin configuration.
 * Version: 1.2.0
 * Author: ELVO Web Studio
 * Author URI: https://www.elvoweb.com
 * Plugin URI: https://www.elvoweb.com
 * Text Domain: elvo-login-redirects
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // No direct access allowed.
}

class ELVO_LoginRedirects {

    private static $instance = null;
    private $option_key = 'elvo_login_redirects_settings';

    public static function get_instance() {
        if ( self::$instance == null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'login_init', [ $this, 'maybe_redirect_login' ] );
        add_action( 'init', [ $this, 'handle_post_login_redirect' ] );
        add_shortcode( 'elvo_login_form', [ $this, 'render_login_form_shortcode' ] );
        add_action( 'admin_notices', [ $this, 'maybe_show_admin_notice' ] );
    }

    public function register_admin_page() {
        add_options_page(
            'ELVO Login Redirects',
            'ELVO Login Redirects',
            'manage_options',
            'elvo-login-redirects',
            [ $this, 'settings_page_html' ]
        );
    }

    public function register_settings() {
        register_setting( 'elvo_login_redirects_group', $this->option_key );
    }

    public function settings_page_html() {
        $options = get_option( $this->option_key );
        ?>
        <div class="wrap">
            <h1>ELVO Login Redirects</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'elvo_login_redirects_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Redirection</th>
                        <td><input type="checkbox" name="<?php echo $this->option_key; ?>[enabled]" value="1" <?php checked( 1, $options['enabled'] ?? 0 ); ?> /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Custom Login Page URL</th>
                        <td><input type="text" placeholder="https://yourdomain.com/login" name="<?php echo $this->option_key; ?>[login_page]" value="<?php echo esc_attr( $options['login_page'] ?? '' ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Redirect After Login (URL)</th>
                        <td><input type="text" placeholder="https://yourdomain.com/redirection-page" name="<?php echo $this->option_key; ?>[after_login]" value="<?php echo esc_attr( $options['after_login'] ?? '' ); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function maybe_redirect_login() {
        $options = get_option( $this->option_key );
    
        if ( isset( $options['enabled'] ) && $options['enabled'] ) {
            $requested_page = $_SERVER['REQUEST_URI'];
    
            // Only redirect default login page
            if ( strpos( $requested_page, 'wp-login.php' ) !== false && ! isset( $_GET['action'] ) ) {
                $target_url = ! empty( $options['login_page'] ) ? $options['login_page'] : home_url();
                wp_redirect( esc_url( $target_url ) );
                exit;
            }
        }
    }
    

    public function handle_post_login_redirect() {
        $options = get_option( $this->option_key );
        if ( isset( $options['enabled'] ) && $options['enabled'] && ! empty( $options['after_login'] ) ) {
            add_filter( 'login_redirect', function( $redirect_to, $request, $user ) use ( $options ) {
                return esc_url( $options['after_login'] );
            }, 10, 3 );
        }
    }

    public function maybe_show_admin_notice() {
        $options = get_option( $this->option_key );
        if ( isset( $options['enabled'] ) && $options['enabled'] ) {
            $page_url = $options['login_page'] ?? '';
            if ( ! empty( $page_url ) && ! url_to_postid( $page_url ) ) {
                echo '<div class="notice notice-error"><p><strong>ELVO Login Redirects:</strong> The custom login page URL does not exist. Please update it in the plugin settings.</p></div>';
            }
        }
    }

    public function render_login_form_shortcode() {
        if ( is_user_logged_in() ) {
            return '<p>You are already logged in.</p>';
        }

        ob_start();
        wp_login_form();
        return ob_get_clean();
    }
}

require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/raihan73/elvo-login-redirects/',
    __FILE__,
    'elvo-login-redirects'
);

// Optional: If you're using a different branch (like main instead of master)
$updateChecker->setBranch('main');



ELVO_LoginRedirects::get_instance();
