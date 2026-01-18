jQuery(document).ready(function($) {
    // アイコン SVG 定義
    // 太陽 (Switch to Light)
    const svgSun = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';
    
    // 月 (Switch to Dark)
    const svgMoon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';

    // 1. 設定の読み込みと適用
    function applyTheme() {
        const isDark = localStorage.getItem('theme') === 'dark' || 
                      (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            $('body').addClass('is-dark-theme');
            // 今がダークなら次はライトにするボタン（太陽）
            $('.theme-toggle-btn').html(svgSun);
            $('.theme-toggle-btn').attr('title', 'Switch to Light Mode');
        } else {
            $('body').removeClass('is-dark-theme');
            // 今がライトなら次はダークにするボタン（月）
            $('.theme-toggle-btn').html(svgMoon); 
            $('.theme-toggle-btn').attr('title', 'Switch to Dark Mode');
        }
    }

    // 初期適用
    applyTheme();

    // 2. メニューの特定の項目をボタンに置換
    // 「サンプルページ」または「Sample Page」を探す
    const $targetLink = $('.main-navigation a').filter(function() {
        const text = $(this).text().trim();
        return text === 'サンプルページ' || text === 'Sample Page';
    });

    if ($targetLink.length) {
        const $li = $targetLink.parent('li');
        // 親のliの中身をボタンに入れ替え
        $li.html('<button class="theme-toggle-btn" style="background:none;border:none;cursor:pointer;padding:0 20px;height:60px;color:inherit;display:flex;align-items:center;justify-content:center;width:100%;"></button>');
        
        // 再度適用（ボタンのアイコン更新のため）
        applyTheme();
    }

    // 3. クリックイベント
    $(document).on('click', '.theme-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation(); // メニューの誤動作防止
        
        if ($('body').hasClass('is-dark-theme')) {
            localStorage.setItem('theme', 'light');
        } else {
            localStorage.setItem('theme', 'dark');
        }
        applyTheme();
    });
});
