#!/bin/bash
set -e # 何かエラーがあったら、そこで処理を中断する

# プロジェクトディレクトリへ移動
cd /home/plusknasy/plusknasy.com/public_html/y.plusknasy.com

# ディレクトリがなければ作成
mkdir -p backups

# 日付付きのファイル名を作成 (例: db_backup_20260202.sql)
FILENAME="backups/db_backup_$(date +%Y%m%d).sql"

# WP-CLIでエクスポート
/home/plusknasy/bin/wp db export $FILENAME --path=/home/plusknasy/plusknasy.com/public_html/y.plusknasy.com

# 30日以上前のバックアップを削除
find backups/ -name "db_*.sql" -mtime +30 -delete

