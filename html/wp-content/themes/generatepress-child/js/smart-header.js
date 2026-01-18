jQuery(document).ready(function($) {
    var $header = $('.site-header');
    var $sidebar = $('.custom-fixed-sidebar'); // サイドバー要素
    var lastScrollTop = 0;
    var delta = 5;
    var headerHeight = $header.outerHeight();
    var sidebarBaseTop = 30; // サイドバーの基本のtop位置 (ヘッダーとの隙間)

    // ヘッダーをfixedにするため、bodyの上部に余白を追加してコンテンツが隠れないようにする
    $('body').css('padding-top', headerHeight + 'px');
    
    // 初期状態：サイドバーの位置をヘッダー下にあわせる
    if ($sidebar.length) {
        $sidebar.css('top', (headerHeight + sidebarBaseTop) + 'px');
    }

    // ウィンドウサイズ変更時に高さを再取得
    $(window).resize(function() {
        headerHeight = $header.outerHeight();
        $('body').css('padding-top', headerHeight + 'px');
        
        // リサイズ時もサイドバー位置を調整（現在隠れているかどうかで分岐が必要だが、簡易的に表示状態とみなすか、クラス確認する）
        if (!$header.hasClass('smart-header-hidden')) {
             if ($sidebar.length) {
                $sidebar.css('top', (headerHeight + sidebarBaseTop) + 'px');
            }
        }
    });

    $(window).scroll(function(event){
        var st = $(this).scrollTop();
        
        // スクロール量がdelta以下なら何もしない
        if(Math.abs(lastScrollTop - st) <= delta)
            return;
        
        // ヘッダーの高さより下にスクロールした場合のみ動作
        if (st > lastScrollTop && st > headerHeight){
            // Scroll Down - ヘッダーを隠す
            $header.addClass('smart-header-hidden');
            $header.removeClass('smart-header-visible');
            
            // サイドバーを上げる（ヘッダー分詰める）
            if ($sidebar.length) {
                $sidebar.css('top', sidebarBaseTop + 'px');
            }

        } else {
            // Scroll Up - ヘッダーを表示
            if(st + $(window).height() < $(document).height()) {
                $header.addClass('smart-header-visible');
                $header.removeClass('smart-header-hidden');
                
                // サイドバーを下げる（ヘッダー分空ける）
                if ($sidebar.length) {
                    $sidebar.css('top', (headerHeight + sidebarBaseTop) + 'px');
                }
            }
        }
        
        // 最上部に戻ったら初期状態
        if (st <= 0) {
             $header.removeClass('smart-header-hidden smart-header-visible');
             if ($sidebar.length) {
                $sidebar.css('top', (headerHeight + sidebarBaseTop) + 'px');
            }
        }
        
        lastScrollTop = st;
    });
});

