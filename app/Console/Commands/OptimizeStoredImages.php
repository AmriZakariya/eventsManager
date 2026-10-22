<?php

namespace App\Console\Commands;

use App\Models\EventSetting;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-off: optimizes images already in storage/app/public, in place (same
 * file names, so URLs stay valid). New admin uploads are optimized on upload.
 */
class OptimizeStoredImages extends Command
{
    protected $signature = 'images:optimize
        {--min-kb=300 : Only touch images larger than this}
        {--include-chat : Also optimize photos users sent in chat (chat_files/), skipped by default}
        {--dry-run : Show what would be saved without changing files}';

    protected $description = 'Resize and recompress large images in public storage';

    public function handle(): int
    {
        if (! ImageOptimizer::available()) {
            $this->error('The PHP GD extension is not available on this server.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $minBytes = max(0, (int) $this->option('min-kb')) * 1024;
        $dryRun = (bool) $this->option('dry-run');

        $skip = $this->fullSizeFiles();
        foreach ($skip as $file) {
            $this->line("Skipping {$file} (floor plan, kept full size)");
        }

        $done = 0;
        $before = 0;
        $after = 0;

        foreach ($disk->allFiles() as $file) {
            if (! preg_match('/\.(jpe?g|png|webp)$/i', $file) || $disk->size($file) <= $minBytes) {
                continue;
            }

            if (in_array($file, $skip, true) || (! $this->option('include-chat') && str_starts_with($file, 'chat_files/'))) {
                continue;
            }

            $result = ImageOptimizer::optimize($disk->path($file), $dryRun);
            if ($result === null) {
                continue;
            }

            $done++;
            $before += $result['before'];
            $after += $result['after'];

            $this->line(sprintf(
                '%s  %s → %s (%dx%d)',
                $file,
                $this->kb($result['before']),
                $this->kb($result['after']),
                $result['width'],
                $result['height'],
            ));
        }

        $this->info(sprintf(
            '%s %d image(s): %s → %s (saved %s).',
            $dryRun ? 'Would optimize' : 'Optimized',
            $done,
            $this->kb($before),
            $this->kb($after),
            $this->kb($before - $after),
        ));

        return self::SUCCESS;
    }

    /**
     * Files that must keep full resolution: the floor plan users zoom into.
     * Settings store it as a URL ("/storage/2026/10/13/x.png"); convert to a disk path.
     */
    private function fullSizeFiles(): array
    {
        return EventSetting::query()
            ->pluck('floor_plan_image')
            ->filter()
            ->map(fn ($value) => (string) parse_url($value, PHP_URL_PATH))
            ->map(fn ($path) => preg_replace('#^/?storage/#', '', ltrim($path, '/')))
            ->filter()
            ->values()
            ->all();
    }

    private function kb(int $bytes): string
    {
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1) . ' MB'
            : number_format($bytes / 1024) . ' KB';
    }
}
