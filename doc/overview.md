# WPSite Podcast Project - 開発ドキュメント

## 1. プロジェクト概要
本プロジェクトは、WordPressを中核としたポッドキャスト配信プラットフォームの構築・運用を目的とする。
レンタルサーバー（Xserver）の安定性と、Firebaseのスケーラビリティを組み合わせた「ハイブリッド構成」を採用し、保守性の高いモダンな開発環境を実現する。

## 2. システムアーキテクチャ

### 2.1 ハイブリッド配信モデル
サイトの制御（HTML/PHP/RSS）はXserverが行い、トラフィック負荷の高い音声アセットはFirebaseから配信する。

* **メインホスト (Xserver)**:
    * WordPressの実行環境（PHP 8.3 / MariaDB 10.5）。
    * テーマ（GeneratePress）によるフロントエンド制御。
    * Podcast用RSSフィードの生成・配信。
* **アセットホスト (Firebase Storage)**:
    * ポッドキャスト音声ファイル（.mp3）の格納。
    * Googleのグローバルネットワーク（CDN）を利用した高速配信。
    * サーバー転送量の削減と、再生時レスポンスの向上。

### 2.2 開発基盤
* **Docker / Dev Container**: ローカルでの環境差異をなくし、ツール一式（WP-CLI, Firebase CLI, GH CLI）を内包。
* **開発環境仕様**:
    * **Webサーバポート**: `8081`
    * **コンテナ内パス**: プロジェクトルートは `/workspace` にマウントされ、WordPressルートは `/var/www/html` (ホストの `html/`) にマウントされる。
    * **環境変数管理**: `.env` ファイルによるDB接続情報の管理。
    * ※ 詳細は [development.md](development.md) を参照のこと。
* **GitHub Actions**: コードのプッシュによる本番環境への自動デプロイ（CI/CD）。

## 4. 実装機能一覧

### 4.1 フロントエンド機能
*   **無限スクロール (Archive/Home)**:
    *   画面下部へのスクロールで自動的に次ページの投稿をフェッチして追加。
    *   開発環境(localhost)でのCORS制約を回避する相対パスリクエスト処理を実装済み。
*   **スマートヘッダー**:
    *   スクロール方向に応じてヘッダーの表示/非表示をアニメーション制御。
    *   コンテンツ閲覧領域を最大化。
