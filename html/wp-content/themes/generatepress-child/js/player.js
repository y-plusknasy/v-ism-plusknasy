jQuery(document).ready(function($) {
    // --- 要素の取得 ---
    const $trackTitle = $('#player-track-title');
    const $currentTime = $('#player-current-time');
    const $duration = $('#player-duration');
    const $seekBar = $('#player-seek-bar');
    const $playBtn = $('#player-btn-play');
    const $rewindBtn = $('#player-btn-rewind');
    const $forwardBtn = $('#player-btn-forward');
    const $speedBtn = $('#player-btn-speed');
    const $volumeBar = $('#player-volume-bar');
    const $downloadBtn = $('#player-btn-download');

    // --- 状態管理 ---
    let audio = new Audio();
    let isPlaying = false;
    let playbackRates = [1.0, 1.25, 1.5, 2.0];
    let currentSpeedIndex = 0;
    
    // 現在再生中のボタン（記事内の）への参照
    let $currentArticleBtn = null;

    // --- ヘルパー関数 ---
    
    // 秒数を mm:ss 形式に変換
    function formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return "0:00";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ":" + (s < 10 ? "0" : "") + s;
    }

    // プレイヤーの再生状態を更新（ボタン表示など）
    function updatePlayState(playing) {
        if (playing) {
            var playPromise = audio.play();
            
            if (playPromise !== undefined) {
                playPromise.then(_ => {
                    // 再生開始成功
                    isPlaying = true;
                    $playBtn.text('⏸');
                    if ($currentArticleBtn) {
                        $currentArticleBtn.text('⏸ Pause Episode');
                        $currentArticleBtn.addClass('playing');
                    }
                })
                .catch(error => {
                    // 自動再生ポリシーや読み込みエラーなどで失敗した場合
                    console.error("Playback failed or interrupted:", error);
                    // UIを停止状態に戻さないと、見た目だけ再生中になってしまう
                    isPlaying = false;
                    $playBtn.text('▶'); 
                    $playBtn.css('padding-left', '4px');
                    if ($currentArticleBtn) {
                        $currentArticleBtn.text('▶ Play Episode');
                        $currentArticleBtn.removeClass('playing');
                    }
                });
            }
        } else {
            audio.pause();
            isPlaying = false;
            $playBtn.text('▶');
            $playBtn.css('padding-left', '4px');
            if ($currentArticleBtn) {
                $currentArticleBtn.text('▶ Play Episode');
                $currentArticleBtn.removeClass('playing');
            }
        }
    }

    // --- イベントリスナー ---

    // 1. 記事内の再生ボタンクリック
    $(document).on('click', '.podcast-play-button', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const src = $btn.data('src');
        const title = $btn.data('title');

        if ($currentArticleBtn && $currentArticleBtn[0] === $btn[0] && isPlaying) {
            // 同じ曲を再生中に押した -> 一時停止
            updatePlayState(false);
        } else {
             // 別の曲、または一時停止中
            if (audio.src !== src) {
                // 再生中のものがあれば確実に止める
                audio.pause();
                isPlaying = false;

                // 新しいトラックをセット
                audio.src = src;
                audio.load(); // 明示的にロード開始

                $trackTitle.text(title);
                $downloadBtn.attr('href', src).show();
                
                // 前のボタンの状態をリセット
                if ($currentArticleBtn) {
                    $currentArticleBtn.text('▶ Play Episode').removeClass('playing');
                }
            }
            
            $currentArticleBtn = $btn;
            updatePlayState(true);
            $playBtn.css('padding-left', '0');
        }
    });

    // 2. プレイヤーの再生/一時停止ボタン
    $playBtn.on('click', function() {
        if (!audio.src) return; // 曲がセットされてなければ何もしない
        updatePlayState(!isPlaying);
    });

    // 3. 前後スキップ
    $rewindBtn.on('click', function() {
        if (!audio.src) return;
        audio.currentTime = Math.max(0, audio.currentTime - 15);
    });

    $forwardBtn.on('click', function() {
        if (!audio.src) return;
        audio.currentTime = Math.min(audio.duration, audio.currentTime + 30);
    });

    // 4. 再生速度変更
    $speedBtn.on('click', function() {
        currentSpeedIndex = (currentSpeedIndex + 1) % playbackRates.length;
        const rate = playbackRates[currentSpeedIndex];
        audio.playbackRate = rate;
        $(this).text(rate + "x");
    });

    // 5. ボリューム変更
    $volumeBar.on('input', function() {
        audio.volume = $(this).val() / 100;
    });

    // 6. シークバー操作（ドラッグ中）
    $seekBar.on('input', function() {
        const seekTo = audio.duration * ($(this).val() / 100);
        audio.currentTime = seekTo;
        $currentTime.text(formatTime(seekTo));
    });

    // --- Audio オブジェクトのイベント ---

    audio.addEventListener('timeupdate', function() {
        // 再生位置の更新
        const percent = (audio.currentTime / audio.duration) * 100;
        $seekBar.val(percent);
        $currentTime.text(formatTime(audio.currentTime));
    });

    audio.addEventListener('loadedmetadata', function() {
        // 総時間の更新
        $duration.text(formatTime(audio.duration));
    });

    audio.addEventListener('ended', function() {
        // 再生終了時
        updatePlayState(false);
        audio.currentTime = 0;
        $seekBar.val(0);
        $currentTime.text("0:00");
    });
    
    // エラーハンドリング
    audio.addEventListener('error', function(e) {
        console.error("Audio error", e);
        $trackTitle.text("Error loading audio.");
    });
});
