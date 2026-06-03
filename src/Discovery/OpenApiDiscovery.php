<?php

declare(strict_types=1);

namespace Lens\Discovery;

use Lens\Config\OpenApiConfig;
use Lens\Facade;
use Lens\OpenApi;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;

final class OpenApiDiscovery implements Discovery
{
    private bool $isInitialized = false;

    public function discover(DiscoveryLocation $location): void
    {
        // Called during Tempest boot to discover controllers and other discoverable items
        // Configuration files (*.config.php) are auto-discovered by Tempest
    }

    public function initialize(): void
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;
    }

    public function generateOpenApi(OpenApiConfig $config): array
    {
        // Use Facade for consistent API
        return Facade::generate($config);
    }
}
