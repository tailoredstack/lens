<?php

declare(strict_types=1);

namespace Lens\Config;

final readonly class OpenApiConfig
{
    /** @var array<string> */
    public array $sources;

    /** @var array<string> */
    public array $exclude;

    /** @var array<class-string> */
    public array $extensions;

    /** @var array<string>|null */
    public ?array $servers;

    public function __construct(
        array $sources = [],
        public string $title = 'Lens OpenAPI',
        public string $version = '0.0.0',
        public string $basePath = '/',
        public array $exclude = [],
        public bool $includeInternal = false,
        public string $outputFormat = 'json',
        public string $exportPath = 'openapi.json',
        public ?string $apiDomain = null,
        public ?array $servers = null,
        public array $ui = [],
        public array $extensions = [],
    ) {
        $this->sources = $sources;
        $this->exclude = $exclude;
        $this->extensions = $extensions;
        $this->servers = $servers;
    }
}
