#!/usr/bin/env php
<?php

use Paywithnear\Docs\Command\Builder;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application();
$application->add(new Builder());

try {
    $application->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
