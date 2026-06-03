<?php

declare(strict_types=1);

namespace Lens\Config;

final readonly class OpenApiConfig
{
    /** @var array<string> */
    public array $sources;

    public function __construct(
        array $sources = [],
        public string $title = 'Lens OpenAPI',
        public string $version = '0.0.0',
        public string $basePath = '/',
        public array $exclude = [],
    ) {
        $this->sources = $sources;
    }
}
