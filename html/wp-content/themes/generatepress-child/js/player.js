jQuery(document).ready(function($) {
    // --- 要素の取得（PC用とモバイル用の両方） ---
    const $trackTitle = $('#player-track-title');
    const $currentTime = $('#player-current-time');
    const $duration = $('#player-duration');
    const $seekBar = $('#player-seek-bar, #mobile-player-seek-bar');
    const $playBtn = $('#player-btn-play, #mobile-player-btn-play');
    const $rewindBtn = $('#player-btn-rewind, #mobile-player-btn-rewind');
    const $forwardBtn = $('#player-btn-forward, #mobile-player-btn-forward');
    const $speedBtn = $('#player-btn-speed');
    const $volumeBar = $('#player-volume-bar');
    const $downloadBtn = $('#player-btn-download');
    
    // シークバーを即座に初期位置（0%）に設定
    $seekBar.val(0);

    // --- 状態管理 ---
    let audio = new Audio();
    let isPlaying = false;
    let playbackRates = [1.0, 1.25, 1.5, 2.0];
    let currentSpeedIndex = 0;
    
    // 現在再生中のボタン（記事内の）への参照
    let $currentArticleBtn = null;
    
    // --- 初期設定：デフォルトで日本語音声をセット ---
    $(function() {
        const $japaneseBtn = $('.podcast-play-button').first();
        if ($japaneseBtn.length && $japaneseBtn.data('src')) {
            const src = $japaneseBtn.data('src');
            const title = $japaneseBtn.data('title');
            audio.src = src;
            audio.load();
            $trackTitle.text(title);
            if ($downloadBtn.length) {
                $downloadBtn.attr('href', src).removeClass('disabled');
            }
            $currentArticleBtn = $japaneseBtn;
        }
    });

    // --- ヘルパー関数 ---
    
    // 秒数を mm:ss 形式に変換
    function formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return "0:00";
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ":" + (s < 10 ? "0" : "") + s;
    }

    // SVG定義（モバイル用は28px、PC用は24px）
    const svgPlay = '<svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
    const svgPause = '<svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>';

    // プレイヤーの再生状態を更新（ボタン表示など）
    function updatePlayState(playing) {
        if (playing) {
            var playPromise = audio.play();
            
            if (playPromise !== undefined) {
                playPromise.then(_ => {
                    // 再生開始成功
                    isPlaying = true;
                    $playBtn.html(svgPause);
                    if ($currentArticleBtn) {
                        // アイコンをWaveアニメーションに変更
                        const $iconParams = '<div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div><div class="wave-bar"></div>';
                        $currentArticleBtn.find('.icon-container').html($iconParams);
                        $currentArticleBtn.find('.text-container').text('Now Playing');
                        $currentArticleBtn.addClass('playing');
                    }
                })
                .catch(error => {
                    console.error("Playback failed:", error);
                    updatePlayState(false); // 状態を戻す
                });
            }
        } else {
            audio.pause();
            isPlaying = false;
            $playBtn.html(svgPlay);
            if ($currentArticleBtn) {
                // Now Playingの状態から元に戻す
                $currentArticleBtn.find('.icon-container').html('▶');
                
                // data-original-textがある場合はそれを使う、なければデフォルト
                const originalText = $currentArticleBtn.data('original-text') || 'Play Episode';
                $currentArticleBtn.find('.text-container').text(originalText);
                
                $currentArticleBtn.removeClass('playing');
            }
        }
    }

    // --- イベントリスナー ---

    // 1. 記事内の再生ボタンクリック
    $(document).off('click', '.podcast-play-button').on('click', '.podcast-play-button', function(e) {
        e.preventDefault();

        const $btn = $(this);
        // disabledなら何もしない
        if ($btn.hasClass('disabled') || $btn.prop('disabled')) return;

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
                
                // アイコンリセット
                if ($currentArticleBtn) {
                    $currentArticleBtn.find('.icon-container').html('▶');
                    const originalText = $currentArticleBtn.data('original-text') || 'Play Episode';
                    $currentArticleBtn.find('.text-container').text(originalText);
                    $currentArticleBtn.removeClass('playing');
                }

                // 新しいトラックをセット
                audio.src = src;
                audio.load();

                $trackTitle.text(title);
                // ダウンロードボタンの更新
                if ($downloadBtn.length) {
                    $downloadBtn.attr('href', src).removeClass('disabled');
                }
            }
            
            $currentArticleBtn = $btn;
            updatePlayState(true);
        }
    });


    // 2. プレイヤーの再生/一時停止ボタン
    $playBtn.on('click', function() {
        if (!audio.src) {
            // 音声がセットされていない場合、日本語音声を自動セット
            const $japaneseBtn = $('.podcast-play-button').first();
            if ($japaneseBtn.length && $japaneseBtn.data('src')) {
                const src = $japaneseBtn.data('src');
                const title = $japaneseBtn.data('title');
                audio.src = src;
                audio.load();
                $trackTitle.text(title);
                if ($downloadBtn.length) {
                    $downloadBtn.attr('href', src).removeClass('disabled');
                }
                $currentArticleBtn = $japaneseBtn;
            } else {
                return; // それでも音声がない場合は何もしない
            }
        }
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
    const $volumeIcon = $('.volume-icon');
    let lastVolume = 1.0;
    
    // アイコンの状態管理（クラスの有無のみで管理）
    function updateVolumeIcon(vol) {
        if (vol < 0.01) {
            $volumeIcon.addClass('muted');
        } else {
            $volumeIcon.removeClass('muted');
        }
    }
    
    $volumeBar.on('input', function() {
        const val = $(this).val();
        const vol = val / 100;
        audio.volume = vol;
        updateVolumeIcon(vol);
        
        if (vol > 0) lastVolume = vol; 
    });

    // ボリュームアイコンクリックでミュート切り替え
    $volumeIcon.on('click', function() {
        if (audio.volume > 0.01) {
            // ミュート実行
            lastVolume = audio.volume;
            audio.volume = 0;
            $volumeBar.val(0);
            updateVolumeIcon(0);
        } else {
            // ミュート解除 (前回の音量 または 50%)
            const targetVol = (lastVolume > 0.01) ? lastVolume : 0.5;
            audio.volume = targetVol;
            $volumeBar.val(targetVol * 100);
            updateVolumeIcon(targetVol);
        }
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
        // シークバーを100%に保持
        $seekBar.val(100);
        $currentTime.text(formatTime(audio.duration));
    });
    
    // エラーハンドリング
    audio.addEventListener('error', function(e) {
        console.error("Audio error", e);
        $trackTitle.text("Error loading audio.");
    });
});
