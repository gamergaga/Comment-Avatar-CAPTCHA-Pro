<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CACP_Frontend {

    protected $uploaded_id = null;

    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'comment_form_logged_in_after', array( $this, 'render_fields' ) );
        add_action( 'comment_form_after_fields', array( $this, 'render_fields' ) );
        add_action( 'comment_form_top', array( $this, 'add_enctype' ) );
        add_action( 'preprocess_comment', array( $this, 'handle_data' ) );
        add_action( 'comment_post', array( $this, 'save_meta_data' ), 10, 2 );
        add_filter( 'pre_get_avatar_data', array( $this, 'override_avatar' ), 10, 2 );
        add_filter( 'get_comment_author_link', array( $this, 'append_badges' ), 10, 3 );
        add_filter( 'comment_text', array( $this, 'append_bio' ), 10, 2 );
    }

    public function enqueue_assets() {
        if ( is_singular() && comments_open() ) {
            wp_enqueue_style( 'cacp-css', CACP_URL . 'assets/css/style.css', array(), CACP_VERSION );
            wp_enqueue_script( 'cacp-js', CACP_URL . 'assets/js/script.js', array( 'jquery' ), CACP_VERSION, true );
            wp_localize_script( 'cacp-js', 'cacp_vars', array( 'file_error' => 'Error: Invalid file type or size.' ) );
        }
    }

    public function render_fields() {
        $user_id = get_current_user_id();
        $saved_avatar_id = $user_id ? (int) get_user_meta( $user_id, 'cacp_profile_pic', true ) : 0;
        $has_avatar = $saved_avatar_id > 0;
        
        ?>
        <div class="cacp-container">
            <?php wp_nonce_field( 'cacp_upload', 'cacp_nonce' ); ?>
            
            <?php if ( $has_avatar ) : ?>
                <div class="cacp-user-controls">
                    <?php echo wp_get_attachment_image( $saved_avatar_id, array( 50, 50 ), false, array('class'=>'cacp-mini-prev') ); ?>
                    <button type="button" class="cacp-toggle-btn button button-small">Change Photo</button>
                </div>
            <?php endif; ?>

            <div class="cacp-upload-area" <?php echo $has_avatar ? 'style="display:none;"' : ''; ?>>
                <label class="cacp-dropzone" for="cacp-file-input">
                    <span class="cacp-browse button button-secondary">Select Photo</span>
                    <span class="cacp-txt">or Drag & Drop</span>
                </label>
                
                <input type="file" name="cacp-file-input" id="cacp-file-input" accept="image/*">
                
                <div class="cacp-preview" style="display:none;">
                    <img src="" id="cacp-preview-img" alt="Preview">
                    <span class="cacp-remove" title="Remove">&times;</span>
                </div>
            </div>

            <?php if ( get_option( 'cacp_enable_bio' ) ) : ?>
                <div class="cacp-field-row">
                    <input type="text" name="cacp_bio" class="regular-text" placeholder="About You (8 Words Max)">
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_data( $commentdata ) {
        // Upload Logic
        if ( ! empty( $_FILES['cacp-file-input']['name'] ) ) {
            if ( ! isset( $_POST['cacp_nonce'] ) || ! wp_verify_nonce( $_POST['cacp_nonce'], 'cacp_upload' ) ) {
                wp_die( 'Security check failed.' );
            }
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $id = media_handle_upload( 'cacp-file-input', 0 );
            if ( ! is_wp_error( $id ) ) $this->uploaded_id = $id;
        }

        // Bio Logic
        if ( isset( $_POST['cacp_bio'] ) ) {
            if ( str_word_count( sanitize_text_field( $_POST['cacp_bio'] ) ) > 8 ) {
                wp_die( 'Error: Bio must be 8 words or less.' );
            }
        }
        return $commentdata;
    }

    public function save_meta_data( $comment_id ) {
        if ( $this->uploaded_id ) {
            update_comment_meta( $comment_id, 'cacp_avatar_id', $this->uploaded_id );
            $uid = get_current_user_id();
            if($uid) update_user_meta( $uid, 'cacp_profile_pic', $this->uploaded_id );
        }
        if ( isset( $_POST['cacp_bio'] ) ) update_comment_meta( $comment_id, 'cacp_bio', sanitize_text_field($_POST['cacp_bio']) );
    }

    public function append_badges( $return, $author, $comment_id ) {
        $comment = get_comment( $comment_id );
        $uid = $comment->user_id;
        $html = $return;
        if ( $uid ) {
            $label = get_user_meta( $uid, 'cacp_user_label', true );
            if ( $label ) $html .= ' <span class="cacp-admin-badge">'.esc_html($label).'</span>';
        }
        return $html;
    }

    public function append_bio( $text, $comment ) {
        $bio = get_comment_meta( $comment->comment_ID, 'cacp_bio', true );
        if ( $bio ) $text .= '<div class="cacp-comment-bio">'.esc_html($bio).'</div>';
        return $text;
    }

    public function override_avatar( $args, $id_or_email ) {
        $aid = null;
        if ( is_object($id_or_email) && isset($id_or_email->comment_ID) ) {
            $aid = get_comment_meta( $id_or_email->comment_ID, 'cacp_avatar_id', true );
        }
        if ( !$aid && is_numeric($id_or_email) ) {
            $aid = get_user_meta( $id_or_email, 'cacp_profile_pic', true );
        }
        if ( $aid ) {
            $url = wp_get_attachment_image_url( $aid, 'thumbnail' );
            if($url) { $args['url'] = $url; $args['found_avatar'] = true; }
        }
        return $args;
    }

    public function add_enctype() {
        echo '<input type="hidden" name="force_multipart" value="1">';
    }
}
