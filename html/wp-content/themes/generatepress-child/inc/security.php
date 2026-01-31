<?php
/**
 * Security Settings
 * 
 * セキュリティ関連の設定
 */

/**
 * Security: Disable XML-RPC
 * DDoS攻撃やブルートフォース攻撃の標的になりやすいため無効化します。
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Security: Hide WordPress Version
 * ソースコード上のWPバージョン情報を削除し、攻撃者にバージョンを特定させにくくします。
 */
remove_action('wp_head', 'wp_generator');
