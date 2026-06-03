<?php

declare(strict_types=1);

namespace Lens\Commands;

use Lens\OpenApi;
use Lens\Config\OpenApiConfig;
use Symfony\Component\Yaml\Yaml;

final class GenerateOpenApiCommand
{
    public function __invoke(
        string $output = 'openapi.json',
        string $format = 'json',
        ?string $title = null,
        ?string $version = null,
        ?string $basePath = null,
    ): int {
        $config = new OpenApiConfig(
            sources: [],
            title: $title ?? 'Lens OpenAPI',
            version: $version ?? '0.0.0',
            basePath: $basePath ?? '/',
        );

        $spec = OpenApi::generate($config);

        if ($format === 'yaml' || $format === 'yml') {
            $content = Yaml::dump($spec, 10, 2);
        } else {
            $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($output === '-') {
            echo $content . PHP_EOL;
        } else {
            file_put_contents($output, $content . PHP_EOL);
            echo "Generated: {$output}" . PHP_EOL;
        }

        return 0;
    }
}
