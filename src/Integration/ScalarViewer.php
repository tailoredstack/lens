<?php

declare(strict_types=1);

namespace Lens\Integration;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\LensConfig;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

/**
 * Scalar OpenAPI viewer integration for Tempest.
 *
 * Provides a route to serve the OpenAPI spec with Scalar UI.
 */
class ScalarViewer
{
    public function __construct(
        private readonly string $routePath = '/docs',
        private readonly string $specPath = '/openapi.json',
        private readonly string $title = 'API Documentation',
        private readonly ?string $specUrl = null,
    ) {}

    /**
     * Get the HTML for Scalar viewer.
     */
    public function getHtml(): string
    {
        $specUrl = $this->specUrl ?? $this->specPath;

        return <<<HTML
        <!DOCTYPE html>
        <html>
          <head>
            <title>{$this->title}</title>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <style>
              body { margin: 0; padding: 0; }
            </style>
          </head>
          <body>
            <script
              id="api-reference"
              data-url="{$specUrl}"
              data-proxy-url="https://proxy.scalar.com">
            </script>
            <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
          </body>
        </html>
        HTML;
    }

    /**
     * Get the route path for the viewer.
     */
    public function getRoutePath(): string
    {
        return $this->routePath;
    }

    /**
     * Get the spec path.
     */
    public function getSpecPath(): string
    {
        return $this->specPath;
    }

    /**
     * Generate OpenAPI spec for the given sources.
     */
    public function generateSpec(array $sources, OpenApiConfig $config): array
    {
        $engine = new Engine($sources);
        $builder = new OpenApiBuilder();

        return $builder->buildFromInfer($engine, $config);
    }

    /**
     * Generate OpenAPI spec from LensConfig.
     */
    public function generateSpecFromConfig(LensConfig $lensConfig): array
    {
        return $this->generateSpec($lensConfig->sources, $lensConfig->toOpenApiConfig());
    }
}
