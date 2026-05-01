#!/bin/bash

# y.plusknasy.com の WordPress用データベースのバックアップ作成スクリプト
# 保存先: $COMMON_BACKUP_DIR/y.plusknasy.com/plusknasy_y/
# $COMMON_BACKUP_DIR は backup_config.sh からインポート
# wp-cli で実行

#######

set -e # 何かエラーがあったら、そこで処理を中断する

# 共通設定の読み込み
CONFIG_FILE="/home/plusknasy/etc/backup_config.sh"
LOGGER_FILE="/home/plusknasy/scripts/common_logger.sh"
if [[ -f "$CONFIG_FILE" && -f "$LOGGER_FILE" ]]; then
    source "$CONFIG_FILE"
    source "$LOGGER_FILE"
    setup_logging
elif [ ! -f "$CONFIG_FILE" ]; then
    mkdir -p "/home/plusknasy/logs"
    echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: Config file not found." >> "/home/plusknasy/logs/config_error.log"
    exit 1
else
    mkdir -p "/home/plusknasy/logs"
    echo "$(date '+%Y-%m-%d %H:%M:%S') ERROR: Logger file not found." >> "/home/plusknasy/logs/config_error.log"
    exit 1
fi

# ディレクトリがなければ作成
PROJECT_DIR="y.plusknasy.com/plusknasy_y"
BACKUP_DIR="${COMMON_BACKUP_DIR}/${PROJECT_DIR}"
mkdir -p "$BACKUP_DIR"
log_message "INFO: New directory is created: $BACKUP_DIR"

# プロジェクトディレクトリへ移動
cd /home/plusknasy/plusknasy.com/public_html/y.plusknasy.com

# 日付付きのファイル名を作成 (例: db_backup_20260202.sql)
FILENAME="$BACKUP_DIR/db_backup_$(date +%Y%m%d).sql"

# WP-CLIでエクスポート
WP_OUTPUT=$(/home/plusknasy/bin/wp db export $FILENAME --path=/home/plusknasy/plusknasy.com/public_html/y.plusknasy.com)
log_message "WP-CLI: $WP_OUTPUT"

# 30日以上前のバックアップを削除
if [ -d "$BACKUP_DIR" ]; then
    find "$BACKUP_DIR" -name "db_backup_*.sql" -mtime +30 | while read -r file; do
        if [ -f "$file" ]; then
            rm -f "$file"
            log_message "CLEANUP: Deleted old backup: $file"
        fi
    done
fi

delete_logs

log_message "INFO: Backup process completed successfully."