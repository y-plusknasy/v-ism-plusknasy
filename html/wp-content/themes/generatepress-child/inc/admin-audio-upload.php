<?php
/**
 * Admin Audio Upload UI
 * 
 * 管理画面での音声ファイルアップロード機能
 */

/**
 * カスタムメタボックスを追加
 */
function add_podcast_audio_meta_box() {
    add_meta_box(
        'podcast_audio_files',
        'Podcast Audio Files',
        'render_podcast_audio_meta_box',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_podcast_audio_meta_box');

/**
 * メタボックスの内容を表示
 */
function render_podcast_audio_meta_box($post) {
    wp_nonce_field('podcast_audio_meta_box', 'podcast_audio_meta_box_nonce');
    
    $audio_url_jp = get_post_meta($post->ID, 'podcast_audio_url', true);
    $audio_url_en = get_post_meta($post->ID, 'podcast_audio_url_en', true);
    ?>
    <div class="podcast-audio-upload-container">
        <div style="background: #f0f0f1; padding: 10px; margin-bottom: 15px; border-left: 4px solid #2271b1;">
            <strong>投稿ID:</strong> <?php echo esc_html($post->ID); ?> 
            <span style="color: #666; font-size: 12px;">
                (Firebase Storage: <code>audio/post-<?php echo esc_html($post->ID); ?>/</code>)
            </span>
        </div>
        <style>
            .podcast-audio-field {
                margin-bottom: 20px;
                padding: 15px;
                border: 1px solid #ddd;
                background: #f9f9f9;
            }
            .podcast-audio-field label {
                display: block;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .podcast-audio-field input[type="file"] {
                display: block;
                margin-bottom: 10px;
            }
            .podcast-audio-current-url {
                color: #0073aa;
                font-size: 12px;
                word-break: break-all;
            }
            .podcast-audio-status {
                margin-top: 10px;
                padding: 10px;
                border-radius: 4px;
            }
            .status-success {
                background: #d4edda;
                color: #155724;
            }
            .status-error {
                background: #f8d7da;
                color: #721c24;
            }
        </style>

        <div class="podcast-audio-field">
            <label for="podcast_audio_file_jp">
                🇯🇵 Japanese Audio (MP3)
            </label>
            <input type="file" 
                   id="podcast_audio_file_jp" 
                   name="podcast_audio_file_jp" 
                   accept="audio/mpeg">
            <?php if ($audio_url_jp): ?>
                <div class="podcast-audio-current-url" style="margin-top: 10px;">
                    <strong>Current:</strong> <?php echo esc_html($audio_url_jp); ?>
                    <button type="button" 
                            class="button button-small delete-audio-url" 
                            data-lang="ja" 
                            data-post-id="<?php echo esc_attr($post->ID); ?>"
                            style="margin-left: 10px; color: #a00;">
                        このURLを削除
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="podcast-audio-field">
            <label for="podcast_audio_file_en">
                🇺🇸 English Audio (MP3)
            </label>
            <input type="file" 
                   id="podcast_audio_file_en" 
                   name="podcast_audio_file_en" 
                   accept="audio/mpeg">
            <?php if ($audio_url_en): ?>
                <div class="podcast-audio-current-url" style="margin-top: 10px;">
                    <strong>Current:</strong> <?php echo esc_html($audio_url_en); ?>
                    <button type="button" 
                            class="button button-small delete-audio-url" 
                            data-lang="en" 
                            data-post-id="<?php echo esc_attr($post->ID); ?>"
                            style="margin-left: 10px; color: #a00;">
                        このURLを削除
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <p style="font-size: 12px; color: #666;">
            <strong>Note:</strong> Uploaded files will be automatically transferred to Firebase Storage 
            and the public URL will be saved. The delete button removes the URL from the post, but keeps the file in Firebase Storage for backup purposes.
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.delete-audio-url').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('本当にこのURLを削除しますか？\nFirebase上のファイルは削除されませんが、記事からのリンクが解除されます。')) {
                return;
            }
            
            var button = $(this);
            var lang = button.data('lang');
            var postId = button.data('post-id');
            
            button.prop('disabled', true).text('削除中...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_podcast_audio_url',
                    post_id: postId,
                    lang: lang,
                    nonce: '<?php echo wp_create_nonce('delete_audio_url_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('.podcast-audio-current-url').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('削除に失敗しました: ' + response.data);
                        button.prop('disabled', false).text('このURLを削除');
                    }
                },
                error: function() {
                    alert('エラーが発生しました');
                    button.prop('disabled', false).text('このURLを削除');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * 投稿保存時に音声ファイルをFirebaseにアップロード
 */
function save_podcast_audio_meta_box($post_id) {
    // Nonce確認
    if (!isset($_POST['podcast_audio_meta_box_nonce'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['podcast_audio_meta_box_nonce'], 'podcast_audio_meta_box')) {
        return;
    }
    
    // 自動保存の場合はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 権限確認
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 日本語音声の処理
    if (isset($_FILES['podcast_audio_file_jp']) && $_FILES['podcast_audio_file_jp']['error'] === UPLOAD_ERR_OK) {
        $result = process_audio_upload($_FILES['podcast_audio_file_jp'], $post_id, 'ja');
        if ($result) {
            update_post_meta($post_id, 'podcast_audio_url', $result);
        }
    }
    
    // 英語音声の処理
    if (isset($_FILES['podcast_audio_file_en']) && $_FILES['podcast_audio_file_en']['error'] === UPLOAD_ERR_OK) {
        $result = process_audio_upload($_FILES['podcast_audio_file_en'], $post_id, 'en');
        if ($result) {
            update_post_meta($post_id, 'podcast_audio_url_en', $result);
        }
    }
}
add_action('save_post', 'save_podcast_audio_meta_box');

/**
 * AJAX: 音声URLを削除
 */
function ajax_delete_podcast_audio_url() {
    // Nonce確認
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_audio_url_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // 権限確認
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    $lang = sanitize_text_field($_POST['lang']);
    $meta_key = ($lang === 'ja') ? 'podcast_audio_url' : 'podcast_audio_url_en';
    
    // URLを削除（Firebase上のファイルは削除しない）
    delete_post_meta($post_id, $meta_key);
    
    wp_send_json_success('URL deleted successfully');
}
add_action('wp_ajax_delete_podcast_audio_url', 'ajax_delete_podcast_audio_url');

/**
 * 音声ファイルのアップロード処理
 */
function process_audio_upload($file, $post_id, $lang) {
    // ファイルタイプ確認
    $allowed_types = ['audio/mpeg', 'audio/mp3'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return false;
    }
    
    // ファイルサイズ確認（100MB）
    if ($file['size'] > 100 * 1024 * 1024) {
        return false;
    }
    
    // Firebaseから最新バージョンを取得
    $version = get_next_audio_version($post_id, $lang);
    
    // ファイル名生成: {lang}-v{version}-{timestamp}.mp3
    $timestamp = time();
    $remote_filename = sprintf('%s-v%d-%d.mp3', $lang, $version, $timestamp);
    
    // Firebaseにアップロード
    $public_url = upload_audio_to_firebase($file['tmp_name'], $remote_filename, $post_id, $lang);
    
    return $public_url;
}
