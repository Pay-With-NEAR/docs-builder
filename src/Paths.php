<?php

declare(strict_types=1);

namespace Paywithnear\Docs;

interface Paths
{
    public const ASSETS_DIRECTORY = __DIR__ . '/../assets/';
    public const TEMPLATES_DIRECTORY = __DIR__ . '/../templates/';
    public const BUILD_DIR = __DIR__ . '/../%s/';
    public const ASSETS_BUILD_DIR = __DIR__ . '/../%s/assets/';
}
