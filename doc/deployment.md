# デプロイガイド

## 初回セットアップ

### 1. デプロイスクリプトの設定

`deploy.sh` を編集して、本番環境の情報を設定：

```bash
SSH_HOST="your-account@your-server.xsrv.jp"
REMOTE_PATH="~/your-domain/public_html"
```

### 2. SSHキー認証の設定（推奨）

パスワード入力なしでデプロイできるよう、SSHキーを設定：

```bash
# ホストマシンで実行
ssh-copy-id your-account@your-server.xsrv.jp
```

### 3. 本番環境の準備

SSH接続して、必要なディレクトリを確認：

```bash
ssh your-account@your-server.xsrv.jp
cd ~/your-domain/public_html
ls -la wp-content/themes/
```

---

## デプロイ手順

### 方法A: rsyncスクリプト（推奨）

```bash
# プロジェクトルートで実行
./deploy.sh
```

**同期される内容:**
- 子テーマファイル (`generatepress-child/`)
  - `vendor/` と `*-firebase-credentials.json` は除外
- `wp-config-docker.php`
- `wp-cli.yml`
- `firebase.json`, `storage.rules`

### 方法B: 手動rsync

特定のファイルのみ更新したい場合：

```bash
# 子テーマのみ
rsync -avz --delete \
  --exclude 'vendor/' \
  --exclude '*-firebase-credentials.json' \
  html/wp-content/themes/generatepress-child/ \
  your-account@your-server.xsrv.jp:~/your-domain/public_html/wp-content/themes/generatepress-child/

# 特定のファイルのみ
rsync -avz \
  html/wp-content/themes/generatepress-child/style.css \
  your-account@your-server.xsrv.jp:~/your-domain/public_html/wp-content/themes/generatepress-child/
```

---

## デプロイ後の作業

### 1. Composer依存関係のインストール

```bash
ssh your-account@your-server.xsrv.jp
cd ~/your-domain/public_html/wp-content/themes/generatepress-child
composer install --no-dev --optimize-autoloader
```

### 2. Firebase認証情報のアップロード

**セキュリティ上、認証情報は手動でアップロード:**

```bash
# ホストマシンから実行
scp /path/to/your-firebase-credentials.json \
  your-account@your-server.xsrv.jp:~/your-domain/public_html/wp-content/themes/generatepress-child/firebase-credentials.json
```

SSH接続してパーミッション設定：

```bash
ssh your-account@your-server.xsrv.jp
cd ~/your-domain/public_html/wp-content/themes/generatepress-child
chmod 600 firebase-credentials.json
```

### 3. 動作確認

WordPress管理画面で確認：
- 外観 → テーマ → GeneratePress Child が有効化されているか
- 投稿編集 → Podcast Audio Files メタボックスが表示されるか
- フロントエンド → プレイヤーが動作するか

---

## トラブルシューティング

### rsyncエラー: Permission denied

**原因**: SSH接続の問題

**解決方法**:
```bash
# SSH接続テスト
ssh your-account@your-server.xsrv.jp

# SSHキーが設定されているか確認
ssh -v your-account@your-server.xsrv.jp
```

### Composer install エラー

**原因**: Composerがインストールされていない

**解決方法**:
```bash
# Xserverでは通常 composer コマンドが使えます
which composer

# 使えない場合は、ローカルインストール
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
./composer.phar install --no-dev --optimize-autoloader
```

### Firebase接続エラー

**原因**: 認証情報のパスまたはパーミッション

**解決方法**:
```bash
# ファイルの存在確認
ls -la ~/your-domain/public_html/wp-content/themes/generatepress-child/firebase-credentials.json

# パーミッション確認（600であること）
chmod 600 firebase-credentials.json

# 内容確認（JSONフォーマットが正しいか）
head -n 5 firebase-credentials.json
```

---

## GitHub Actionsでの自動デプロイ（オプション）

将来的に、GitHubにpushしたら自動デプロイする設定も可能です。
詳細は `.github/workflows/` に追加できます。

**メリット:**
- 手動デプロイ不要
- デプロイ履歴が残る
- チーム開発時の誤デプロイ防止

**デメリット:**
- SSHキーをGitHub Secretsに登録する必要がある
- 初期設定がやや複雑

---

## ベストプラクティス

### デプロイ前チェックリスト

- [ ] ローカル環境で動作確認済み
- [ ] Git commitとpushが完了している
- [ ] `composer.lock` が最新
- [ ] Firebase認証情報のバックアップがある
- [ ] データベースのバックアップがある（念のため）

### デプロイ後チェックリスト

- [ ] フロントエンドが正常に表示される
- [ ] プレイヤーが動作する
- [ ] 音声ファイルのアップロードができる
- [ ] ダウンロードボタンが機能する
- [ ] ダークモード切り替えが動作する

---

**最終更新**: 2026-02-02
