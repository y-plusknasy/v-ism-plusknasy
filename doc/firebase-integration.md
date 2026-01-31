# Firebase Storage 連携ガイド

## 概要

WordPress管理画面から音声ファイルをアップロードすると、自動的にFirebase Storageへ転送され、Firebaseの公開URLがカスタムフィールドに保存される仕組みです。

### システムフロー

```
WordPress管理画面 (ファイル選択)
   ↓
WordPress一時保存
   ↓
PHP + Firebase Admin SDK
   ↓
Firebase Storage へアップロード
   ↓
公開URL取得
   ↓
カスタムフィールドに保存
   ↓
プレイヤーで再生
```

### メリット

- **管理の一元化**: WordPress管理画面のみで完結、Firebase Consoleを直接操作する必要なし
- **転送量削減**: 音声ファイルはFirebase CDN経由で配信、Xserverの転送量を節約
- **高速配信**: Googleのグローバルネットワークによる高速・安定配信
- **バージョン管理**: Firebase Storageの実データから自動的にバージョン番号を取得、削除してもバックアップが残る設計

---

## ファイル構造とバージョン管理

### ディレクトリ構造

Firebase Storage内のファイルは以下のパターンで保存されます：

```
audio/
  └── post-{投稿ID}/
      ├── ja-v1-1234567890.mp3
      ├── ja-v2-1234567891.mp3
      └── en-v1-1234567892.mp3
```

**パス形式**: `audio/post-{投稿ID}/{lang}-v{version}-{timestamp}.mp3`

- `{投稿ID}`: WordPressの投稿ID (例: `123`)
- `{lang}`: 言語コード (`ja` または `en`)
- `{version}`: バージョン番号 (1から始まる連番)
- `{timestamp}`: Unixタイムスタンプ (衝突回避用)

### バージョン管理の仕組み

#### 自動バージョン採番

新しいファイルをアップロードすると、システムが自動的に次のバージョン番号を決定します：

1. **Firebase Storageをクエリ**: `audio/post-{ID}/{lang}-v` で始まるファイルを検索
2. **最大バージョンを抽出**: 正規表現でバージョン番号を抽出し、最大値を取得
3. **インクリメント**: `max_version + 1` を新しいバージョン番号として使用

```php
function get_next_audio_version($post_id, $lang) {
    $bucket = get_firebase_storage();
    $prefix = 'audio/post-' . $post_id . '/' . $lang . '-v';
    $objects = $bucket->objects(['prefix' => $prefix]);
    
    $max_version = 0;
    foreach ($objects as $object) {
        if (preg_match('/' . preg_quote($lang, '/') . '-v(\d+)-/', $object->name(), $matches)) {
            $version = intval($matches[1]);
            if ($version > $max_version) {
                $max_version = $version;
            }
        }
    }
    
    return $max_version + 1;
}
```

#### URL削除の挙動

管理画面の「このURLを削除」ボタンは、**WordPressのカスタムフィールドからURLを削除するのみ**で、Firebase Storage上のファイルは削除しません。

**メリット**:
- 誤操作時の復旧が可能
- 過去バージョンのバックアップとして保持
- Firebase Consoleから手動で削除可能

**デメリット**:
- Storage容量を消費し続ける（定期的な手動クリーンアップが必要）

---

## 環境要件

### 本番環境（Xserver）
- PHP 8.3.21 以上
- Composer 2.5.8 以上
- SSH接続可能
- `curl`, `json`, `mbstring` 拡張モジュール（標準で有効）

### Firebase
- Firebase プロジェクト
- Storage 有効化
- サービスアカウントキー（JSON）

---

## セットアップ手順

### Phase 1: Firebase プロジェクト準備

