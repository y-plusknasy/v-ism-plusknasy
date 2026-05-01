#!/bin/bash

# ログファイルの保存期間
LOG_TTL="+90"

# バックアップディレクトリのパスをエクスポート
export COMMON_BACKUP_DIR="/home/plusknasy/backups"

# ログの共通フォーマット関数
# 使い方: log_message "ログメッセージ"
log_message() {
    local MESSAGE="$1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$(basename "$0")] ${MESSAGE}"
}

# ログの出力先をセットアップする関数
# 出力先: $COMMON_BACKUP_DIR/logs/
setup_logging() {
    local LOG_DIR="${COMMON_BACKUP_DIR}/logs"

    # ログディレクトリがなければ作成
    mkdir -p "$LOG_DIR"

    # ログファイルのパスを作成 (例: /home/plusknasy/backups/logs/backup_202605.log)
    local LOG_FILE="${LOG_DIR}/backup_$(date +%Y%m).log"

    # 全出力をログファイルへリダイレクト
    exec >> "$LOG_FILE" 2>&1
}

# LOG_TTL 日以上前のログファイルを削除
delete_logs() {
    local LOG_DIR="${COMMON_BACKUP_DIR}/logs"
    DELETED_FILES=$(find "$LOG_DIR" -name "backup_*.log" -mtime "$LOG_TTL" -print -delete)

    if [ -n "$DELETED_FILES" ]; then
        while read -r file; do
            log_message "CLEANUP: Deleted old log: $file"
        done <<< "$DELETED_FILES"
    fi
}