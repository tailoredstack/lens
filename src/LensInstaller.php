<?php

declare(strict_types=1);

namespace Lens;

use Tempest\Console\Console;
use Tempest\Core\Installer;
use Tempest\Core\PublishesFiles;
use function Tempest\app_path;
use function Tempest\root_path;

/**
 * Lens installer for Tempest.
 */
final class LensInstaller
{
    use PublishesFiles;

    public function __construct(
        private readonly Console $console = new Console(),
    ) {}

    #[Installer('Lens OpenAPI', alias: ['lens', 'openapi'])]
    public function install(
        ?bool $publishConfig = null,
    ): void {
        $this->console->writeln('');
        $this->console->writeln('<fg=yellow>╭──────────────────────────────────────────╮</>');
        $this->console->writeln('<fg=yellow>│</>  <fg=cyan>Lens OpenAPI Installer</>                    <fg=yellow>│</>');
        $this->console->writeln('<fg=yellow>╰──────────────────────────────────────────╯</>');
        $this->console->writeln('');

        // Publish config file
        $this->publishConfig();

        $this->console->writeln('');
        $this->console->writeln('<fg=green>✓ Lens OpenAPI installed successfully!</>');
        $this->console->writeln('');
        $this->console->writeln('  <fg=cyan>Commands:</>');
        $this->console->writeln('    <fg=white>php bin/lens generate</>     - Generate OpenAPI spec');
        $this->console->writeln('    <fg=white>php bin/lens config:publish</> - Re-publish config');
        $this->console->writeln('');
        $this->console->writeln('  <fg=cyan>Routes (auto-registered):</>');
        $this->console->writeln('    <fg=white>/docs</>         - Scalar API documentation viewer');
        $this->console->writeln('    <fg=white>/openapi.json</> - OpenAPI specification JSON');
        $this->console->writeln('');
        $this->console->writeln('  <fg=yellow>Start your Tempest app and visit /docs!</>');
        $this->console->writeln('');
    }

    /**
     * Publish the lens.config.php file to the application.
     * 
     * Installs in app/ directory if it exists, otherwise in root.
     */
    public function publishConfig(): void
    {
        $sourcePath = __DIR__ . '/../stubs/lens.config.stub.php';
        
        // Determine target path
        $targetPath = null;
        if (is_dir(app_path())) {
            $targetPath = app_path('lens.config.php');
        } else {
            $targetPath = root_path('lens.config.php');
        }

        // Check if already exists
        if (file_exists($targetPath)) {
            $this->console->writeln('<fg=yellow>⚠ Config file already exists. Skipping.</>');
            return;
        }

        // Ensure directory exists
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        copy($sourcePath, $targetPath);

        $relativePath = str_replace(getcwd() . '/', '', $targetPath);
        $this->console->writeln("<fg=green>✓</> Published <fg=cyan>{$relativePath}</>");
    }
}
