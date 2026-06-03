<?php

declare(strict_types=1);

namespace Lens\Commands;

use Lens\Config\OpenApiConfig;
use Lens\Discovery\OpenApiDiscovery;
use Symfony\Component\Yaml\Yaml;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HandlesArguments;
use Tempest\Console\HandlesOptions;
use Tempest\Console\HasConsole;

final class GenerateOpenApiConsoleCommand
{
    use HasConsole;
    use HandlesArguments;
    use HandlesOptions;

    #[ConsoleCommand(name: 'openapi:generate', description: 'Generate OpenAPI specification')]
    public function __invoke(
        string $output = 'openapi.json',
        string $format = 'json',
        ?string $title = null,
        ?string $version = null,
        ?string $basePath = null,
        array $exclude = [],
        bool $includeInternal = false,
    ): int {
        $config = new OpenApiConfig(
            sources: [],
            title: $title ?? 'Lens OpenAPI',
            version: $version ?? '0.0.0',
            basePath: $basePath ?? '/',
            exclude: $exclude,
            includeInternal: $includeInternal,
            outputFormat: $format,
        );

        $discovery = new OpenApiDiscovery();
        $spec = $discovery->generateOpenApi($config);

        if ($format === 'yaml' || $format === 'yml') {
            $content = Yaml::dump($spec, 10, 2);
        } else {
            $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($output === '-') {
            $this->writeln($content);
        } else {
            file_put_contents($output, $content . PHP_EOL);
            $this->success("Generated: {$output}");
        }

        return 0;
    }
}
