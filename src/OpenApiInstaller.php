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
            source: __DIR__ . '/../config/openapi.php',
            destination: 'config/openapi.php',
        );

        $this->console->success('Lens OpenAPI installed. Config published to config/openapi.php');
    }
}
