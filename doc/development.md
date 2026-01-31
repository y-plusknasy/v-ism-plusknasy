# V-ism +knasy - 開発環境ドキュメント

## 1. 開発環境の概要

本プロジェクトでは、開発者間の環境差異を排除し、スムーズなセットアップを実現するために、**Docker** および **VS Code Dev Containers** を採用しています。

開発に必要な全てのツール（PHP, MariaDB, WP-CLI, Firebase CLI, Node.js）はコンテナ内に封入されています。

## 2. 前提条件

*   Docker Desktop (または Docker Engine)
*   Visual Studio Code
*   VS Code Extension: [Dev Containers](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers)

## 3. 環境セットアップ手順

1.  **リポジトリのクローン**:
    ```bash
    git clone https://github.com/y-plusknasy/v-ism-plusknasy.git
    cd v-ism-plusknasy
    ```
2.  **VS Code で開く**:
    ```bash
    code .
    ```
3.  **コンテナの起動**:
    *   VS Code が `.devcontainer` フォルダを検出し、右下に通知が表示されるので「Reopen in Container」をクリックします。
    *   または、コマンドパレット (`Cmd+Shift+P` / `Ctrl+Shift+P`) から `Dev Containers: Reopen in Container` を実行します。
    *   初回ビルドには数分かかる場合があります。