*   **ダークモード対応 (Theme Switcher)**:
    *   OS設定、またはユーザーによる手動切り替え（ヘッダー内 SVG アイコンボタン）に対応。
    *   テーマ設定は `localStorage` に保存され、再訪問時も維持される。
    *   カラープロファイル:
        *   Light: 白背景 / 青アクセント (#1da1f2) / ダークグレー文字
        *   Dark: ダークグレー背景 (#1e1e1e) / オレンジアクセント (#ff6600) / 白文字

### 4.2 ポッドキャストプレイヤー

#### PC版プレイヤー
*   **Sticky Sidebar Player**: PC画面右側に常駐する高機能Webプレイヤー。
*   **再生制御**:
    *   Play/Pause, Seek (-15s/+30s), Speed (1.0x-2.0x), Volume (Mute対応)。
    *   ダウンロードボタン（音源ロード時のみ有効）。
*   **インタラクション**:
    *   記事内の「Ep. in Japanese」「Ep. in English」ボタンクリックでプレイヤーにトラックをロード＆再生開始。
    *   再生中の記事ボタンには「Waveアニメーション（波形ビジュアライザー）」を表示。
    *   再生中のボタンを再度クリックすると「一時停止」するトグル動作。
    *   二重再生防止ロジック（常に1つのオーディオのみアクティブ）。

#### モバイル版プレイヤー
*   **Sticky Bottom Player**: 記事詳細ページ（single post）でのみ表示される画面下部固定プレイヤー。
*   **表示条件**: 
    *   記事詳細ページのみ表示（アーカイブページ、ホームでは非表示）。
    *   PC版プレイヤーは非表示。
*   **UI構成**:
    *   シークバー（時間表示なし、シンプルデザイン）。
    *   3つのコントロールボタン: -15s / Play/Pause / +30s。
*   **スタイリング**:
    *   `z-index: 1000` でコンテンツの上に常時表示。
    *   フッターに `padding-bottom: 110px` を追加し、プレイヤーで隠れないように配慮。
*   **デフォルト動作**:
    *   ページ読み込み時に日本語音声を自動セット。
    *   記事内の「Ep. in Japanese」「Ep. in English」ボタンと連動。
*   **再生終了時の挙動**:
    *   シークバーを100%の位置に留める（0%にリセットしない）。

## 5. ディレクトリ構造

```text
wp-site/
├── .devcontainer/    # 開発環境の定義（Dockerfile, devcontainer.json）
├── .github/          # CI/CD (Actions) のワークフロー定義
├── doc/              # プロジェクトドキュメント
│   ├── overview.md       # 本ドキュメント
│   └── development.md    # 開発環境構築ガイド
├── html/             # WordPress 実行ディレクトリ（Xserver同期対象）
│   ├── wp-content/
│   │   ├── themes/   # GeneratePress 及び 子テーマ
│   │   └── plugins/  # ポッドキャスト関連プラグイン
│   └── wp-config.php # 環境変数を優先読み込みするように調整済み
├── wp-cli.yml        # WP-CLI 設定ファイル（パス解決・SSL設定等）
├── wp-config-docker.php # Docker環境用 wp-config (URL固定設定等)
├── firebase.json     # Firebase 設定ファイル
├── local_backup.sql  # ローカル開発用データベース・ダンプ (Git管理外)
├── compose.yml       # Docker Compose 構成定義
├── .env              # ローカル環境変数（Git管理外）
└── README.md         # プロジェクトのREADME
```

## 4. デプロイメント・フロー (CI/CD)

本プロジェクトでは、手動によるファイル転送（FTP等）を廃止し、GitHub Actionsを利用した以下の自動化フローを採用する。

1.  **Local Development**: Dev Container内でテーマのコードや設定を修正・検証。
2.  **Git Push**: GitHubの `main` ブランチへ変更をプッシュ。
3.  **GitHub Actions 起動**: 以下の2系統のデプロイが自動実行される。
    * **Xserver同期**: `html/wp-content/themes/` 等の差分ファイルを、`rsync`（SSH経由）または `lftp` を用いて本番サーバーへ自動転送。
    * **Firebaseデプロイ**: `firebase.json` やセキュリティルールに変更があれば、`firebase-tools` によりデプロイを実行。

## 5. 運用ガイドライン

### 5.1 メディア管理（ポッドキャスト音声）
* **アップロード**: 音声ファイル（.mp3）はWordPressのメディアライブラリではなく、Firebase Storageへ直接アップロードする。
* **紐付け**: WordPressの投稿（Seriously Simple Podcasting等）からは、Firebase Storageの「公開URL」を参照して配信を行う。
* **メリット**: Xserverの転送量制限を回避し、GoogleのCDNによる高速な音声再生を実現する。

### 5.2 データベースとコンテンツの同期
* **正本管理**: 記事内容やコメントなどのDBデータは本番環境（Xserver）を正とする。
* **ローカル同期**: 本番の最新状態をローカルで確認したい場合は、`All-in-One WP Migration` 等を用いて本番からローカルへデータをインポートする。

### 5.3 テーマとプラグインの管理
* **テーマ**: GeneratePressの子テーマ（Child Theme）にてカスタマイズを行い、ソースコードはGitで管理する。
* **プラグイン**: 基本的に管理画面からインストールするが、プロジェクト固有の重要なプラグイン構成は `README.md` に記録し、必要に応じて Git 管理下に置く。



---
**Last Updated**: 2026-01-21