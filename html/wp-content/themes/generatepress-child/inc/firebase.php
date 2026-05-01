<?php
/**
 * Firebase Storage Integration
 * 
 * Firebase Storage への音声ファイルアップロード機能
 */

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

/**
 * Firebase連携: Bucket インスタンスを取得
 */
function get_firebase_storage() {
    static $bucket = null;
    
    if ($bucket === null) {
        $serviceAccountPath = __DIR__ . '/../v-ism-plusknasy-firebase-credentials.json';
        
        if (!file_exists($serviceAccountPath)) {
            error_log('Firebase credentials not found: ' . $serviceAccountPath);
            return null;
        }
        
        try {
            $firebase = (new Factory)->withServiceAccount($serviceAccountPath);
            $storage = $firebase->createStorage();
            
            // 明示的にバケット名を指定
            $bucket = $storage->getStorageClient()->bucket('v-ism-plusknasy.firebasestorage.app');
        } catch (Exception $e) {
            error_log('Firebase initialization failed: ' . $e->getMessage());
            return null;
        }
    }
    
    return $bucket;
}

/**
 * Firebase Storage: 音声ファイルをアップロード
 * 
 * @param string $localFilePath ローカルファイルパス
 * @param string $remoteFileName リモートファイル名（例: ja-v1-1234567890.mp3）
 * @param int $post_id 投稿ID
 * @param string $lang 言語コード（'ja' または 'en'）
 * @return string|false 公開URL または false
 */
function upload_audio_to_firebase($localFilePath, $remoteFileName, $post_id, $lang) {
    $bucket = get_firebase_storage();
    
    if (!$bucket) {
        log_message('upload_audio_to_firebase: Firebase bucket is null, aborting upload', 'ERROR');
        return false;
    }
    
    try {
        // パス構造: audio/post-{ID}/{lang}-v{version}-{timestamp}.mp3
        $remotePath = 'audio/post-' . $post_id . '/' . $remoteFileName;
        
        log_message(
            sprintf('Firebase upload start: post_id=%d lang=%s path=%s', $post_id, $lang, $remotePath),
            'INFO'
        );
        
        // ファイルをアップロード
        $bucket->upload(
            fopen($localFilePath, 'r'),
            [
                'name' => $remotePath,
                'predefinedAcl' => 'publicRead'
            ]
        );
        
        // 公開URLを生成
        $publicUrl = sprintf(
            'https://storage.googleapis.com/%s/%s',
            $bucket->name(),
            $remotePath
        );
        
        log_message('Firebase upload success: ' . $publicUrl, 'INFO');
        
        return $publicUrl;
        
    } catch (Exception $e) {
        log_message(
            sprintf('Firebase upload failed: post_id=%d lang=%s error=%s', $post_id, $lang, $e->getMessage()),
            'ERROR'
        );
        return false;
    }
}

/**
 * Firebase Storageから特定投稿・言語の最新バージョン番号を取得
 * 
 * @param int $post_id 投稿ID
 * @param string $lang 言語コード ('ja' または 'en')
 * @return int 次のバージョン番号
 */
function get_next_audio_version($post_id, $lang) {
    $bucket = get_firebase_storage();
    
    if (!$bucket) {
        log_message('get_next_audio_version: Firebase bucket is null, returning version 1', 'ERROR');
        return 1;
    }
    
    try {
        $prefix = 'audio/post-' . $post_id . '/' . $lang . '-v';
        log_message(
            sprintf('get_next_audio_version: searching prefix="%s"', $prefix),
            'INFO'
        );
        
        // 該当ディレクトリ内のファイル一覧を取得
        $objects = $bucket->objects(['prefix' => $prefix]);
        
        $max_version = 0;
        
        foreach ($objects as $object) {
            $name = $object->name();
            // ファイル名からバージョン番号を抽出: ja-v2-xxx.mp3 → 2
            if (preg_match('/' . preg_quote($lang, '/') . '-v(\d+)-/', $name, $matches)) {
                $version = intval($matches[1]);
                if ($version > $max_version) {
                    $max_version = $version;
                }
            }
        }
        
        $next_version = $max_version + 1;
        log_message(
            sprintf('get_next_audio_version: post_id=%d lang=%s current_max=%d next=%d', $post_id, $lang, $max_version, $next_version),
            'INFO'
        );
        
        return $next_version;
        
    } catch (Exception $e) {
        log_message(
            sprintf('get_next_audio_version failed: post_id=%d lang=%s error=%s', $post_id, $lang, $e->getMessage()),
            'ERROR'
        );
        return 1;
    }
}
