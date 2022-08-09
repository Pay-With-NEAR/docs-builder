<?php

declare(strict_types=1);

namespace Paywithnear\Docs\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

#[AsCommand(name: 'app:build', description: 'Builds documentation to public directory', hidden: false)]
class Builder extends Command
{
    private const ASSETS_DIRECTORY = __DIR__ . '/../../assets/';
    private const TEMPLATES_DIRECTORY = __DIR__ . '/../../templates/';
    private const BUILD_DIR = __DIR__ . '/../../%s/';
    private const ASSETS_BUILD_DIR = __DIR__ . '/../../%s/assets/';

    protected function configure(): void
    {
        $this->setHelp('This command builds documentation files');
        $this->addOption('target_dir', 't', InputOption::VALUE_OPTIONAL, 'Output directory (default: docs)', 'docs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('###########################################################');
        $output->writeln('##                    PayWithNEAR Docs                   ##');
        $output->writeln('###########################################################');
        $output->writeln('');
        $output->writeln('Scanning directory: ' . self::TEMPLATES_DIRECTORY . 'pages');
        $output->writeln('Found pages:');

        $buildDir = sprintf(self::BUILD_DIR, $input->getOption('target_dir'));

        self::rmDir($buildDir);
        self::ensureDirExists($buildDir);

        $loader = new FilesystemLoader(self::TEMPLATES_DIRECTORY);
        $twig = new Environment($loader, []);

        foreach (self::scanDir(self::TEMPLATES_DIRECTORY . 'pages') as $path) {
            $relativePath = str_replace(self::TEMPLATES_DIRECTORY . 'pages', '', $path);
            $relativePath = ltrim($relativePath, '/');

            $output->writeln($relativePath);
            if (basename($relativePath) === '_index.twig') {
                self::processIndexFile($twig, $buildDir, $relativePath);
            } else {
                self::processNamedFile($twig, $buildDir, $relativePath);
            }
        }

        $output->writeln('');
        $output->writeln('Found assets:');

        $assetsBuildDir = sprintf(self::ASSETS_BUILD_DIR, $input->getOption('target_dir'));
        foreach (self::scanDir(self::ASSETS_DIRECTORY) as $asset) {
            $relativePath = str_replace(self::ASSETS_DIRECTORY, '', $asset);
            $relativePath = ltrim($relativePath, '/');

            $output->writeln($relativePath);
            if (dirname($relativePath) !== '_root') {
                self::ensureDirExists($assetsBuildDir . dirname($relativePath));
                copy(self::ASSETS_DIRECTORY . $relativePath, $assetsBuildDir . $relativePath);
            } else {
                copy(self::ASSETS_DIRECTORY . $relativePath, $assetsBuildDir . '/../' . basename($relativePath));
            }
        }

        return Command::SUCCESS;
    }

    private static function processIndexFile(Environment $twig, string $buildDir, string $path): void
    {
        $targetPath = dirname($path);
        self::ensureDirExists($buildDir . $targetPath);
        $targetPath = $buildDir . $targetPath . DIRECTORY_SEPARATOR . 'index.html';

        $contents = $twig->render('pages/' . $path);
        file_put_contents($targetPath, $contents);
    }

    private static function processNamedFile(Environment $twig, string $buildDir, string $path): void
    {
        $targetPath = dirname($path);
        $targetPath = $buildDir . $targetPath . DIRECTORY_SEPARATOR . basename($path);
        $targetPath = substr($targetPath, 0, -5);
        self::ensureDirExists($targetPath);

        $targetPath .= '/index.html';
        $contents = $twig->render('pages/' . $path);
        file_put_contents($targetPath, $contents);
    }

    private static function scanDir(string $directory): array
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

    private static function rmDir(string $path, bool $rmRoot = false): void
    {
        $files = glob($path . '/*');

        foreach ($files as $file) {
            is_dir($file) ? self::rmDir($file, true) : unlink($file);
        }

        if ($rmRoot) {
            rmdir($path);
        }
    }

    private static function ensureDirExists(string $path): void
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
