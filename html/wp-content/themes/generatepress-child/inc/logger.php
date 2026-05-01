<?php
/**
 * Application Logger
 *
 * ログの保存先・ファイル名プレフィックスは .env の設定を参照します。
 *
 * .env 設定キー:
 *   WP_LOG_DIR         ログ保存ディレクトリの絶対パス（デフォルト: /workspace/backups/logs）
 *   WP_LOG_FILE_PREFIX ログファイル名のプレフィックス（デフォルト: app）
 *
 * ログファイル名フォーマット: {WP_LOG_FILE_PREFIX}_{YYYYMM}.log
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
    $logDir    = rtrim((string)(getenv('WP_LOG_DIR') ?: '/workspace/backups/logs'), '/');
    $prefix    = (string)(getenv('WP_LOG_FILE_PREFIX') ?: 'app');
    $logFile   = $logDir . '/' . $prefix . '_' . date('Ym') . '.log';

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
