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

    // アップロード結果をtransientから取得（一度読んだら削除）
    $transient_key = 'podcast_audio_upload_' . get_current_user_id() . '_' . $post->ID;
    $upload_results = get_transient($transient_key);
    if ($upload_results !== false) {
        delete_transient($transient_key);
    } else {
        $upload_results = ['jp' => null, 'en' => null];
    }
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
    var podcastAudioUploadResults = <?php echo wp_json_encode($upload_results); ?>;

    jQuery(document).ready(function($) {
        // ページロード時に常にfile inputをリセット（ブラウザのセッション復元対策）
        $('#podcast_audio_file_jp, #podcast_audio_file_en').val('');

        // アップロード結果メッセージを表示
        var langMap = { jp: '#podcast_audio_file_jp', en: '#podcast_audio_file_en' };
        $.each(podcastAudioUploadResults, function(lang, result) {
            if (result === null) return;
            var $input = $(langMap[lang]);
            var $field = $input.closest('.podcast-audio-field');
            $field.find('.podcast-audio-upload-result').remove();
            if (result === 'success') {
                $field.append(
                    '<div class="podcast-audio-status status-success podcast-audio-upload-result">✅ アップロード成功</div>'
                );
            } else {
                $field.append(
                    '<div class="podcast-audio-status status-error podcast-audio-upload-result">❌ アップロード失敗。ファイルを確認してください。</div>'
                );
            }
        });

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
        log_message('save_podcast_audio_meta_box: nonce verification failed for post_id=' . $post_id, 'WARNING');
        return;
    }
    
    // 自動保存の場合はスキップ
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // 権限確認
    if (!current_user_can('edit_post', $post_id)) {
        log_message('save_podcast_audio_meta_box: permission denied for post_id=' . $post_id, 'WARNING');
        return;
    }
    
    log_message('save_podcast_audio_meta_box: triggered for post_id=' . $post_id, 'INFO');

    $upload_results = ['jp' => null, 'en' => null];
    
    // 日本語音声の処理
    if (isset($_FILES['podcast_audio_file_jp']) && $_FILES['podcast_audio_file_jp']['error'] === UPLOAD_ERR_OK) {
        log_message('save_podcast_audio_meta_box: JP audio file received, starting upload for post_id=' . $post_id, 'INFO');
        $result = process_audio_upload($_FILES['podcast_audio_file_jp'], $post_id, 'ja');
        if ($result) {
            update_post_meta($post_id, 'podcast_audio_url', $result);
            log_message('save_podcast_audio_meta_box: podcast_audio_url updated for post_id=' . $post_id, 'INFO');
            $upload_results['jp'] = 'success';
        } else {
            log_message('save_podcast_audio_meta_box: JP audio upload failed for post_id=' . $post_id, 'ERROR');
            $upload_results['jp'] = 'error';
        }
    }
    
    // 英語音声の処理
    if (isset($_FILES['podcast_audio_file_en']) && $_FILES['podcast_audio_file_en']['error'] === UPLOAD_ERR_OK) {
        log_message('save_podcast_audio_meta_box: EN audio file received, starting upload for post_id=' . $post_id, 'INFO');
        $result = process_audio_upload($_FILES['podcast_audio_file_en'], $post_id, 'en');
        if ($result) {
            update_post_meta($post_id, 'podcast_audio_url_en', $result);
            log_message('save_podcast_audio_meta_box: podcast_audio_url_en updated for post_id=' . $post_id, 'INFO');
            $upload_results['en'] = 'success';
        } else {
            log_message('save_podcast_audio_meta_box: EN audio upload failed for post_id=' . $post_id, 'ERROR');
            $upload_results['en'] = 'error';
        }
    }

    // アップロードが発生した場合のみtransientに保存（60秒間有効、次の画面描画で一度だけ読む）
    if ($upload_results['jp'] !== null || $upload_results['en'] !== null) {
        $transient_key = 'podcast_audio_upload_' . get_current_user_id() . '_' . $post_id;
        set_transient($transient_key, $upload_results, 60);
    }
}
add_action('save_post', 'save_podcast_audio_meta_box');

/**
 * AJAX: 音声URLを削除
 */
function ajax_delete_podcast_audio_url() {
    // Nonce確認
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete_audio_url_nonce')) {
        log_message('ajax_delete_podcast_audio_url: invalid nonce', 'WARNING');
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    // 権限確認
    $post_id = intval($_POST['post_id']);
    if (!current_user_can('edit_post', $post_id)) {
        log_message('ajax_delete_podcast_audio_url: permission denied for post_id=' . $post_id, 'WARNING');
        wp_send_json_error('Permission denied');
        return;
    }
    
    $lang = sanitize_text_field($_POST['lang']);
    $meta_key = ($lang === 'ja') ? 'podcast_audio_url' : 'podcast_audio_url_en';
    
    log_message(
        sprintf('ajax_delete_podcast_audio_url: deleting meta_key=%s for post_id=%d', $meta_key, $post_id),
        'INFO'
    );
    
    // URLを削除（Firebase上のファイルは削除しない）
    delete_post_meta($post_id, $meta_key);
    
    log_message(
        sprintf('ajax_delete_podcast_audio_url: successfully deleted meta_key=%s for post_id=%d', $meta_key, $post_id),
        'INFO'
    );
    
    wp_send_json_success('URL deleted successfully');
}
add_action('wp_ajax_delete_podcast_audio_url', 'ajax_delete_podcast_audio_url');

/**
 * 音声ファイルのアップロード処理
 */
function process_audio_upload($file, $post_id, $lang) {
    log_message(
        sprintf('process_audio_upload: start post_id=%d lang=%s size=%d bytes', $post_id, $lang, $file['size']),
        'INFO'
    );
    
    // ファイルタイプ確認
    $allowed_types = ['audio/mpeg', 'audio/mp3'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        log_message(
            sprintf('process_audio_upload: invalid MIME type "%s" for post_id=%d lang=%s', $mime_type, $post_id, $lang),
            'WARNING'
        );
        return false;
    }
    
    // ファイルサイズ確認（100MB）
    if ($file['size'] > 100 * 1024 * 1024) {
        log_message(
            sprintf('process_audio_upload: file size %d bytes exceeds 100MB limit for post_id=%d lang=%s', $file['size'], $post_id, $lang),
            'WARNING'
        );
        return false;
    }
    
    // Firebaseから最新バージョンを取得
    $version = get_next_audio_version($post_id, $lang);
    
    // ファイル名生成: {lang}-v{version}-{timestamp}.mp3
    $timestamp = time();
    $remote_filename = sprintf('%s-v%d-%d.mp3', $lang, $version, $timestamp);
    log_message(
        sprintf('process_audio_upload: remote filename=%s post_id=%d', $remote_filename, $post_id),
        'INFO'
    );
    
    // Firebaseにアップロード
    $public_url = upload_audio_to_firebase($file['tmp_name'], $remote_filename, $post_id, $lang);
    
    // 一時ファイルを明示的に削除
    if (file_exists($file['tmp_name'])) {
        unlink($file['tmp_name']);
        log_message('process_audio_upload: temp file deleted: ' . $file['tmp_name'], 'INFO');
    }
    
    return $public_url;
}