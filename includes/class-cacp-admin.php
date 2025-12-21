<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CACP_Admin {

    public function init() {
        // Change: Using 'admin_menu' with a top-level menu for better visibility
        add_action( 'admin_menu', array( $this, 'add_plugin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // User Profile Fields
        add_action( 'show_user_profile', array( $this, 'add_user_label_field' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_label_field' ) );
        add_action( 'personal_options_update', array( $this, 'save_user_label_field' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_label_field' ) );
    }

    public function add_plugin_menu() {
        // This adds a dedicated "Comment Suite" item to the main sidebar
        add_menu_page(
            'Comment Avatar & CAPTCHA', // Page Title
            'Comment Suite',            // Menu Title
            'manage_options',           // Capability
            'cacp-settings',            // Menu Slug
            array( $this, 'render_settings_page' ), // Callback
            'dashicons-admin-comments', // Icon
            25                          // Position
        );
    }

    public function register_settings() {
        // Registering our settings group
        register_setting( 'cacp_options_group', 'cacp_captcha_provider' );
        register_setting( 'cacp_options_group', 'cacp_site_key' );
        register_setting( 'cacp_options_group', 'cacp_secret_key' );
        register_setting( 'cacp_options_group', 'cacp_enable_bio' );
        register_setting( 'cacp_options_group', 'cacp_enable_count' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Comment Avatar & CAPTCHA Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'cacp_options_group' ); ?>
                <?php do_settings_sections( 'cacp_options_group' ); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Security Protection</th>
                        <td>
                            <select name="cacp_captcha_provider">
                                <option value="none" <?php selected( get_option('cacp_captcha_provider'), 'none' ); ?>>Disabled</option>
                                <option value="google" <?php selected( get_option('cacp_captcha_provider'), 'google' ); ?>>Google reCAPTCHA v2</option>
                                <option value="cloudflare" <?php selected( get_option('cacp_captcha_provider'), 'cloudflare' ); ?>>Cloudflare Turnstile</option>
                                <option value="hcaptcha" <?php selected( get_option('cacp_captcha_provider'), 'hcaptcha' ); ?>>hCaptcha</option>
                                <option value="math" <?php selected( get_option('cacp_captcha_provider'), 'math' ); ?>>Simple Math (Free)</option>
                            </select>
                            <p class="description">Select a CAPTCHA provider to block spam.</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Site Key</th>
                        <td><input type="text" name="cacp_site_key" value="<?php echo esc_attr( get_option('cacp_site_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Secret Key</th>
                        <td><input type="password" name="cacp_secret_key" value="<?php echo esc_attr( get_option('cacp_secret_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <hr>
                    <tr valign="top">
                        <th scope="row">Identity Features</th>
                        <td>
                            <label><input type="checkbox" name="cacp_enable_bio" value="1" <?php checked( get_option('cacp_enable_bio'), 1 ); ?> /> Enable User Bio (8 words max)</label><br>
                            <label><input type="checkbox" name="cacp_enable_count" value="1" <?php checked( get_option('cacp_enable_count'), 1 ); ?> /> Allow Users to Show Comment Count</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    // --- User Labels Logic ---
    public function add_user_label_field( $user ) {
        ?>
        <h3>Comment Suite Badges</h3>
        <table class="form-table">
            <tr>
                <th><label for="cacp_user_label">Badge Label</label></th>
                <td>
                    <input type="text" name="cacp_user_label" id="cacp_user_label" value="<?php echo esc_attr( get_the_author_meta( 'cacp_user_label', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description">e.g., "Verified User", "Team", "Expert". Appears next to their name.</span>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_user_label_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return false;
        update_user_meta( $user_id, 'cacp_user_label', sanitize_text_field( $_POST['cacp_user_label'] ) );
    }
}
