# テーマ構成・デザイン仕様書

## 1. デザインコンセプト
*   **ターゲット**: ポッドキャストリスナー
*   **スタイル**: SNS風カード型UI（GeneratePressベース）
*   **特徴**:
    *   コンテンツを「カード」として視覚的に独立させる。
    *   画像を効果的に配置し、視認性を高める。
    *   ポッドキャスト再生への導線を短縮する。

## 2. 技術スタック
*   **親テーマ**: [GeneratePress](https://wordpress.org/themes/generatepress/)
*   **子テーマ**: `generatepress-child`
    *   カスタマイズは基本的に子テーマに対して行う。
*   **CSS/JS**: 子テーマ内の `style.css` および `functions.php` で管理。
*   **ポッドキャストプラグイン**: Seriously Simple Podcasting (想定)

## 3. レイアウト仕様 (確定)

### 3.1 投稿一覧 (アーカイブページ)
Twitter (X) 風の「縦スクロール・タイムライン」をベースにしつつ、PCでは CSS Grid を用いた独自の2カラム構成を採用。

*   **PCレイアウト構成**:
    *   **コンテナ**: CSS Grid による2カラム (`minmax(0, 1fr) 300px`)。
    *   **メインエリア (左側・1fr)**:
        *   カード型リストを表示。
        *   **無限スクロール**: jQuery.get を用いた簡易実装済み。開発環境(CORS)対策として相対パス変換処理を含む。
    *   **サイドエリア (右側・300px固定)**:
        *   Hook: `generate_after_primary_content_area` を使用して注入。
        *   **要素**:
            1.  **高機能プレイヤー (Sticky)**: `position: sticky` で画面上部に固定。
            2.  **広告枠**: プレイヤーの下に**1枠**配置（Fix固定）。
        *   **連動動作**:
            *   「スマートヘッダー」の動きに連動して、サイドバーの `top` 位置が自動調整されるJS実装済み。
        *   **DOM制御**: GeneratePress標準のサイドバー（ウィジェットエリア）はフックでHTML生成ごと無効化済み。

### 3.2 ヘッダー・フッター仕様 (New)
*   **スマートヘッダー**:
    *   `position: fixed` で常時上部に配置。
    *   **下スクロール**: アニメーションして画面外へ隠れる。
    *   **上スクロール**: 即座に再表示される。
    *   右サイドバー（プレイヤー）もこの動きに合わせて上下にスライドし、重なりを防ぐ。

*   **フッター**:
    *   GeneratePress標準のクレジット表記 (`Built with ...`) は非表示。
    *   カスタムクレジット (`{SiteName} © {Year}+knasy`) を表示。
    *   **Sticky Footer**: コンテンツが少ないページでも最下部に固定表示するよう設定済み。

*   **カード内の要素 (タイムライン)**:
    1.  **タイトル**: 記事タイトル。
    2.  **投稿日時**: メタ情報。(投稿者名は非表示)
    3.  **再生ボタン (Play Episode) - Dual Buttons**:
        *   **日本語版(JP)** と **英語版(EN)** の2つのボタンを並列配置。
        *   カスタムフィールド (`podcast_audio_url`, `podcast_audio_url_en`) を参照し、空の場合は "Coming Soon..." として非活性化(disable)する。
        *   クリックすると、右側の「固定プレイヤー」で再生が開始される。
    4.  **エントリーフッター (Action Bar)**:
        *   標準のメタ情報（カテゴリ、タグ、コメント）を排除。記事詳細ページのコメント欄も削除。
        *   **Listen on**: Apple Podcasts, Spotify 等へのリンク（現在は準備中プレースホルダー）。RSS機能は削除。
        *   **Share**: X (Twitter), Facebook のシェアボタンのみをシンプルに配置。


## 4. UI/UXデザイン仕様

### 4.1 テーマ切り替え (Dark/Light Mode)
ユーザーの環境設定および手動操作によるテーマ切り替えを実装。

*   **切り替えスイッチ**:
    *   ヘッダーメニュー内の「サンプルページ」項目の位置に、SVGアイコン（☀️/🌙）ボタンを配置。
    *   `localStorage` に設定を保存 (`theme: 'dark' | 'light'`)。
*   **配色設計**:
    *   **Light Theme**:
        *   Background: `#ffffff`
        *   Text: `#333333`, `#666666`
        *   Accent: **Twitter Blue** (`#1da1f2`)
        *   Card Style: 白抜き、ドロップシャドウ付き。
    *   **Dark Theme**:
        *   Background: `#121212` (Body), `#1e1e1e` (Components)
        *   Text: `#e0e0e0`, `#aaaaaa`
        *   Accent: **Orange** (`#ff6600`) - 黒背景での視認性と暖かみを重視。
        *   Card Style: 明度操作によるレイヤー表現（シャドウ＋ボーダー）。

### 4.2 アイコン・ビジュアル
*   **SVG採用**: FontAwesome等の外部フォントに依存せず、インラインSVGを使用して軽量化・レスポンシブ化。
    *   Play/Pause, Volume/Mute, Speed, Download。
    *   Theme Toggle (Sun/Moon)。
*   **Wave Animation**:
    *   再生中の記事ボタン内に、CSS Keyframes (`@keyframes wave`) を用いた4本のバーが動くオーディオスペクトラム風アニメーションを表示。
    *   JSによるDOM注入 (`div.wave-bar`) で制御。

### 4.3 プレイヤー詳細仕様
*   **機能ボタン**:
    *   **Rewind/Forward**: -15秒 / +30秒 の固定スキップ。
    *   **Speed**: 1.0x, 1.25x, 1.5x, 2.0x のループ切り替え。
    *   **Volume**: スライダー + ミュート切り替えボタン。
    *   **Download**: 音源ロード完了時のみ `disabled` が解除される安全設計。
*   **状態管理**:
    *   非同期(`Promise`)による再生処理のエラーハンドリング（連打時の `AbortError` 対策）。
    *   グローバルロックフラグではなく、イベントリスナーの重複登録解除 (`.off().on()`) による多重発火防止を採用。

### 4.4 モバイル最適化仕様
*   **ヘッダーUI刷新**:
    *   ハンバーガーメニューを撤廃し、**テーマ切り替えボタン（ダークモードトグル）**のみを右上に配置。
    *   レイアウト崩れを防ぐため、CSS Flexbox (`flex-direction: row`, `flex-wrap: nowrap`) を強制適用し、ロゴとボタンを常に1行に収める。
    *   ヘッダー高さを `60px` に固定し、ページ遷移時のガタつきを排除。
*   **広告表示 (Mobile Ad)**:
    *   投稿一覧（インデックスページ）記事間にインフィード広告を自動挿入（1件目の後、以降3件ごと）。
    *   記事詳細ページでは再生ボタンの直後に1枠表示。
    *   **パララックス効果**: 記事スクロール時に背景画像が固定される視差効果(`clip-path: inset(0)`)を実装。
*   **横スクロール対策**:
    *   `position: sticky`の動作を維持しつつ横スクロールを防ぐため、`overflow-x: clip` を採用。`hidden` ではなく `clip` を使うことで、PC版の追従プレイヤーの機能を損なわないように設計。
*   **モバイル専用プレイヤー**:
    *   記事詳細ページでのみ画面下部に固定表示される専用プレイヤーを実装済み。
    *   シンプルなUI構成: シークバー + 3ボタン（-15s / Play・Pause / +30s）。
    *   `z-index: 1000` でコンテンツの上に常時表示。
    *   PC版プレイヤー（サイドバー）はモバイルでは非表示。
    *   フッターに `padding-bottom: 110px` を追加し、プレイヤーで隠れないように配慮。

## 5. コード構造とモジュール化

### 5.1 functions.php のモジュール化

保守性向上のため、元の899行の `functions.php` を機能ごとに分割し、21行のエントリーポイントに集約：

```php
// functions.php (21行)
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/firebase.php';
require_once __DIR__ . '/inc/admin-audio-upload.php';
require_once __DIR__ . '/inc/admin-post-columns.php';
require_once __DIR__ . '/inc/frontend-ui.php';
```

### 5.2 モジュール構成

#### inc/security.php (18行)
- XML-RPC無効化 (`xmlrpc_enabled` フィルター)
- WordPressバージョン情報の非表示 (`wp_generator` 削除)

#### inc/firebase.php (131行)
- `get_firebase_storage()`: Bucketインスタンス取得（静的キャッシュ）
- `upload_audio_to_firebase()`: ファイルアップロード処理
- `get_next_audio_version()`: バージョン番号の自動取得

#### inc/admin-audio-upload.php (267行)
- `add_podcast_audio_meta_box()`: メタボックス登録
- `render_podcast_audio_meta_box()`: 日英ファイル入力UI
- `save_podcast_audio_meta_box()`: 投稿保存時のアップロード処理
- `ajax_delete_podcast_audio_url()`: URL削除AJAX処理
- `process_audio_upload()`: ファイルバリデーション

#### inc/admin-post-columns.php (34行)
- 投稿一覧にID列を追加（Firebase管理用）
- ソート可能な列として実装

#### inc/frontend-ui.php (371行)
- `generatepress_child_add_fixed_sidebar()`: PC固定サイドバープレイヤー
- `generatepress_child_add_mobile_player()`: モバイルスティッキープレイヤー
- `generatepress_child_add_play_button()`: 再生ボタンUI
- `generatepress_child_add_social_footer()`: SNSシェアボタン
- スマートヘッダー・テーマ切り替え連携

### 5.3 モジュール化のメリット

- **可読性向上**: 各ファイルが単一責任を持ち、目的が明確
- **保守性向上**: 修正箇所が特定しやすく、影響範囲が限定的
- **テスト容易性**: 各モジュールが独立しており、単体テストが可能
- **チーム開発**: 複数人での同時編集時のコンフリクトを軽減
- **再利用性**: 他プロジェクトへの機能移植が容易

## 6. 課題と今後の展望
*   **SPA遷移**: ページ遷移時にオーディオが途切れる問題は未解決。将来的にはBarba.js導入等のSPA化を検討。
*   **Firebase Storage クリーンアップ**: 古いバージョンの音声ファイルは手動削除が必要。自動クリーンアップスクリプトの検討。
