<?php
/**
 * GeneratePress child theme functions and definitions.
 *
 * Add your custom functions here.
 */

function generatepress_child_enqueue_scripts() {
    if ( is_rtl() ) {
        wp_enqueue_style( 'generatepress-rtl', trailingslashit( get_template_directory_uri() ) . 'rtl.css' );
    }
}
add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_scripts', 100 );

/**
 * メインコンテンツの後に、PC用固定サイドバーを出力する
 * GeneratePressのフック 'generate_after_primary_content_area' を使用
 * (2カラムグリッドの右側として配置される)
 */
function generatepress_child_add_fixed_sidebar() {
    // モバイルでは非表示にする制御はCSSで行うが、HTML出力自体を制御しても良い
    ?>
    <aside class="custom-fixed-sidebar hide-on-mobile">
        <div class="sticky-player-container">
            <div class="player-track-info">
                <h3 id="player-track-title">Select an episode to play</h3>
            </div>
            
            <div class="player-progress-container">
                <span id="player-current-time" class="time-display">0:00</span>
                <input type="range" id="player-seek-bar" value="0" max="100">
                <span id="player-duration" class="time-display">0:00</span>
            </div>

            <div class="player-main-controls">
                <button id="player-btn-rewind" class="control-btn" aria-label="Rewind 15 seconds">-15s</button>
                <button id="player-btn-play" class="control-btn play-btn" aria-label="Play/Pause">▶</button>
                <button id="player-btn-forward" class="control-btn" aria-label="Forward 30 seconds">+30s</button>
            </div>

            <div class="player-sub-controls">
                <button id="player-btn-speed" class="sub-control-btn">1.0x</button>
                <div class="volume-control">
                    <span class="volume-icon">🔊</span>
                    <input type="range" id="player-volume-bar" value="100" max="100">
                </div>
                <a id="player-btn-download" href="#" class="sub-control-btn download-link" download style="display:none;">↓ DL</a>
            </div>
        </div>
        
        <div class="ad-placeholder">
            Ad Space
        </div>
    </aside>
    <?php
}
add_action( 'generate_after_primary_content_area', 'generatepress_child_add_fixed_sidebar' );

/**
 * プレイヤー用のスクリプトとスタイルを読み込む
 */
function generatepress_child_enqueue_player_scripts() {
    // プレイヤー制御用JS
    wp_enqueue_script( 'podcast-player-js', get_stylesheet_directory_uri() . '/js/player.js', array('jquery'), '1.0', true );
    
    // スマートヘッダーJS
    wp_enqueue_script( 'smart-header-js', get_stylesheet_directory_uri() . '/js/smart-header.js', array('jquery'), '1.0', true );

    // 無限スクロールJS (アーカイブ、ホーム画面のみ)
    if ( is_home() || is_archive() ) {
        wp_enqueue_script( 'infinite-scroll-js', get_stylesheet_directory_uri() . '/js/infinite-scroll.js', array('jquery'), '1.0', true );
        
        // JSに変数を渡す
        global $wp_query;
        wp_localize_script( 'infinite-scroll-js', 'infinite_scroll_params', array(
            'current_page' => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
            'max_page' => $wp_query->max_num_pages,
            'next_link' => get_next_posts_page_link( $wp_query->max_num_pages ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_player_scripts' );

/**
 * 各記事に「再生ボタン」を追加する
 * (GeneratePressのフック generate_after_entry_header を使用してタイトルの下に表示)
 */
function generatepress_child_add_play_button() {
    // カスタムフィールド 'podcast_audio_url' から音声URLを取得
    $audio_url = get_post_meta( get_the_ID(), 'podcast_audio_url', true );

    // URLが登録されていない場合はボタンを表示しない（またはデフォルトを表示）
    if ( ! $audio_url ) {
        // 開発中のみ、データがない場合のフォールバックを入れるか、
        // あるいは何も表示しないか。今回は何も表示しないこととする。
        return;
    }
    ?>
    <div class="podcast-play-button-wrapper">
        <button class="podcast-play-button" data-src="<?php echo esc_url($audio_url); ?>" data-title="<?php the_title_attribute(); ?>">
            ▶ Play Episode
        </button>
    </div>
    <?php
}
add_action( 'generate_after_entry_header', 'generatepress_child_add_play_button' );

/**
 * フッターのクレジットを完全に上書きして独自のものにする
 * generate_credits フック自体を上書き
 */
function generatepress_child_custom_credits() {
    ?>
    <span class="copyright">&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?></span>
    <?php
}

// 親テーマのデフォルト出力を削除し、独自のものを追加する
add_action( 'init', function() {
    remove_action( 'generate_credits', 'generate_add_footer_info' );
    add_action( 'generate_credits', 'generatepress_child_custom_credits' );
} );


/**
 * サイドバーを完全に制御（削除）する
 * GeneratePressのフック generate_sidebar_layout を使用して
 * 強制的に 'no-sidebar' レイアウトを適用する（ただし特定の条件の場合）
 * 
 * 今回は右サイドバーを完全に消したいので、すべてのページでサイドバーなしにするか、
 * あるいはウィジェットエリアとしての書き出しを停止する。
 */
add_filter( 'generate_sidebar_layout', function( $layout ) {
    // 独自の固定サイドバー(HTML注入)を使うため、
    // テーマ標準のサイドバー機能はOFFにする（= 1カラム扱いにする）
    // ただし、そうすると #primary の幅が100%になるので、CSS Gridでの制御とマッチするか要確認
    // 現状のCSS Gridは .site-content { display: grid; ... } なので、
    // #primary があろうがなかろうが、Gridの1カラム目に入る要素として扱われるはず。
    return 'no-sidebar';
 } );





