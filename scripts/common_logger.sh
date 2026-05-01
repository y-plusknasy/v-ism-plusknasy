#!/bin/bash

# ログ出力の共通関数と設定をまとめたスクリプト
# 保存先: $LOG_DIR/{YYYYMM}.log
# LOG_DIR は scripts_common_config.sh からインポート

#######

# 共通設定の読み込み
CONFIG_FILE="/home/plusknasy/etc/scripts_common_config.sh"
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
else
    mkdir -p "/home/plusknasy/logs"
    echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: Config file not found." >> "/home/plusknasy/logs/config_error.log"
    exit 1
fi

# ログの共通フォーマット関数
# 使い方: log_message "ログメッセージ"
log_message() {
    local MESSAGE="$1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [$(basename "$0")] ${MESSAGE}"
}

# ログの出力先をセットアップする関数
setup_logging() {
    # ログディレクトリがなければ作成
    mkdir -p "$LOG_DIR"

    # ログファイルのパスを作成 (例: /home/plusknasy/logs/202605.log)
    local LOG_FILE="${LOG_DIR}/$(date +%Y%m).log"

    # 全出力をログファイルへリダイレクト
    exec >> "$LOG_FILE" 2>&1
}

# LOG_TTL 日以上前のログファイルを削除
delete_logs() {
    # 削除対象があるか確認して、あれば削除・記録する
    if [ -d "$LOG_DIR" ]; then
        find "$LOG_DIR" -name "*.log" -mtime "$LOG_TTL" | while read -r file; do
            rm -f "$file"
            log_message "CLEANUP: Deleted old log: $file"
        done
    fi
}