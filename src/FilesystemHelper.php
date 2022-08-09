<?php

declare(strict_types=1);

namespace Paywithnear\Docs;

class FilesystemHelper
{
    public static function scanDir(string $directory): array
    {
        $results = [];

        foreach (scandir($directory) as $page) {
            if ($page[0] === '.') {
                continue; // skip "hidden" directories
            }

            $path = rtrim($directory, '/') . DIRECTORY_SEPARATOR . ltrim($page, '/');
            if (!is_dir($path)) {
                $results[] = $path;
            } else {
                $results = [...$results, ...self::scanDir($path)];
            }
        }

        return $results;
    }

    public static function rmDir(string $path, bool $rmRoot = false): void
    {
        $files = glob($path . '/*');

        foreach ($files as $file) {
            is_dir($file) ? self::rmDir($file, true) : unlink($file);
        }

        if ($rmRoot) {
            rmdir($path);
        }
    }

    public static function ensureDirExists(string $path): void
    {
        if (
            !is_dir($path)
            && !mkdir($concurrentDirectory = $path, 0755, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(
                sprintf('Directory "%s" was not created', $concurrentDirectory)
            );
        }
    }
}
