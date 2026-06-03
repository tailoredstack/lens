<?php

declare(strict_types=1);

namespace Lens\Discovery;

use Lens\Config\OpenApiConfig;
use Lens\OpenApi;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;

final class OpenApiDiscovery implements Discovery
{
    private bool $isInitialized = false;

    public function discover(DiscoveryLocation $location): void
    {
        // Called during Tempest boot to discover controllers
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
        return OpenApi::generate($config);
    }
}
