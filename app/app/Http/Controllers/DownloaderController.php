<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Illuminate\Support\Facades\Log;

class DownloaderController extends Controller
{
    public function showForm()
    {
        return view('downloader.form');
    }

    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'format' => 'required|in:mp4,mp3,mov',
        ]);

        $url = $request->input('url');
        $format = $request->input('format');
        $sessionId = $request->session()->getId();

        // 1. ダウンロードせずに動画情報を取得する
        $infoProcess = new Process(['yt-dlp', '--print-json', '--skip-download', $url]);
        try {
            $infoProcess->run();
            if (!$infoProcess->isSuccessful()) {
                throw new \RuntimeException($infoProcess->getErrorOutput());
            }
            $info = json_decode($infoProcess->getOutput(), true);
            $videoTitle = $info['title'] ?? 'download';
        } catch (\Exception $e) {
            return back()->with('error', '動画情報の取得に失敗しました。');
        }

        // 2. 物理的なファイル名をUUIDで生成
        $uuidFileName = (string) Str::uuid() . '.' . $format;
        $outputDir = storage_path('app/downloads/' . $sessionId);
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $outputPath = $outputDir . '/' . $uuidFileName;

        // 3. ダウンロードコマンドを構築
        $command = ['yt-dlp', '--embed-thumbnail', '-o', $outputPath];

        if ($format === 'mp4') {
            array_push($command, '-f', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]', '--merge-output-format', 'mp4');
        } elseif ($format === 'mov'){
            array_push($command, '-f', "bv[vcodec!~='^(vp0?9|av0?1)']+ba[ext='m4a']", '--merge-output-format', 'mov');
        } elseif ($format === 'mp3') {
            array_push($command, '--extract-audio', '--audio-format', 'mp3');
        }

        array_push($command, $url);
        
        $process = new Process($command);
        $process->setTimeout(3600);

        try {
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $filePath = storage_path('app/downloads/' . $sessionId . '/' . $uuidFileName);

            $downloadFileName = $videoTitle . '.' . $format;
            $downloadFileName = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $downloadFileName);

            $directoryPath = 'downloads/' . $sessionId;
            $response = response()->download($filePath, $downloadFileName);
            $response->deleteFileAfterSend(true);

            register_shutdown_function(function () use ($directoryPath) {
                if (Storage::disk('app_root')->exists($directoryPath)) {
                    Storage::disk('app_root')->deleteDirectory($directoryPath);
                }
            });
            
            return $response;

        } catch (\Exception $e) {
            return back()->with('error', 'ダウンロードに失敗しました: ' . $e->getMessage());
        }
    }
}