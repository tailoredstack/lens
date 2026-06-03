<?php

declare(strict_types=1);

namespace Lens;

use Tempest\Core\Installer;
use Tempest\Core\IsInstaller;
use Tempest\Core\PublishesFiles;

final class OpenApiInstaller implements Installer
{
    use IsInstaller;
    use PublishesFiles;

    public function install(): void
    {
        $this->publish(
            source: __DIR__ . '/../config/lens.config.php',
            destination: 'lens.config.php',
        );

        $this->console->success('Lens OpenAPI installed. Config published to lens.config.php');
        $this->console->writeln('Customize your configuration in lens.config.php');
        $this->console->writeln('Generate your spec with: <info>php tempest openapi:generate</info>');
    }
}
