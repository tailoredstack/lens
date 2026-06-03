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

        // Ask if user wants to publish config
        $publishConfig ??= $this->console->confirm(
            question: 'Do you want to publish the configuration file?',
            default: true,
        );

        if ($publishConfig) {
            $this->publishConfig();
        }

        $this->console->writeln('');
        $this->console->writeln('<fg=green>✓ Lens OpenAPI installed successfully!</>');
        $this->console->writeln('');
        $this->console->writeln('  <fg=cyan>Commands:</>');
        $this->console->writeln('    <fg=white>php bin/lens generate</>     - Generate OpenAPI spec');
        $this->console->writeln('    <fg=white>php bin/lens config:publish</> - Publish config file');
        $this->console->writeln('');
        $this->console->writeln('  <fg=cyan>Routes:</>');
        $this->console->writeln('    <fg=white>/docs</>         - Scalar API documentation viewer');
        $this->console->writeln('    <fg=white>/openapi.json</> - OpenAPI specification JSON');
        $this->console->writeln('');
        $this->console->writeln('  <fg=yellow>Start your Tempest app and visit /docs to view your API documentation!</>');
        $this->console->writeln('');
    }

    /**
     * Publish the lens.config.php file to the application.
     * 
     * Installs in app/ directory if it exists, otherwise in root.
     */
    public function publishConfig(): void
    {
        $sourcePath = __DIR__ . '/../config/lens.config.php';
        
        // Determine target path
        $targetPath = null;
        if (is_dir(app_path())) {
            $targetPath = app_path('lens.config.php');
        } else {
            $targetPath = root_path('lens.config.php');
        }

        // Use PublishesFiles trait to publish
        $this->publish(
            source: $sourcePath,
            destination: $targetPath,
            confirm: false, // Already confirmed in install()
        );
    }
}