4.  **動作確認**:
    *   コンテナ起動後、ブラウザで [http://localhost:8081](http://localhost:8081) にアクセスし、WordPress サイトが表示されることを確認します。

## 4. コンテナ構成詳細

`compose.yml` および `.devcontainer/` に基づく構成は以下の通りです。

### 4.1 サービス定義
*   **wordpress**:
    *   ベースイメージ: `wordpress:php8.3-apache` (プロジェクト用にカスタムビルド)
    *   ポート: `8081` (ホスト) -> `80` (コンテナ)
    *   マウント:
        *   `./html`: `/var/www/html` (WordPress本体)
        *   `.`: `/workspace` (プロジェクトルート。Firebase設定などの編集用)
    *   同梱ツール: WP-CLI, Firebase CLI, Node.js (LTS), Git
*   **db**:
    *   イメージ: `mariadb:10.5`
    *   ポート: `3306`
    *   データ永続化: Docker Volume `db_data`

### 4.2 環境変数 (.env)
DB接続情報などの機密情報は `.env` ファイルで管理され、`compose.yml` 経由でコンテナに注入されます。また、`wp-config.php` はこれらの環境変数を優先的に読み込むように修正されています。

```ini
# .env example
DB_ROOT_PASSWORD=root_password
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=password
```

### 4.3 ローカル環境特有の設定

本開発環境では、スムーズな操作とセキュリティ確保のために以下の調整が行われています。

*   **WP-CLI 設定 (`wp-cli.yml`)**:
    *   `path: /var/www/html` を指定しているため、`/workspace` ディレクトリから直接 `wp` コマンドを実行可能です。
    *   開発データベースへの接続時にSSLエラーが発生しないよう、`skip-ssl: true` が自動的に適用されます。
*   **URL 固定 (`wp-config-docker.php`)**:
    *   本番データベースをインポートしてもローカル環境で動作するよう、`WP_HOME` および `WP_SITEURL` を `http://localhost:8081` に強制設定しています。
*   **Git 除外設定 (`.gitignore`)**:
    *   `html/wp-config.php`, `.env`, `local_backup.sql`, `html/wp-content/uploads/` などの秘匿情報やバイナリデータは Git 管理対象外となっています。

## 5. テーマのディレクトリ構造

子テーマ (`generatepress-child`) は機能ごとにモジュール化されています：

```
generatepress-child/
├── functions.php              # エントリーポイント (21行)
├── style.css                  # カスタムCSS
├── inc/                       # モジュール化されたPHP
│   ├── security.php           # セキュリティ設定 (18行)
│   ├── firebase.php           # Firebase Storage連携 (131行)
│   ├── admin-audio-upload.php # 管理画面UI (267行)
│   ├── admin-post-columns.php # 投稿ID列表示 (34行)
│   └── frontend-ui.php        # フロントエンドUI (371行)
├── js/                        # JavaScript
│   ├── player.js              # オーディオプレイヤー
│   ├── smart-header.js        # スマートヘッダー
│   ├── theme-switcher.js      # ダークモード切替
│   └── infinite-scroll.js     # 無限スクロール
├── vendor/                    # Composer依存関係 (gitignore)
├── composer.json              # Composer設定
├── composer.lock              # 依存関係ロック
└── {project}-firebase-credentials.json  # Firebase認証情報 (gitignore)
```

### モジュール化のメリット

- **保守性向上**: 機能ごとにファイルが分離され、修正箇所が明確
- **可読性向上**: 元の899行のfunctions.phpが21行のエントリーポイントに
- **テスト容易性**: 各モジュールが独立しており、単体テストが可能
- **チーム開発**: 複数人での同時編集時のコンフリクトを軽減

## 6. 主な操作コマンド (Tips)

すべての操作は、VS Code の統合ターミナル（コンテナ内部）から実行します。

### WordPress 関連 (WP-CLI)
```bash
# データベース接続確認
wp db check

# データベースのエクスポート
wp db export local_backup.sql

# データベースのインポート
wp db import local_backup.sql

# ユーザー一覧
wp user list

# テーマのPHPシンタックスチェック
php -l /var/www/html/wp-content/themes/generatepress-child/functions.php
php -l /var/www/html/wp-content/themes/generatepress-child/inc/*.php
```

### Firebase 関連
```bash
# ログイン (初回のみ)
firebase login --no-localhost

# デプロイ (Storage ルールなど)
firebase deploy

# Storageルールのテスト
firebase emulators:start --only storage
```

### Composer 関連
```bash
# 依存関係のインストール
cd /var/www/html/wp-content/themes/generatepress-child
composer install

# 依存関係の更新
composer update

# オートローダーの再生成
composer dump-autoload
```

## 6. 本番環境仕様

### 6.1 サーバー環境（Xserver）
*   **PHP バージョン**: 8.3.21
*   **Composer バージョン**: 2.5.8
*   **アクセス権限**: 共有レンタルサーバー（ルート権限なし）
*   **SSH接続**: 利用可能
*   **WP-CLI**: `/usr/bin/wp` としてインストール済み

### 6.2 本番環境での Composer 利用
*   **パッケージインストール先**: ユーザー領域（`wp-content/themes/generatepress-child/vendor/`）
*   **権限**: ユーザー権限で完結（システムレベルのインストール不要）
*   **デプロイフロー**:
    1. ローカルで開発・テスト
    2. `composer.json` と `composer.lock` を Git にコミット
    3. GitHub Actions で本番デプロイ
    4. 本番サーバーで `composer install --no-dev --optimize-autoloader` を実行

## 7. Firebase 連携セットアップ

### 7.1 概要
WordPress管理画面から音声ファイルをアップロードすると、自動的にFirebase Storageへ転送され、カスタムフィールドにFirebaseの公開URLが設定される仕組みを実装します。

### 7.2 必要な準備

#### A. Firebase プロジェクトの作成
1. [Firebase Console](https://console.firebase.google.com/) にアクセス
2. 「プロジェクトを追加」をクリック
3. プロジェクト名を入力（例: `v-ism-plusknasy`）
4. Google Analytics は任意（推奨: 無効）
5. プロジェクト作成完了

#### B. Firebase Storage の有効化
1. Firebase Console → 左メニュー「ビルド」→「Storage」
2. 「始める」をクリック
3. セキュリティルールは後で設定するため、デフォルトのまま「次へ」
4. ロケーションを選択（推奨: `asia-northeast1` 東京）
5. 「完了」をクリック

#### C. サービスアカウントキーの取得
1. Firebase Console → プロジェクト設定（歯車アイコン）
2. 「サービスアカウント」タブ
3. 「新しい秘密鍵の生成」をクリック
4. JSON ファイルがダウンロードされる
5. ファイル名を `firebase-credentials.json` にリネーム

### 7.3 ローカル環境セットアップ

#### A. composer.json の作成
```bash
# テーマディレクトリに移動
cd /workspace/html/wp-content/themes/generatepress-child/

# composer.json を作成
cat > composer.json << 'EOF'
{
    "name": "generatepress-child/firebase-integration",
    "description": "Firebase Storage integration for podcast audio files",
    "require": {
        "kreait/firebase-php": "^7.0"
    },
    "config": {
        "platform": {
            "php": "8.3"
        },
        "allow-plugins": {
            "php-http/discovery": true
        }
    }
}
EOF
```

#### B. Firebase Admin SDK のインストール
```bash
# 依存パッケージをインストール
composer install

# インストール確認
ls -la vendor/kreait/firebase-php/
```

#### C. サービスアカウントキーの配置
```bash
# ダウンロードした JSON ファイルをテーマディレクトリにコピー
# （実際のパスは環境に応じて調整）
cp ~/Downloads/your-project-xxxxx.json firebase-credentials.json

# パーミッション設定
chmod 600 firebase-credentials.json
```

#### D. .gitignore の更新
```bash
# プロジェクトルートの .gitignore に追加
cat >> /workspace/.gitignore << 'EOF'

# Firebase credentials
html/wp-content/themes/generatepress-child/firebase-credentials.json

# Composer vendor (本番で再生成)
html/wp-content/themes/generatepress-child/vendor/
EOF
```

#### E. セキュリティ設定（.htaccess）
```bash
# テーマディレクトリに .htaccess を作成
cat > .htaccess << 'EOF'
# Deny access to sensitive files
<Files "firebase-credentials.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.json">
    Order allow,deny
    Deny from all
</Files>

<Files "composer.lock">
    Order allow,deny
    Deny from all
</Files>

# Prevent direct access to vendor directory
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^vendor/ - [F,L]
</IfModule>
EOF
```

### 7.4 Firebase Storage セキュリティルールの設定

#### A. firebase.json の作成（プロジェクトルート）
```bash
cd /workspace

cat > firebase.json << 'EOF'
{
  "storage": {
    "rules": "storage.rules"
  }
}
EOF
```

#### B. storage.rules の作成
```bash
cat > storage.rules << 'EOF'
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    // 公開読み取り許可（音声ファイル配信用）
    match /{allPaths=**} {
      allow read: if true;
      allow write: if false; // WordPress側からのみアップロード
    }
    
    // podcast フォルダ配下は認証済みユーザーのみ書き込み可能
    match /podcast/{allPaths=**} {
      allow read: if true;
      allow write: if request.auth != null;
    }
  }
}
EOF
```

#### C. Firebase CLI での初期化とデプロイ
```bash
# Firebase にログイン（初回のみ）
firebase login --no-localhost

# プロジェクトを初期化
firebase use --add
# プロジェクトIDを入力し、エイリアスを設定（例: default）

# Storage ルールをデプロイ
firebase deploy --only storage
```

### 7.5 動作確認

#### A. 簡易テストコードの実行
```bash
# テーマディレクトリに移動
cd /workspace/html/wp-content/themes/generatepress-child/

# テストPHPスクリプトを作成
cat > test-firebase.php << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

$serviceAccountPath = __DIR__ . '/firebase-credentials.json';

if (!file_exists($serviceAccountPath)) {
    die("Error: firebase-credentials.json not found\n");
}

try {
    $firebase = (new Factory)
        ->withServiceAccount($serviceAccountPath);
    
    $storage = $firebase->createStorage();
    $bucket = $storage->getBucket();
    
    echo "✓ Firebase connection successful!\n";
    echo "Bucket name: " . $bucket->name() . "\n";
} catch (Exception $e) {
    echo "✗ Firebase connection failed: " . $e->getMessage() . "\n";
}
EOF

# テスト実行
php test-firebase.php

# テスト後はファイル削除
rm test-firebase.php
```

### 7.6 本番環境へのデプロイ

#### A. ローカルでの準備
```bash
# Git にコミット（vendor/ は除外）
git add composer.json composer.lock firebase.json storage.rules
git add html/wp-content/themes/generatepress-child/.htaccess
git commit -m "Add Firebase Storage integration setup"
git push origin main
```

#### B. 本番サーバーでの作業
```bash
# SSH接続
ssh your-account@your-server.xsrv.jp

# テーマディレクトリに移動
cd ~/your-domain/public_html/wp-content/themes/generatepress-child/

# Composer パッケージをインストール
composer install --no-dev --optimize-autoloader

# サービスアカウントキーを手動アップロード（SFTPなど）
# firebase-credentials.json を配置

# パーミッション設定
chmod 600 firebase-credentials.json
chmod 755 vendor/

# 動作確認（上記のテストスクリプトを実行）
```

## 8. ディレクトリ構造の補足
*   `/workspace`: コンテナ内のワークスペースルート。ホストのプロジェクトルートと同期しています。
*   `/var/www/html`: WordPress のドキュメントルート。ホストの `html/` ディレクトリと同期しています。

## 8. ディレクトリ構造の補足
*   `/workspace`: コンテナ内のワークスペースルート。ホストのプロジェクトルートと同期しています。
*   `/var/www/html`: WordPress のドキュメントルート。ホストの `html/` ディレクトリと同期しています。

**Firebase 連携後の構造**:
```
v-ism-plusknasy/
├── firebase.json              # Firebase 設定ファイル
├── storage.rules              # Storage セキュリティルール
├── html/
│   └── wp-content/
│       └── themes/
│           └── generatepress-child/
│               ├── composer.json
│               ├── composer.lock
│               ├── vendor/           # Git管理外
│               │   └── kreait/
│               │       └── firebase-php/
│               ├── firebase-credentials.json  # Git管理外
│               ├── .htaccess         # セキュリティ設定
│               └── functions.php
```

## 9. 実装状況ログ

### 7.1 プレイヤー UI の改修
*   **Dual Language Buttons**: 日本語版と英語版の2つの再生ボタンを設置。カスタムフィールド (`podcast_audio_url`, `podcast_audio_url_en`) が空の場合はボタンを無効化し "Coming Soon..." を表示する仕様に変更。
*   **レイアウト調整**: 再生ボタンの幅を固定 (200px) し、"Play" から "Now Playing" にテキストが変化した際のレイアウトシフトを防止。
*   **ボリュームアイコン**: JSによるDOM操作をやめ、CSSクラス (`.muted`) のトグルでアイコン (`volume-up`, `volume-mute`) の表示を切り替える方式に変更。アイコンサイズの変化を防ぐため。

### 7.2 フッターエリアの再構築
*   **メタ情報の削除**: 投稿下部のデフォルトのカテゴリ・コメントリンクなどを削除。
*   **SNSシェアボタン**: X (Twitter) と Facebook のシェアボタンを追加。
*   **配信プラットフォームリンク**: "Listen on" セクションを用意。
    *   *決定事項*: RSSフィードの実装および Apple/Spotify 等のボタン設置は、外部プラットフォームのアカウント準備が整うまで**延期**とする。現在はプレースホルダー `(Links coming soon)` を表示中。
    *   *削除*: PHP/CSS/JSからRSSアイコンおよび関連する記述を完全に削除。

### 9.3 レイアウト・表示の微調整
*   **メタ情報**: トップページ一覧および記事詳細にて、投稿日横の「投稿者名」を非表示に変更。
*   **コメント欄**: 記事詳細ページのコメントフォームを削除。
*   **Sticky Footer**: コンテンツ量が少ないページでもフッターが画面最下部に固定されるよう修正 (`display: flex` & `#page { flex: 1 }`)。
*   **クレジット表記**: フッターの著作権表記を `{SiteName} © {Year}+knasy` 形式に変更。
*   **プレイヤー位置調整**: コンテンツが少ないページでプレイヤーがヘッダーと被る問題を修正 (初期マージン確保)。

### 9.4 モバイル対応（2026-01-21）
*   **モバイル専用プレイヤー**: 記事詳細ページでのみ画面下部に固定表示。
*   **UI構成**: シークバー + 3ボタン（-15s / Play・Pause / +30s）。
*   **ダークモード対応**: オレンジアクセント（#ff6600）。
*   **フッター対応**: `padding-bottom: 110px` でプレイヤーと重ならないよう調整。

---
**Last Updated**: 2026-01-25
