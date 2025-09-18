<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTubeダウンローダー</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">YouTubeダウンローダー</h1>

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ url('/download') }}" method="POST">
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
                    <option value="mp3">MP3 (音声)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">ダウンロード</button>
        </form>
    </div>
</body>
</html>