<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CACP_Security {

    private $salt = 'cacp_math_';

    public function init() {
        add_action( 'comment_form_after_fields', array( $this, 'render_captcha' ) );
        add_action( 'comment_form_logged_in_after', array( $this, 'render_captcha' ) );
        add_filter( 'preprocess_comment', array( $this, 'verify_captcha' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts() {
        if ( ! is_singular() || ! comments_open() ) {
            return;
        }

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

        if ( ! $provider || 'none' === $provider ) {
            return;
        }

        if ( isset( $_GET['cacp_error'] ) ) {
            echo '<div class="cacp-error-msg">' .
                esc_html__( 'Security check failed. Please try again.', 'comment-avatar-captcha-pro' ) .
            '</div>';
        }

        echo '<div class="cacp-security-box">';

        if ( 'math' === $provider ) {

            $a = rand( 1, 9 );
            $b = rand( 1, 9 );
            $hash = md5( $this->salt . ( $a + $b ) . NONCE_SALT );

            echo '<p>' . esc_html( $a . ' + ' . $b ) . '</p>';
            echo '<input type="number" name="cacp_math_ans" required>';
            echo '<input type="hidden" name="cacp_math_hash" value="' . esc_attr( $hash ) . '">';

        } elseif ( 'google' === $provider ) {

            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';

        } elseif ( 'cloudflare' === $provider ) {

            echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '"></div>';

        } elseif ( 'hcaptcha' === $provider ) {

            echo '<div class="h-captcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
        }

        echo '</div>';
    }

    private function fail() {
        $ref = wp_get_referer() ? wp_get_referer() : home_url( '/' );
        wp_safe_redirect( add_query_arg( 'cacp_error', '1', $ref ) . '#respond' );
        exit;
    }

    public function verify_captcha( $commentdata ) {

        $provider = get_option( 'cacp_captcha_provider' );
        $secret   = get_option( 'cacp_secret_key' );

        if ( ! $provider || 'none' === $provider ) {
            return $commentdata;
        }

        $verified = false;

        if ( 'math' === $provider ) {

            if ( empty( $_POST['cacp_math_ans'] ) || empty( $_POST['cacp_math_hash'] ) ) {
                $this->fail();
            }

            $ans  = intval( $_POST['cacp_math_ans'] );
            $hash = sanitize_text_field( $_POST['cacp_math_hash'] );

            if ( hash_equals( md5( $this->salt . $ans . NONCE_SALT ), $hash ) ) {
                $verified = true;
            }

        } else {

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

            if ( empty( $response ) ) {
                $this->fail();
            }

            $req = wp_remote_post( $url, array(
                'timeout' => 10,
                'body' => array(
                    'secret'   => $secret,
                    'response' => sanitize_text_field( $response ),
                    'remoteip' => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
                ),
            ) );

            if ( ! is_wp_error( $req ) ) {
                $body = json_decode( wp_remote_retrieve_body( $req ), true );
                if ( ! empty( $body['success'] ) ) {
                    $verified = true;
                }
            }
        }

        if ( ! $verified ) {
            $this->fail();
        }

        return $commentdata;
    }
}
