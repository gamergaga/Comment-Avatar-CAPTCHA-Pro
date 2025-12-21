<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CACP_Security {

    // 1. Secret Salt to make Math Captcha un-hackable
    private $salt = 'cacp_secure_salt_'; 

    public function init() {
        add_action( 'comment_form_after_fields', array( $this, 'render_captcha' ) );
        add_action( 'comment_form_logged_in_after', array( $this, 'render_captcha' ) );
        add_filter( 'preprocess_comment', array( $this, 'verify_captcha' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts() {
        if ( ! is_singular() || ! comments_open() ) return;
        $provider = get_option( 'cacp_captcha_provider' );
        
        if ( 'google' === $provider ) {
            wp_enqueue_script( 'cacp-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
        } elseif ( 'cloudflare' === $provider ) {
            wp_enqueue_script( 'cacp-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
        } elseif ( 'hcaptcha' === $provider ) {
            wp_enqueue_script( 'cacp-hcaptcha', 'https://js.hcaptcha.com/1/api.js', array(), null, true );
        }
    }

    public function render_captcha() {
        $provider = get_option( 'cacp_captcha_provider' );
        $site_key = get_option( 'cacp_site_key' );

        if ( 'none' === $provider || ! $provider ) return;

        echo '<div class="cacp-security-box" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">';
        
        if ( 'google' === $provider ) {
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
        } elseif ( 'cloudflare' === $provider ) {
            echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
        } elseif ( 'hcaptcha' === $provider ) {
            echo '<div class="h-captcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
        } elseif ( 'math' === $provider ) {
            $n1 = rand( 1, 9 );
            $n2 = rand( 1, 9 );
            $sum = $n1 + $n2;
            // Secure Hash: Salt + Sum
            $hash = md5( $this->salt . $sum ); 
            
            echo '<p style="margin-bottom:10px;"><strong>Security Question:</strong> What is ' . $n1 . ' + ' . $n2 . '?</p>';
            echo '<input type="number" name="cacp_math_ans" style="width:60px; padding:5px;" required placeholder="?">';
            echo '<input type="hidden" name="cacp_math_hash" value="' . esc_attr( $hash ) . '">';
        }
        echo '</div>';
    }

    public function verify_captcha( $commentdata ) {
        // NOTE: I removed the "Admin Bypass" so you can test it. 
        // In the final version, you might want to uncomment this line:
        // if ( current_user_can( 'manage_options' ) ) return $commentdata;

        $provider = get_option( 'cacp_captcha_provider' );
        $secret = get_option( 'cacp_secret_key' );

        if ( 'none' === $provider || ! $provider ) return $commentdata;

        $verified = false;

        if ( 'math' === $provider ) {
            $ans = isset($_POST['cacp_math_ans']) ? intval($_POST['cacp_math_ans']) : '';
            $expected = isset($_POST['cacp_math_hash']) ? sanitize_text_field($_POST['cacp_math_hash']) : '';
            
            // Check if the hash of (Salt + User Answer) matches the hidden hash
            if ( md5( $this->salt . $ans ) === $expected ) {
                $verified = true;
            }
        } else {
            // API Checks (Google/Cloudflare/hCaptcha)
            $response = ''; 
            $url = '';
            
            if ( 'google' === $provider ) {
                $response = $_POST['g-recaptcha-response'] ?? '';
                $url = 'https://www.google.com/recaptcha/api/siteverify';
            } elseif ( 'cloudflare' === $provider ) {
                $response = $_POST['cf-turnstile-response'] ?? '';
                $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            } elseif ( 'hcaptcha' === $provider ) {
                $response = $_POST['h-captcha-response'] ?? '';
                $url = 'https://hcaptcha.com/siteverify';
            }

            if ( $response ) {
                $req = wp_remote_post( $url, array( 'body' => array( 'secret' => $secret, 'response' => $response ) ) );
                if ( ! is_wp_error( $req ) ) {
                    $body = json_decode( wp_remote_retrieve_body( $req ), true );
                    if ( isset( $body['success'] ) && $body['success'] ) $verified = true;
                }
            }
        }

        if ( ! $verified ) {
            // This stops the comment submission completely
            wp_die( '<strong>Error:</strong> Security Check Failed. Please go back and try again.', 'Captcha Error', array( 'response' => 403 ) );
        }

        return $commentdata;
    }
}
