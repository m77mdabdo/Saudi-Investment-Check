<?php

namespace App\Console\Commands;

use App\Services\MediaService;
use App\Services\PexelsClient;
use Illuminate\Console\Command;

class SyncMedia extends Command
{
    protected $signature = 'media:sync {--slot= : Only refresh this slot} {--force : Replace slots that already have an image}';

    protected $description = 'Fetch background imagery from Pexels and cache it locally for the public pages';

    public function handle(MediaService $media, PexelsClient $pexels): int
    {
        if (! $pexels->configured()) {
            $this->warn('PEXELS_API_KEY is not set — the app will keep using bundled fallback images.');

            return self::SUCCESS;
        }

        $slots = $this->option('slot') ? [$this->option('slot')] : array_keys(MediaService::SLOTS);

        foreach ($slots as $slot) {
            if (! array_key_exists($slot, MediaService::SLOTS)) {
                $this->error("Unknown slot: {$slot}");

                continue;
            }

            if (! $this->option('force') && $media->slot($slot)['remote']) {
                $this->line("• {$slot}: already set (use --force to replace)");

                continue;
            }

            $asset = $media->refresh($slot);

            $asset
                ? $this->info("✓ {$slot}: {$asset->query}")
                : $this->warn("× {$slot}: no result, falling back to the local image");
        }

        return self::SUCCESS;
    }
}
