<?php

namespace App\Listeners;

use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use Orchid\Platform\Events\UploadFileEvent;

/**
 * Every image uploaded in the admin (Cropper/Picture fields) is resized and
 * recompressed right after upload. Same path and name, so the URL Orchid
 * returns stays valid.
 */
class OptimizeUploadedImage
{
    public function handle(UploadFileEvent $event): void
    {
        $attachment = $event->attachment;

        if (! str_starts_with((string) $attachment->mime, 'image/')) {
            return;
        }

        $disk = Storage::disk($attachment->disk);

        // Only local disks have a real file path to work on.
        if (! method_exists($disk, 'path')) {
            return;
        }

        $result = ImageOptimizer::optimize($disk->path($attachment->physicalPath()));

        if ($result !== null) {
            $attachment->forceFill(['size' => $result['after']])->saveQuietly();
        }
    }
}