#### 1. Firebase プロジェクト作成
1. [Firebase Console](https://console.firebase.google.com/) にアクセス
2. 「プロジェクトを追加」→ プロジェクト名を入力
3. Google Analytics は無効化（任意）

#### 2. Storage 有効化
1. Firebase Console → 「Storage」
2. 「始める」をクリック
3. ロケーション: `asia-northeast1`（東京）を選択

#### 3. サービスアカウントキー取得
1. プロジェクト設定（歯車アイコン）→「サービスアカウント」
2. 「新しい秘密鍵の生成」をクリック
3. ダウンロードしたJSONファイルを `firebase-credentials.json` にリネーム

### Phase 2: ローカル環境セットアップ

#### 1. Composer 初期化

```bash
cd /workspace/html/wp-content/themes/generatepress-child/

# composer.json を作成（既に用意済み）
composer install
```

**確認事項**:
- `vendor/kreait/firebase-php/` が生成されること
- `vendor/autoload.php` が存在すること

#### コード構造 (モジュール化)

子テーマは保守性向上のため、機能ごとにモジュール化されています：

**functions.php** (21行 - エントリーポイント):
```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/firebase.php';
require_once __DIR__ . '/inc/admin-audio-upload.php';
require_once __DIR__ . '/inc/admin-post-columns.php';
require_once __DIR__ . '/inc/frontend-ui.php';
```

**inc/firebase.php** (131行):
- `get_firebase_storage()`: Bucketインスタンス取得（静的キャッシュ）
- `upload_audio_to_firebase()`: ファイルアップロード処理
- `get_next_audio_version()`: バージョン番号の自動取得

**inc/admin-audio-upload.php** (267行):
- `add_podcast_audio_meta_box()`: メタボックス登録
- `render_podcast_audio_meta_box()`: 日英ファイル入力UI
- `save_podcast_audio_meta_box()`: 投稿保存時のアップロード処理
- `ajax_delete_podcast_audio_url()`: URL削除AJAX処理
- `process_audio_upload()`: ファイルバリデーション

**inc/admin-post-columns.php** (34行):
- 投稿一覧にID列を追加（Firebase管理用）

**inc/security.php** (18行):
- XML-RPC無効化
- WordPressバージョン情報の非表示

**inc/frontend-ui.php** (371行):
- PC固定サイドバープレイヤー
- モバイルスティッキープレイヤー
- 再生ボタン、フッターカスタマイズ
- スマートヘッダー連携

#### 2. 認証情報の配置

```bash
# サービスアカウントキーをテーマディレクトリに配置
cp ~/Downloads/your-firebase-key.json firebase-credentials.json

# パーミッション設定
chmod 600 firebase-credentials.json
```

#### 3. セキュリティ設定

`.gitignore` に追加（プロジェクトルート）:
```
html/wp-content/themes/generatepress-child/firebase-credentials.json
html/wp-content/themes/generatepress-child/vendor/
```

`.htaccess` を作成（テーマディレクトリ）:
```apache
<Files "firebase-credentials.json">
    Order allow,deny
    Deny from all
</Files>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^vendor/ - [F,L]
</IfModule>
```

#### 4. Firebase Storage ルール設定

プロジェクトルートに `storage.rules` と `firebase.json` を作成（既に用意済み）

```bash
cd /workspace

# Firebase CLI でログイン
firebase login --no-localhost

# プロジェクトを選択
firebase use --add

# Storage ルールをデプロイ
firebase deploy --only storage
```

#### 5. 動作確認

```bash
cd /workspace/html/wp-content/themes/generatepress-child/

# テストスクリプトを実行（test-firebase.php を作成して実行）
php test-firebase.php
```

**期待される出力**:
```
✓ Firebase connection successful!
Bucket name: your-project.appspot.com
```

### Phase 3: 本番環境デプロイ

#### 1. ローカルでコミット

```bash
git add composer.json composer.lock firebase.json storage.rules
git add html/wp-content/themes/generatepress-child/.htaccess
git add html/wp-content/themes/generatepress-child/functions.php
git commit -m "Add Firebase Storage integration"
git push origin main
```

#### 2. 本番サーバーで作業

```bash
# SSH接続
ssh your-account@your-server.xsrv.jp

# テーマディレクトリに移動
cd ~/your-domain/public_html/wp-content/themes/generatepress-child/

# Composer パッケージをインストール
composer install --no-dev --optimize-autoloader

# パーミッション確認
chmod 755 vendor/
```

#### 3. 認証情報の配置（手動）

- SFTP経由で `firebase-credentials.json` を本番サーバーのテーマディレクトリにアップロード
- パーミッション設定: `chmod 600 firebase-credentials.json`

---

## 使い方

### 音声ファイルのアップロード

1. WordPress管理画面 → 投稿 → 編集
2. 「Podcast Audio Files」メタボックスが表示される
3. 日本語版・英語版それぞれのMP3ファイルを選択
4. 「更新」をクリック
5. 自動的にFirebase Storageへアップロードされ、URLが保存される

### 確認方法

- 記事編集画面: メタボックスに現在のURLが表示される
- Firebase Console: Storage → Files でアップロードされたファイルを確認
- フロントエンド: 記事詳細ページで「Ep. in Japanese」ボタンが有効化される

### 削除方法

1. 記事編集画面のメタボックスで「削除」ボタンをクリック
2. URLがWordPressから削除される（Firebase上のファイルは残る）

---

## 注意事項

### ファイル制限

- **対応形式**: MP3 のみ
- **最大サイズ**: 100MB（変更可能）
- **ファイル名**: 自動生成（`podcast/post-{ID}-{lang}-{timestamp}.mp3`）

### セキュリティ

- **サービスアカウントキー**: Git管理対象外、`.htaccess` でアクセス禁止
- **Storage ルール**: 読み取りは公開、書き込みは認証必須
- **vendor/ ディレクトリ**: 直接アクセス禁止

### パフォーマンス

- **PHP メモリ**: 大容量ファイル対応のため、必要に応じて `memory_limit` を調整
- **タイムアウト**: アップロード時間が長い場合、`max_execution_time` を調整
- **同時アップロード**: 1ファイルずつ処理（複数同時は未対応）

### Firebase 制限

- **無料枠**: 1GB ストレージ / 月、10GB 転送量 / 月
- **クォータ超過**: 有料プランへのアップグレードが必要
- **削除**: WordPress側のURL削除では、Firebase上のファイルは削除されない（手動削除が必要）

---

## トラブルシューティング

### "Firebase connection failed"
**原因**:
- `firebase-credentials.json` のパスが間違っている
- JSONファイルが破損している

**解決方法**:
- ファイルパスを確認: `ls -la firebase-credentials.json`
- パーミッション確認: `chmod 600 firebase-credentials.json`

### "Class not found" エラー
**原因**:
- Composer パッケージが正しくインストールされていない

**解決方法**:
```bash
composer install --no-dev
ls -la vendor/kreait/firebase-php/
```

### アップロード失敗
**原因**:
- PHPの設定制限
- ファイルサイズ超過

**解決方法**:
```bash
# PHP設定確認
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"

# WordPress設定確認（wp-config.php）
define('WP_MEMORY_LIMIT', '256M');
```

### 本番環境で動作しない
**原因**:
- 環境差異
- 認証情報の配置ミス

**解決方法**:
1. エラーログ確認: `tail -f ~/your-domain/log/error_log`
2. WordPress デバッグ有効化: `wp-config.php` に `define('WP_DEBUG', true);`
3. テストスクリプトで接続確認

---

## 今後の拡張予定

- [ ] 複数ファイル一括アップロード
- [ ] アップロード進捗表示（プログレスバー）
- [ ] Firebase上のファイル削除機能（WordPress側の削除ボタンと連動）
- [ ] RSS フィード対応（Seriously Simple Podcasting連携）
- [ ] 使用量モニタリング機能

---

**作成日**: 2026-01-25  
**更新日**: 2026-01-25

