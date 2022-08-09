<?php

declare(strict_types=1);

namespace Paywithnear\Docs\Command;

use Paywithnear\Docs\FilesystemHelper;
use Paywithnear\Docs\Functions\TableOfContents;
use Paywithnear\Docs\Paths;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

#[AsCommand(name: 'app:build', description: 'Builds documentation to public directory', hidden: false)]
class Builder extends Command
{
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
        $buildDir = sprintf(Paths::BUILD_DIR, $input->getOption('target_dir'));

        FilesystemHelper::rmDir($buildDir);
        FilesystemHelper::ensureDirExists($buildDir);

        $loader = new FilesystemLoader(Paths::TEMPLATES_DIRECTORY);
        $twig = new Environment($loader, []);
        $twig->addFunction(new TwigFunction('table_of_contents', new TableOfContents($twig)));

        $output->writeln('');
        $output->writeln('Scanning directory: ' . Paths::TEMPLATES_DIRECTORY . 'pages');
        $output->writeln('Found pages:');

        foreach (FilesystemHelper::scanDir(Paths::TEMPLATES_DIRECTORY . 'pages') as $path) {
            $relativePath = str_replace(Paths::TEMPLATES_DIRECTORY . 'pages', '', $path);
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

        $assetsBuildDir = sprintf(Paths::ASSETS_BUILD_DIR, $input->getOption('target_dir'));
        foreach (FilesystemHelper::scanDir(Paths::ASSETS_DIRECTORY) as $asset) {
            $relativePath = str_replace(Paths::ASSETS_DIRECTORY, '', $asset);
            $relativePath = ltrim($relativePath, '/');

            $output->writeln($relativePath);
            if (dirname($relativePath) !== '_root') {
                FilesystemHelper::ensureDirExists($assetsBuildDir . dirname($relativePath));
                copy(Paths::ASSETS_DIRECTORY . $relativePath, $assetsBuildDir . $relativePath);
            } else {
                copy(Paths::ASSETS_DIRECTORY . $relativePath, $assetsBuildDir . '/../' . basename($relativePath));
            }
        }

        return Command::SUCCESS;
    }

    private static function processIndexFile(Environment $twig, string $buildDir, string $path): void
    {
        $targetPath = dirname($path);
        FilesystemHelper::ensureDirExists($buildDir . $targetPath);
        $targetPath = $buildDir . $targetPath . DIRECTORY_SEPARATOR . 'index.html';

        $contents = $twig->render('pages/' . $path);
        file_put_contents($targetPath, $contents);
    }

    private static function processNamedFile(Environment $twig, string $buildDir, string $path): void
    {
        $targetPath = dirname($path);
        $targetPath = $buildDir . $targetPath . DIRECTORY_SEPARATOR . basename($path);
        $targetPath = substr($targetPath, 0, -5);
        FilesystemHelper::ensureDirExists($targetPath);

        $targetPath .= '/index.html';
        $contents = $twig->render('pages/' . $path);
        file_put_contents($targetPath, $contents);
    }
}
