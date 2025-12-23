<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CACP_Frontend {

    protected $uploaded_id = 0;

    public function init() {

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        add_action( 'comment_form_logged_in_after', array( $this, 'render_fields' ) );
        add_action( 'comment_form_after_fields', array( $this, 'render_fields' ) );
        add_action( 'comment_form_top', array( $this, 'add_enctype' ) );

        add_action( 'preprocess_comment', array( $this, 'handle_data' ) );
        add_action( 'comment_post', array( $this, 'save_avatar_identity' ) );

        add_filter( 'pre_get_avatar_data', array( $this, 'override_avatar' ), 10, 2 );
        add_filter( 'get_comment_author_link', array( $this, 'append_badge' ), 10, 3 );
        add_filter( 'comment_text', array( $this, 'append_bio' ), 10, 2 );
    }

    public function enqueue_assets() {
        if ( ! is_singular() || ! comments_open() ) {
            return;
        }

        wp_enqueue_style(
            'cacp-style',
            CACP_URL . 'assets/css/style.css',
            array(),
            CACP_VERSION
        );

        wp_enqueue_script(
            'cacp-script',
            CACP_URL . 'assets/js/script.js',
            array( 'jquery' ),
            CACP_VERSION,
            true
        );
    }

    /* ---------------- Guest avatar storage ---------------- */

    private function get_guest_avatars() {
        $data = get_option( 'cacp_guest_avatars', array() );
        return is_array( $data ) ? $data : array();
    }

    private function get_guest_avatar_id( $email ) {
        if ( ! $email ) return 0;
        $avatars = $this->get_guest_avatars();
        $key = md5( strtolower( $email ) );
        return isset( $avatars[ $key ] ) ? (int) $avatars[ $key ] : 0;
    }

    private function save_guest_avatar( $email, $attachment_id ) {
        if ( ! $email || ! $attachment_id ) return;
        $avatars = $this->get_guest_avatars();
        $avatars[ md5( strtolower( $email ) ) ] = (int) $attachment_id;
        update_option( 'cacp_guest_avatars', $avatars, false );
    }

    private function remove_guest_avatar( $email ) {
        if ( ! $email ) return;
        $avatars = $this->get_guest_avatars();
        unset( $avatars[ md5( strtolower( $email ) ) ] );
        update_option( 'cacp_guest_avatars', $avatars, false );
    }

    /* ---------------- Render UI ---------------- */

    public function render_fields() {

        $user_id = get_current_user_id();
        $email   = '';

        if ( ! $user_id ) {
            $commenter = wp_get_current_commenter();
            $email = isset( $commenter['comment_author_email'] )
                ? sanitize_email( $commenter['comment_author_email'] )
                : '';
        }

        $has_avatar = $user_id
            ? (bool) get_user_meta( $user_id, 'cacp_profile_pic', true )
            : (bool) $this->get_guest_avatar_id( $email );
        ?>
        <div class="cacp-container">

            <?php wp_nonce_field( 'cacp_upload', 'cacp_nonce' ); ?>

            <input type="hidden" name="cacp_avatar_action" id="cacp_avatar_action" value="keep">

            <!-- SINGLE BUTTON -->
            <button
                type="button"
                class="cacp-edit-avatar-btn"
                aria-expanded="false"
            >
                <?php esc_html_e( 'Edit profile picture', 'comment-avatar-captcha-pro' ); ?>
            </button>

            <!-- DROPDOWN (HIDDEN BY DEFAULT) -->
            <div class="cacp-avatar-dropdown" aria-hidden="true">
                <button type="button" class="cacp-avatar-change">
                    <?php esc_html_e( 'Change photo', 'comment-avatar-captcha-pro' ); ?>
                </button>

                <?php if ( $has_avatar ) : ?>
                    <button type="button" class="cacp-avatar-remove">
                        <?php esc_html_e( 'Remove photo', 'comment-avatar-captcha-pro' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <input
                type="file"
                name="cacp-file-input"
                id="cacp-file-input"
                accept="image/*"
            >

            <?php if ( get_option( 'cacp_enable_bio' ) ) : ?>
                <div class="cacp-field-row">
                    <input
                        type="text"
                        name="cacp_bio"
                        placeholder="<?php esc_attr_e( 'About you (8 words max)', 'comment-avatar-captcha-pro' ); ?>"
                    >
                </div>
            <?php endif; ?>

        </div>
        <?php
    }

    /* ---------------- Handle submission ---------------- */

    public function handle_data( $commentdata ) {

        if (
            ! empty( $_FILES['cacp-file-input']['name'] ) &&
            isset( $_POST['cacp_nonce'] ) &&
            wp_verify_nonce( $_POST['cacp_nonce'], 'cacp_upload' )
        ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            if ( (int) $_FILES['cacp-file-input']['size'] > 2 * 1024 * 1024 ) {
                wp_die( esc_html__( 'File size must be under 2MB.', 'comment-avatar-captcha-pro' ) );
            }

            $id = media_handle_upload( 'cacp-file-input', 0 );
            if ( ! is_wp_error( $id ) ) {
                $this->uploaded_id = (int) $id;
            }
        }

        return $commentdata;
    }

    public function save_avatar_identity() {

        $action = isset( $_POST['cacp_avatar_action'] )
            ? sanitize_text_field( $_POST['cacp_avatar_action'] )
            : 'keep';

        $user_id = get_current_user_id();
        $email   = '';

        if ( ! $user_id ) {
            $commenter = wp_get_current_commenter();
            $email = isset( $commenter['comment_author_email'] )
                ? sanitize_email( $commenter['comment_author_email'] )
                : '';
        }

        if ( 'remove' === $action ) {
            $user_id
                ? delete_user_meta( $user_id, 'cacp_profile_pic' )
                : $this->remove_guest_avatar( $email );
            return;
        }

        if ( $this->uploaded_id ) {
            $user_id
                ? update_user_meta( $user_id, 'cacp_profile_pic', $this->uploaded_id )
                : $this->save_guest_avatar( $email, $this->uploaded_id );
        }
    }

    /* ---------------- Avatar display ---------------- */

    public function override_avatar( $args, $id_or_email ) {

        $avatar_id = 0;

        if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
            $comment = get_comment( $id_or_email->comment_ID );

            if ( $comment && $comment->user_id ) {
                $avatar_id = (int) get_user_meta( $comment->user_id, 'cacp_profile_pic', true );
            }

            if ( ! $avatar_id && $comment && $comment->comment_author_email ) {
                $avatar_id = $this->get_guest_avatar_id(
                    sanitize_email( $comment->comment_author_email )
                );
            }
        }

        if ( $avatar_id ) {
            $url = wp_get_attachment_image_url( $avatar_id, 'thumbnail' );
            if ( $url ) {
                $args['url'] = $url;
                $args['found_avatar'] = true;
            }
        }

        return $args;
    }

    public function append_badge( $link, $author, $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( $comment && $comment->user_id ) {
            $label = get_user_meta( $comment->user_id, 'cacp_user_label', true );
            if ( $label ) {
                $link .= ' <span class="cacp-admin-badge">' . esc_html( $label ) . '</span>';
            }
        }
        return $link;
    }

    public function append_bio( $text, $comment ) {
        $bio = get_comment_meta( $comment->comment_ID, 'cacp_bio', true );
        if ( $bio ) {
            $text .= '<div class="cacp-comment-bio">' . esc_html( $bio ) . '</div>';
        }
        return $text;
    }

    public function add_enctype() {
        echo '<input type="hidden" name="cacp_force_multipart" value="1">';
    }
}
