#!/bin/bash
#
# デプロイスクリプト - Xserverへのrsync同期
#
# 使い方:
#   1. chmod +x deploy.sh で実行権限を付与（初回のみ）
#   2. ./deploy.sh でデプロイ実行
#

set -e  # エラーで停止

# ===========================
# 設定
# ===========================
SSH_HOST="xserver"
REMOTE_PATH="/home/plusknasy/plusknasy.com/public_html/y.plusknasy.com"

# ===========================
# 同期対象の定義
# ===========================

echo "=========================================="
echo "Xserver デプロイ開始"
echo "=========================================="

# 1. 子テーマのファイルを同期
echo ""
echo "[1/4] 子テーマ (generatepress-child) を同期中..."
rsync -avz --delete \
  --exclude 'vendor/' \
  --exclude '*-firebase-credentials.json' \
  html/wp-content/themes/generatepress-child/ \
  ${SSH_HOST}:${REMOTE_PATH}/wp-content/themes/generatepress-child/

# 2. wp-config-docker.php を同期（本番では使用しないがバックアップとして）
echo ""
echo "[2/4] wp-config-docker.php を同期中..."
rsync -avz \
  wp-config-docker.php \
  ${SSH_HOST}:${REMOTE_PATH}/

# 3. wp-cli.yml を同期
echo ""
echo "[3/4] wp-cli.yml を同期中..."
rsync -avz \
  wp-cli.yml \
  ${SSH_HOST}:${REMOTE_PATH}/

# 4. Firebase設定ファイルを同期
echo ""
echo "[4/4] Firebase設定ファイルを同期中..."
rsync -avz \
  firebase.json \
  storage.rules \
  cors.json \
  ${SSH_HOST}:${REMOTE_PATH}/

echo ""
echo "=========================================="
echo "同期完了！"
echo "=========================================="
echo ""
echo "次のステップ:"
echo "  1. SSH接続: ssh ${SSH_HOST}"
echo "  2. テーマディレクトリへ移動:"
echo "     cd ${REMOTE_PATH}/wp-content/themes/generatepress-child"
echo "  3. Composer依存関係をインストール:"
echo "     composer install --no-dev --optimize-autoloader"
echo "  4. Firebase認証情報を手動アップロード:"
echo "     scp your-firebase-credentials.json ${SSH_HOST}:${REMOTE_PATH}/wp-content/themes/generatepress-child/firebase-credentials.json"
echo ""
