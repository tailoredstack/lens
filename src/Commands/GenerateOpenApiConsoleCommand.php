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
use Tempest\Container\Container;

final class GenerateOpenApiConsoleCommand
{
    use HasConsole;
    use HandlesArguments;
    use HandlesOptions;

    public function __construct(
        private Container $container,
    ) {}

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
        // Try to load config from container, or create new one
        try {
            $config = $this->container->get(OpenApiConfig::class);
        } catch (\Throwable) {
            $config = new OpenApiConfig();
        }

        // Override with CLI options
        $config = new OpenApiConfig(
            sources: $config->sources,
            title: $title ?? $config->title,
            version: $version ?? $config->version,
            basePath: $basePath ?? $config->basePath,
            exclude: $exclude !== [] ? $exclude : $config->exclude,
            includeInternal: $includeInternal || $config->includeInternal,
            outputFormat: $format,
            exportPath: $config->exportPath,
            apiDomain: $config->apiDomain,
            servers: $config->servers,
            ui: $config->ui,
            extensions: $config->extensions,
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
