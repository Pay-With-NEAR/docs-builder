<?php

declare(strict_types=1);

namespace Paywithnear\Docs\Functions;

use Paywithnear\Docs\FilesystemHelper;
use Paywithnear\Docs\Paths;
use Twig\Environment;

class TableOfContents
{
    public const ASCENDING = 'asc';
    public const DESCENDING = 'desc';

    public const SORT_BY_TITLE = 'title';
    public const SORT_BY_TIME = 'time';

    public function __construct(private readonly Environment $twig)
    {
    }

    public function __invoke(
        string $directoryToScan,
        string $direction = self::ASCENDING,
        string $sortingParam = self::SORT_BY_TITLE
    ): array {
        if (!is_dir(Paths::TEMPLATES_DIRECTORY . $directoryToScan)) {
            throw new \RuntimeException(sprintf('Cannot find directory %s.', $directoryToScan));
        }

        $result = [];
        foreach (FilesystemHelper::scanDir(Paths::TEMPLATES_DIRECTORY . $directoryToScan) as $file) {
            $templateName = str_replace(Paths::TEMPLATES_DIRECTORY, '', $file);

            $template = $this->twig->load($templateName);
            $result[] = [
                '_filename' => basename($templateName),
                'title' => $template->hasBlock('title') ? $template->renderBlock('title') : null,
                'time' => $template->hasBlock('time') ? $template->renderBlock('title') : null,
                'href' => self::resolveHref($templateName),
                'preview' => $template->hasBlock('preview') ? $template->renderBlock('preview') : null,
            ];
        }

        usort($result, static function (array $first, array $second) use ($direction, $sortingParam) {
            if ($sortingParam === self::SORT_BY_TITLE && $first['title'] && $second['title'] && $direction === self::ASCENDING) {
                return $first['title'] <=> $second['title'];
            }

            if ($sortingParam === self::SORT_BY_TITLE && $first['title'] && $second['title'] && $direction === self::DESCENDING) {
                return $second['title'] <=> $first['title'];
            }

            if ($sortingParam === self::SORT_BY_TIME && $first['time'] && $second['time'] && $direction === self::ASCENDING) {
                return $first['time'] <=> $second['time'];
            }

            if ($sortingParam === self::SORT_BY_TIME && $first['time'] && $second['time'] && $direction === self::DESCENDING) {
                return $second['time'] <=> $first['time'];
            }

            return $direction === self::ASCENDING
                ? $first['filename'] <=> $second['filename']
                : $second['filename'] <=> $first['filename'];
        });

        return array_map(
            static fn(array $item) => array_filter($item, static fn($key) => $key[0] !== '_', ARRAY_FILTER_USE_KEY),
            $result
        );
    }

    private static function resolveHref(string $templatePath): ?string
    {
        $explodedPath = explode('/', $templatePath);
        if ($explodedPath[0] !== 'pages') {
            return null;
        }

        foreach ($explodedPath as $part) {
            if ($part[0] === '.') {
                return null;
            }
        }

        return '/' . substr(implode('/', array_slice($explodedPath, 1)), 0, -5) . '/';
    }
}
