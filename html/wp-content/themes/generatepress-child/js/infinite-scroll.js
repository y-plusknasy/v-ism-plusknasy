jQuery(document).ready(function($) {
    // パラメータがなければ終了
    if (typeof infinite_scroll_params === 'undefined') return;

    var $main = $('main#main'); // 記事が追加されるコンテナ
    var $window = $(window);
    var $document = $(document);
    var loading = false;
    var current_page = parseInt(infinite_scroll_params.current_page);
    var max_page = parseInt(infinite_scroll_params.max_page);
    var next_link = infinite_scroll_params.next_link; // 次のページのURL

    // ローディングインジケータを作成
    var $loader = $('<div class="infinite-loader" style="text-align:center; padding:20px; font-weight:bold; color:#666;">Loading more episodes...</div>');
    $loader.hide();
    $main.after($loader);

    // 標準のページネーションを隠す
    $('.paging-navigation, .nav-links').hide();

    $window.scroll(function() {
        // すでにロード中、または最後のページなら何もしない
        if (loading || current_page >= max_page || !next_link) return;

        // スクロール位置の計算 (フッターより 800px 手前で発火)
        if ($document.height() - $window.height() - $window.scrollTop() < 800) {
            loading = true;
            $loader.show();

            // 開発環境でのCORSエラー(localhost vs [::1]など)を回避するため、
            // 絶対URLからドメインを除去して相対パスに変換してリクエストする
            var requestUrl = next_link;
            try {
                var urlObj = new URL(next_link);
                requestUrl = urlObj.pathname + urlObj.search;
            } catch(e) {
                // URLパース失敗時はそのまま
                console.warn('URL parse failed', e);
            }

            // Fetch API ではなく jQuery.get を使用 (互換性のため)
            $.get(requestUrl, function(data) {
                var $data = $(data);
                // 記事要素を抽出 (GeneratePressの構造に合わせる)
                var $new_articles = $data.find('main#main > article');
                
                // 次のページリンクを取得
                var $next_link_elem = $data.find('.nav-links .next, .paging-navigation .next');
                var fetchedNextHref = $next_link_elem.attr('href');

                if ($new_articles.length) {
                    // 記事をメインコンテナに追加
                    $new_articles.hide(); // 一旦隠す
                    $main.append($new_articles);
                    $new_articles.fadeIn(500); // ふわっと表示

                    // カウントアップ
                    current_page++;

                    // 次のリンク更新
                    if (fetchedNextHref) {
                        next_link = fetchedNextHref;
                    } else {
                        next_link = null; // 次がない
                    }
                    
                    // コンソールログ（デバッグ用）
                    console.log('Loaded page ' + current_page);
                } else {
                    next_link = null;
                }

                loading = false;
                $loader.hide();
            }).fail(function() {
                console.log('Infinite scroll failed.');
                loading = false;
                $loader.hide();
            });
        }
    });
});
