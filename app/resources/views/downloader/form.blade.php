<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTubeダウンローダー</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4330955739769594" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">YouTubeダウンローダー</h1>

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ url('/download') }}" method="POST" id="download-form">
            @csrf
            <div class="mb-3">
                <label for="url" class="form-label">YouTube URL</label>
                <input type="text" name="url" id="url" class="form-control" placeholder="例: https://www.youtube.com/watch?v=..." required>
            </div>
            <div class="mb-3">
                <label for="format" class="form-label">ファイル形式</label>
                <select name="format" id="format" class="form-select">
                    <option value="mp4" selected>MP4 (動画)</option>
                    <option value="mov">MOV (動画)</option>
                    <option value="m4a">M4A (音声)</option>
                    <option value="mp3">MP3 (音声)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" id="btn">ダウンロード</button>
        </form>
    <div id="progress-container" style="display:none;">
        <p>ダウンロード中...</p>
        <div id="progress-bar-container" style="width: 100%; background-color: #f3f3f3; border-radius: 5px;">
            <div id="progress-bar" style="width: 0%; height: 20px; background-color: #4CAF50; text-align: center; line-height: 20px; color: white; border-radius: 5px;">
                0%
            </div>
        </div>
    </div>
    </div>
<script>
document.getElementById('download-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const form = e.target;
    const url = form.action;
    const method = form.method;
    const formData = new FormData(form);

    document.getElementById('download-form').style.display = 'none';
    document.getElementById('progress-container').style.display = 'block';

    // フォーム送信
    fetch(url, {
        method: method,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('#download-form > input[type=hidden]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const jobId = data.jobId;

            // ポーリング開始
            const progressInterval = setInterval(function() {
                fetch(`/download-progress/${jobId}`)
                    .then(res => res.json())
                    .then(progressData => {
                        const progressBar = document.getElementById('progress-bar');
                        
                        if (progressData.status === 'downloading') {
                            const progress = Math.floor(progressData.progress);
                            progressBar.style.width = progress + '%';
                            progressBar.textContent = progress + '%';
                        } else if (progressData.status === 'completed') {
                            // ダウンロード完了
                            clearInterval(progressInterval);
                            progressBar.style.width = '100%';
                            progressBar.textContent = '100%';
                            alert('ダウンロードが完了しました！');

                            // 新しいエンドポイントからファイルをダウンロードさせる
                            window.location.href = `/serve-download/${jobId}`;

                        } else if (progressData.status === 'failed') {
                            // ダウンロード失敗
                            clearInterval(progressInterval);
                            alert('ダウンロードに失敗しました: ' + progressData.message);
                            // エラー後、フォームを再表示
                            document.getElementById('download-form').style.display = 'block';
                            document.getElementById('progress-container').style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching progress:', error);
                        clearInterval(progressInterval);
                    });
            }, 1000); // 1秒ごとにポーリング
        } else {
            alert('ダウンロードを開始できませんでした。');
        }
    })
    .catch(error => {
        console.error('Error starting download:', error);
    });
});
</script>
</body>
</html>