#!/bin/bash

# y.plusknasy.com の WordPress用データベースのバックアップ作成スクリプト
# 保存先: $COMMON_BACKUP_DIR/y.plusknasy.com/plusknasy_y/
# $COMMON_BACKUP_DIR は backup_config.sh からインポート
# wp-cli で実行

#######

set -e # 何かエラーがあったら、そこで処理を中断する

# 共通設定の読み込み
CONFIG_FILE="/home/plusknasy/etc/backup_config.sh"
if [ -f "$CONFIG_FILE" ]; then
    source "$CONFIG_FILE"
    setup_logging
else
    log_message "ERROR: Configuration file not found at $CONFIG_FILE"
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
DELETED_FILES=$(find "$BACKUP_DIR" -name "db_backup_*.sql" -mtime +30 -print -delete)

if [ -n "$DELETED_FILES" ]; then
    while read -r file; do
        log_message "CLEANUP: Deleted old backup: $file"
    done <<< "$DELETED_FILES"
fi

delete_logs