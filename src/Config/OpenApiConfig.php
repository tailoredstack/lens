<?php

declare(strict_types=1);

namespace Lens\Config;

final readonly class OpenApiConfig
{
    public function __construct(
        public array $sources = [],
        public string $title = 'Lens OpenAPI',
        public string $version = '0.0.0',
        public string $basePath = '/',
        public array $exclude = [],
        public bool $includeInternal = false,
        public string $outputFormat = 'json',
        public string $exportPath = 'openapi.json',
        public ?string $apiDomain = null,
        public ?array $servers = null,
        public array $extensions = [],
    ) {}
}
