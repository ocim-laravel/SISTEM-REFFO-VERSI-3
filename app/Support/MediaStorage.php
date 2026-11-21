<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public const DISK = 'media_sistem';

    public const ROOT_DIRECTORY = 'MEDIA SISTEM';

    public const FOLDER_BRIEFING_DESAIN = 'briefing_desain';

    public const FOLDER_PREVIEW_ACC = 'preview_acc';

    public const FOLDER_FILE_CETAK_TIFF = 'file_cetak_tiff';

    public const FOLDER_FILE_MASTER_ARSIP = 'file_master_arsip';

    /**
     * @var array<string, string>
     */
    public const FOLDERS = [
        self::FOLDER_BRIEFING_DESAIN => 'Briefing Desain',
        self::FOLDER_PREVIEW_ACC => 'Preview ACC',
        self::FOLDER_FILE_CETAK_TIFF => 'File Cetak TIFF',
        self::FOLDER_FILE_MASTER_ARSIP => 'File Master Arsip',
    ];

    public static function rootPath(): string
    {
        return storage_path('app/public/'.self::ROOT_DIRECTORY);
    }

    public static function ensureDirectories(): void
    {
        File::ensureDirectoryExists(self::rootPath());

        foreach (array_keys(self::FOLDERS) as $folder) {
            File::ensureDirectoryExists(self::rootPath().DIRECTORY_SEPARATOR.$folder);
        }
    }

    /**
     * @return array<int, array{key: string, label: string, exists: bool}>
     */
    public static function foldersStatus(): array
    {
        $disk = Storage::disk(self::DISK);

        return collect(self::FOLDERS)
            ->map(fn (string $label, string $folder): array => [
                'key' => $folder,
                'label' => $label,
                'exists' => $disk->directoryExists($folder),
            ])
            ->values()
            ->all();
    }

    public static function isReady(): bool
    {
        return collect(self::foldersStatus())
            ->every(fn (array $folder): bool => $folder['exists']);
    }
}
