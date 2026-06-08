<?php

namespace App\Console\Commands;

use App\Models\TrainingLesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DebugVideoStream extends Command
{
    protected $signature = 'debug:video-stream';
    protected $description = 'Debug training video streaming';

    public function handle(): void
    {
        $lesson = TrainingLesson::whereNotNull('video_path')->first();

        if (! $lesson) {
            $this->error('No lessons with videos found.');
            return;
        }

        $this->info("Lesson: {$lesson->id}");
        $this->info("Path: {$lesson->video_path}");

        $useR2 = (bool) config('filesystems.disks.r2.bucket');
        $this->info("Disk: " . ($useR2 ? 'r2' : 'public'));

        $disk = $useR2 ? 'r2' : 'public';

        try {
            $exists = Storage::disk($disk)->exists($lesson->video_path);
            $this->info("File exists: " . ($exists ? 'yes' : 'NO'));
        } catch (\Throwable $e) {
            $this->error("exists() failed: " . $e->getMessage());
        }

        try {
            $size = Storage::disk($disk)->size($lesson->video_path);
            $this->info("File size: {$size} bytes");
        } catch (\Throwable $e) {
            $this->error("size() failed: " . $e->getMessage());
        }

        if ($useR2) {
            try {
                $client = new \Aws\S3\S3Client([
                    'region'                  => 'auto',
                    'endpoint'                => config('filesystems.disks.r2.endpoint'),
                    'credentials'             => [
                        'key'    => config('filesystems.disks.r2.key'),
                        'secret' => config('filesystems.disks.r2.secret'),
                    ],
                    'use_path_style_endpoint' => true,
                    'version'                 => 'latest',
                ]);
                $this->info("S3Client created OK");

                $result = $client->getObject([
                    'Bucket' => config('filesystems.disks.r2.bucket'),
                    'Key'    => $lesson->video_path,
                    'Range'  => 'bytes=0-1023',
                ]);
                $this->info("GetObject (range 0-1023) OK, HTTP: " . $result['@metadata']['statusCode']);
                $this->info("Content-Type: " . ($result['ContentType'] ?? 'unknown'));
            } catch (\Throwable $e) {
                $this->error("S3 error: " . $e->getMessage());
            }
        }

        $this->info("Stream URL would be: " . route('training.stream', $lesson));
    }
}
