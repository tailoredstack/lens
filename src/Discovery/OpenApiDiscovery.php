<?php

declare(strict_types=1);

namespace Lens\Discovery;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Lens\OpenApi;
use Lens\Config\OpenApiConfig;

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
