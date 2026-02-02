<?php
/**
 * Frontend UI Components
 * 
 * PC固定サイドバー、モバイルプレイヤー、再生ボタン、フッターなどの
 * フロントエンド表示に関する機能
 */

/**
 * ヘッダーのサイトタイトル下にbloginfo（説明文）を小さく表示
 */
add_filter( 'generate_site_branding_output', function( $output ) {
    $title = get_bloginfo( 'name' );
    $description = get_bloginfo( 'description' );
    
    // タイトルが表示されている場合のみ、その下に説明文を追加
    if ( ! empty( $title ) && ! empty( $description ) ) {
        $output = str_replace(
            '</div>',
            '<p class="site-subtitle" style="margin: 0; font-size: 0.85em; color: #666; font-weight: 300;">' . esc_html( $description ) . '</p></div>',
            $output
        );
    }
    
    return $output;
} );

/**
 * メインコンテンツの後に、PC用固定サイドバーを出力する
 * GeneratePressのフック 'generate_after_primary_content_area' を使用
 * (2カラムグリッドの右側として配置される)
 */
function generatepress_child_add_fixed_sidebar() {
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
                <button id="player-btn-play" class="control-btn play-btn" aria-label="Play/Pause">
                    <!-- Play Icon SVG -->
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button id="player-btn-forward" class="control-btn" aria-label="Forward 30 seconds">+30s</button>
            </div>

            <div class="player-sub-controls">
                <button id="player-btn-speed" class="sub-control-btn">1.0x</button>
                <div class="volume-control">
                    <span class="volume-icon" title="Mute/Unmute">
                        <!-- Volume On Icon -->
                        <svg class="icon-on" viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                        </svg>
                        <!-- Volume Off (Muted) Icon -->
                        <svg class="icon-off" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" style="display: none;">
                            <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73 4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                        </svg>
                    </span>
                    <input type="range" id="player-volume-bar" value="100" max="100">
                </div>
            </div>

            <!-- Download Area -->
            <div class="player-download-area">
                <a id="player-btn-download" href="#" class="download-link disabled" download>
                    <span class="icon">↓</span> Download Episode
                </a>
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
 * モバイル用スティッキープレイヤーを出力する
 * (記事詳細ページのみ、フッター前に配置)
 */
function generatepress_child_add_mobile_player() {
    // 記事詳細ページでのみ表示
    if ( ! is_single() ) {
        return;
    }
    ?>
    <div class="mobile-sticky-player">
        <div class="mobile-player-progress-container">
            <input type="range" id="mobile-player-seek-bar" min="0" max="100" value="0" step="0.1">
        </div>

        <div class="mobile-player-controls">
            <button id="mobile-player-btn-rewind" class="mobile-control-btn" aria-label="Rewind 15 seconds">-15s</button>
            <button id="mobile-player-btn-play" class="mobile-control-btn mobile-play-btn" aria-label="Play/Pause">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </button>
            <button id="mobile-player-btn-forward" class="mobile-control-btn" aria-label="Forward 30 seconds">+30s</button>
        </div>
    </div>
    <?php
}
add_action( 'generate_before_footer', 'generatepress_child_add_mobile_player' );

/**
 * プレイヤー用のスクリプトとスタイルを読み込む
 */
function generatepress_child_enqueue_player_scripts() {
    // プレイヤー制御用JS
    wp_enqueue_script( 'podcast-player-js', get_stylesheet_directory_uri() . '/js/player.js', array('jquery'), '1.0', true );
    
    // スマートヘッダーJS
    wp_enqueue_script( 'smart-header-js', get_stylesheet_directory_uri() . '/js/smart-header.js', array('jquery'), '1.0', true );

    // テーマ切り替えJS
    wp_enqueue_script( 'theme-switcher-js', get_stylesheet_directory_uri() . '/js/theme-switcher.js', array('jquery'), '1.0', true );

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
    // カスタムフィールドから音声URLを取得
    $url_jp = get_post_meta( get_the_ID(), 'podcast_audio_url', true );
    $url_en = get_post_meta( get_the_ID(), 'podcast_audio_url_en', true );

    // ヘルパー関数: ボタン出力
    $render_button = function($url, $label, $lang) {
        if ( $url ) {
            ?>
            <button class="podcast-play-button" 
                    data-src="<?php echo esc_url($url); ?>" 
                    data-title="<?php the_title_attribute(); ?>"
                    data-lang="<?php echo esc_attr($lang); ?>"
                    data-original-text="<?php echo esc_attr($label); ?>">
                <span class="icon-container">▶</span> 
                <span class="text-container"><?php echo esc_html($label); ?></span>
            </button>
            <?php
        } else {
            ?>
            <button class="podcast-play-button disabled" disabled>
                <span class="text-container">Coming Soon...</span>
            </button>
            <?php
        }
    };

    ?>
    <div class="podcast-play-button-wrapper">
        <?php 
        $render_button($url_jp, 'Ep. in Japanese', 'ja');
        $render_button($url_en, 'Ep. in English', 'en');
        ?>
    </div>
    <?php
    
    // モバイル用広告枠を再生ボタンの直後に挿入（記事詳細ページのみ）
    if ( is_single() && wp_is_mobile() ) {
        ?>
        <div class="mobile-ad-slot single-post-ad" style="margin: 20px -20px;">
            <div class="mobile-ad-content" style="background-image: url('https://placehold.co/600x400/333333/FFFFFF/png?text=Ad+Space');">
                <a href="#" class="ad-overlay-text" target="_blank">Sponsored Content</a>
            </div>
        </div>
        <?php
    }
}
add_action( 'generate_after_entry_header', 'generatepress_child_add_play_button' );

/**
 * フッターのクレジットを完全に上書きして独自のものにする
 */
function generatepress_child_custom_credits() {
    ?>
    <span class="copyright"><?php bloginfo( 'name' ); ?> &copy; <?php echo date('Y'); ?> +knasy</span>
    <?php
}

// 親テーマのデフォルト出力を削除し、独自のものを追加する
add_action( 'init', function() {
    remove_action( 'generate_credits', 'generate_add_footer_info' );
    add_action( 'generate_credits', 'generatepress_child_custom_credits' );
} );

/**
 * GeneratePressのデフォルトフッターメタ（カテゴリ、コメントリンクなど）を削除
 */
add_action( 'wp', function() {
    remove_action( 'generate_after_entry_content', 'generate_footer_meta' );
} );

add_action( 'generate_after_entry_content', 'generatepress_child_add_social_footer' );

/**
 * SNSシェアボタンとListen Onリンクを追加
 */
function generatepress_child_add_social_footer() {
    $apple_url = ''; 
    $spotify_url = ''; 
    
    ?>
    <footer class="entry-meta social-footer">
        <div class="social-actions-container">
            <!-- Subscribe / Listen on (Left side) -->
            <div class="listen-on-links">
                <span class="label">Listen on:</span>
                
                <?php if ($apple_url): ?>
                <a href="<?php echo esc_url($apple_url); ?>" class="platform-link apple" target="_blank" rel="noopener noreferrer" aria-label="Apple Podcasts">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M16 11.37A4.65 4.65 0 0011.37 16 4.37 4.37 0 0015.34 20v2.66c0 .4.46.6.72.32 2.68-2.9 5.86-6 4.2-10.45A4.6 4.6 0 0016 11.37zM4.64 12.16a6.6 6.6 0 00-.18 7.37l-1.74 3a.47.47 0 00.7.6l2.16-2.58a6.56 6.56 0 005.1-12.83 5.4 5.4 0 011.69 3.52 4.49 4.49 0 01-3.1 4.7 4.14 4.14 0 01-4-1 4.75 4.75 0 01-.63-2.78zM12 2A10 10 0 002 12a10 10 0 0010 10 10 10 0 0010-10A10 10 0 0012 2z"/></svg>
                </a>
                <?php endif; ?>

                <?php if ($spotify_url): ?>
                <a href="<?php echo esc_url($spotify_url); ?>" class="platform-link spotify" target="_blank" rel="noopener noreferrer" aria-label="Spotify">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.59 14.42c-.18.3-.56.39-.86.21-2.36-1.44-5.33-1.76-8.83-.97-.33.07-.66-.13-.73-.46s.13-.66.46-.73c3.83-.87 7.14-.51 9.77 1.09.28.18.37.55.19.86zm1.22-2.71c-.22.36-.69.47-1.04.26-2.69-1.66-6.8-2.14-9.92-1.17-.4.12-.82-.1-.95-.49-.12-.4.1-.82.49-.95 3.55-1.09 8.1-.57 11.16 1.31.36.22.47.69.26 1.04zm.1-2.88c-3.23-1.92-8.56-2.1-11.64-1.16-.49.15-1.01-.13-1.16-.61-.15-.49.13-1.01.61-1.16 3.6-1.09 9.49-.87 13.23 1.35.45.26.59.84.34 1.29-.26.45-.84.6-1.38.29z"/></svg>
                </a>
                <?php endif; ?>
                
                <span class="coming-soon-label" style="font-size: 11px; color: #ccc;">(Links coming soon)</span>
            </div>
            
            <!-- SNS Share Links (Right side) -->
            <div class="sns-share-links">
                <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>&text=<?php the_title_attribute(); ?>" target="_blank" rel="noopener noreferrer" class="sns-link twitter" aria-label="Share on X">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" target="_blank" rel="noopener noreferrer" class="sns-link facebook" aria-label="Share on Facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                </a>
            </div>
        </div>
    </footer>
    <?php
}

/**
 * サイドバーを完全に制御（削除）する
 */
add_filter( 'generate_sidebar_layout', function( $layout ) {
    return 'no-sidebar';
} );

/**
 * エピソード詳細ページ（投稿）でコメントフォームを削除する
 */
add_action( 'wp', function() {
    if ( is_single() ) {
        remove_action( 'generate_after_do_template_part', 'generate_do_comments_template', 15 );
    }
} );

/**
 * 投稿メタ情報（日付の横）から「投稿者名」を削除する
 */
add_filter( 'generate_header_entry_meta_items', function( $items ) {
    return array_diff( $items, array( 'author' ) );
} );

/**
 * モバイル用インフィード広告の挿入ロジック
 * ルール: 記事1の後、以降3記事ごと (4の後, 7の後...)
 */
add_action( 'generate_after_do_template_part', function( $template ) {
    if ( is_admin() ) return;
    if ( ! is_home() && ! is_archive() ) return;
    
    static $post_count = 0;
    
    if ( in_the_loop() ) {
        $post_count++;
    } else {
        return;
    }

    $should_insert = ( $post_count === 1 ) || ( $post_count > 1 && ( $post_count - 1 ) % 3 === 0 );

    if ( $should_insert ) {
        $ads = [
            'https://placehold.co/600x800/222222/FFFFFF/png?text=Ad+Space+1',
            'https://placehold.co/600x800/333333/FFFFFF/png?text=Ad+Space+2',
            'https://placehold.co/600x800/444444/FFFFFF/png?text=Ad+Space+3',
        ];
        
        $slot_index = 0;
        if ( $post_count > 1 ) {
            $slot_index = ( $post_count - 1 ) / 3;
        }

        $slot_index = (int)$slot_index;
        $ad_image = $ads[ $slot_index % count($ads) ];
        
        ?>
        <div class="mobile-ad-slot hide-on-desktop">
            <div class="mobile-ad-content" style="background-image: url('<?php echo esc_url($ad_image); ?>');">
                <a href="#" class="ad-overlay-text" target="_blank">Sponsored Content <?php echo $slot_index + 1; ?></a>
            </div>
        </div>
        <?php
    }
} );

/**
 * モバイルメニュートグルをテーマトグルに置き換える
 */
add_action( 'after_setup_theme', function() {
    remove_action( 'generate_before_navigation', 'generate_do_header_mobile_menu_toggle' );
    add_action( 'generate_before_navigation', 'generatepress_child_mobile_theme_toggle_wrapper' );
}, 100 );

function generatepress_child_mobile_theme_toggle_wrapper() {
	if ( ! function_exists( 'generate_is_using_flexbox' ) || ! generate_is_using_flexbox() ) {
		return;
	}

	if ( ! function_exists( 'generate_has_inline_mobile_toggle' ) || ! generate_has_inline_mobile_toggle() ) {
		return;
	}
	?>
	<nav <?php echo function_exists('generate_do_attr') ? generate_do_attr( 'mobile-menu-control-wrapper' ) : 'class="mobile-menu-control-wrapper"'; ?>>
		<?php
		do_action( 'generate_inside_mobile_menu_control_wrapper' );
		?>
		<button class="custom-mobile-theme-toggle theme-toggle-btn" aria-label="<?php esc_attr_e( 'Toggle Dark Mode', 'generatepress-child' ); ?>" style="background:transparent;border:0;padding:0 20px;">
			<span class="screen-reader-text"><?php esc_html_e( 'Toggle Dark Mode', 'generatepress-child' ); ?></span>
		</button>
	</nav>
	<?php
}

/**
 * ナビゲーション位置のオーバーライド
 */
function generate_navigation_position() {
	do_action( 'generate_before_navigation' );
	?>
	<nav <?php generate_do_attr( 'navigation' ); ?>>
		<div <?php generate_do_attr( 'inside-navigation' ); ?>>
			<?php
			do_action( 'generate_inside_navigation' );
			?>

			<?php
			do_action( 'generate_after_mobile_menu_button' );

			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container' => 'div',
					'container_class' => 'main-nav',
					'container_id' => 'primary-menu',
					'menu_class' => '',
					'fallback_cb' => 'generate_menu_fallback',
					'items_wrap' => '<ul id="%1$s" class="%2$s ' . join( ' ', generate_get_element_classes( 'menu' ) ) . '">%3$s</ul>',
				)
			);

			do_action( 'generate_after_primary_menu' );
			?>
		</div>
	</nav>
	<?php
	do_action( 'generate_after_navigation' );
}
