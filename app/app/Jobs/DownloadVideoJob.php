<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DownloadVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $format;
    protected $uuidFileName;
    protected $sessionId;
    protected $jobId;

    public function __construct($url, $format, $uuidFileName, $sessionId, $jobId)
    {
        $this->url = $url;
        $this->format = $format;
        $this->uuidFileName = $uuidFileName;
        $this->sessionId = $sessionId;
        $this->jobId = $jobId;
    }

    public function handle()
    {
        $outputPath = storage_path("app/downloads/{$this->sessionId}/{$this->uuidFileName}");
        $cacheKey = "download-progress-{$this->sessionId}-{$this->jobId}";

        // ダウンロードの進捗状況を初期化
        Cache::put($cacheKey, ['status' => 'pending', 'progress' => 0], now()->addMinutes(30));

        // ダウンロードコマンドを構築
        $command = ['yt-dlp', '--progress', '--newline', '--no-warnings', '--embed-thumbnail', '-o', $outputPath];
        
        // フォーマットに応じたオプションを追加
        if ($this->format === 'mp4') {
            array_push($command, '-f', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]', '--merge-output-format', 'mp4');
        } elseif ($this->format === 'mov') {
            array_push($command, '-f', "bv[vcodec!~='^(vp0?9|av0?1)']+ba[ext='m4a']", '--merge-output-format', 'mov');
        } elseif ($this->format === 'm4a') {
            array_push($command, '--extract-audio', '--audio-format', 'm4a');
        } elseif ($this->format === 'mp3') {
            array_push($command, '--extract-audio', '--audio-format', 'mp3');
        }
        array_push($command, $this->url);
        
        $process = new Process($command);
        $process->setTimeout(3600);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            // 完了ステータスをキャッシュに保存
            Cache::put($cacheKey, ['status' => 'completed', 'progress' => 100], now()->addMinutes(30));

        } catch (\Exception $e) {
            // エラー時はエラー情報をキャッシュに保存
            if (str_contains($e, "Requested format is not available.")) {
                Cache::put($cacheKey, ['status' => 'error-1'], now()->addMinutes(30));
            } else {
                Cache::put($cacheKey, ['status' => 'failed'], now()->addMinutes(30));
            }
            Log::error('ダウンロードエラー' . $e);
        }
    }
}