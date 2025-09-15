<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

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
            'format' => 'required|in:mp4,mp3',
        ]);
        
        $url = $request->input('url');
        $format = $request->input('format');

        $fileName = Str::uuid() . '.' . $format;
        $outputDir = storage_path('app/downloads');
        $outputPath = $outputDir . '/' . $fileName;

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $command = ['yt-dlp','--embed-thumbnail', '-o', $outputPath];
        if ($format === 'mp4') {
            array_push($command, '-f', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]', '--merge-output-format', 'mp4');
        } elseif ($format === 'mp3') {
            array_push($command, '--extract-audio', '--audio-format', 'mp3');
        }

        array_push($command, $url);
        $process = new Process($command);
        $process->setTimeout(3600);

        try{
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            $downloadLink = route('download.file', ['file' => $fileName]);
            return back()->with('success', '動画のダウンロードが完了しました！')
                        ->with('download_link', $downloadLink);
        } catch (\Exception $e) {
            return back()->with('error', 'ダウンロードに失敗しました: ' . $e->getMessage());
        }
    }

    public function downloadFile($file)
    {
        $filePath = storage_path('app/downloads/' . $file);

        if (!file_exists($filePath)) {
            return back()->with('error', 'ファイルが見つかりません');
        }

        return response()->download($filePath);
    }

}
