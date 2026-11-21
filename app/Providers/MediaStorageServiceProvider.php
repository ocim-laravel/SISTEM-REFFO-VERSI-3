<?php

namespace App\Providers;

use App\Support\MediaStorage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class MediaStorageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Ensure the local media directory and the public storage link exist.
     */
    public function boot(): void
    {
        try {
            MediaStorage::ensureDirectories();
        } catch (Throwable $exception) {
            Log::warning('Folder MEDIA SISTEM gagal disiapkan.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        $this->ensurePublicStorageLink();
    }

    private function ensurePublicStorageLink(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (file_exists($link) || is_link($link)) {
            return;
        }

        try {
            File::link($target, $link);
        } catch (Throwable $exception) {
            Log::warning('Symbolic link public/storage gagal dibuat. Jalankan php artisan storage:link.', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
