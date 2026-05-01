<?php
/**
 * Application Logger
 *
 * ログの保存先・ファイル名プレフィックスは .env の設定を参照します。
 *
 * ログ保存先は wp-config.php で定義する定数 WP_APP_LOG_DIR を参照します。
 * 未定義の場合は環境変数 WP_LOG_DIR を、それも未定義の場合は ABSPATH/logs を使用します。
 *
 * wp-config.php 設定例:
 *   define( 'WP_APP_LOG_DIR', '/home/plusknasy/logs' );  // 本番
 *   define( 'WP_APP_LOG_DIR', '/workspace/backups/logs' ); // 開発
 *
 * ログファイル名フォーマット: {YYYYMM}.log
 * ログ行フォーマット:         YYYY-MM-DD HH:MM:SS [呼び出し元ファイル名] [LEVEL] メッセージ
 */

/**
 * ログを書き込む
 *
 * @param string $message ログメッセージ
 * @param string $level   ログレベル: 'INFO' | 'WARNING' | 'ERROR'
 * @return void
 */
function log_message(string $message, string $level = 'INFO'): void {
    if (defined('WP_APP_LOG_DIR')) {
        $logDir = rtrim(WP_APP_LOG_DIR, '/');
    } else {
        $logDir = rtrim((string)(getenv('WP_LOG_DIR') ?: (defined('ABSPATH') ? ABSPATH . 'logs' : '/tmp/wp-logs')), '/');
    }
    $logFile   = $logDir . '/' . date('Ym') . '.log';

    // ディレクトリが存在しない場合は作成
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // 呼び出し元ファイル名を取得
    $trace    = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller   = isset($trace[0]['file']) ? basename($trace[0]['file']) : 'unknown';

    $line = sprintf(
        "%s [%s] [%s] %s\n",
        date('Y-m-d H:i:s'),
        $caller,
        strtoupper($level),
        $message
    );

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
