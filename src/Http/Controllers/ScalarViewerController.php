<?php

declare(strict_types=1);

namespace Lens\Http\Controllers;

use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;
use Lens\Builder\OpenApiBuilder;
use Tempest\Http\Get;
use Tempest\Http\Response;
use Tempest\Http\Status;

/**
 * Controller for serving Scalar OpenAPI viewer.
 * 
 * Routes are automatically discovered by Tempest.
 * Configure in lens.config.php:
 * - lens.scalar.enabled - Enable/disable viewer
 * - lens.scalar.route - Viewer route path (default: /docs)
 * - lens.scalar.spec_route - Spec JSON route (default: /openapi.json)
 */
final class ScalarViewerController
{
    /**
     * Serve the Scalar HTML viewer.
     */
    #[Get('/docs')]
    public function view(): Response
    {
        /** @var \Lens\Config\LensConfig $lensConfig */
        $lensConfig = config('lens');
        
        // Check if viewer is enabled
        if (!$lensConfig?->scalar->enabled) {
            return Response::notFound();
        }

        $html = $this->getScalarHtml(
            $lensConfig->scalar->getSpecUrl(),
            $lensConfig->scalar->title
        );
        
        return Response::html($html)
            ->withStatus(Status::OK);
    }

    /**
     * Serve the OpenAPI JSON specification.
     */
    #[Get('/openapi.json')]
    public function spec(): Response
    {
        /** @var \Lens\Config\LensConfig $lensConfig */
        $lensConfig = config('lens');
        
        // Check if viewer is enabled
        if (!$lensConfig) {
            return Response::notFound();
        }

        $engine = new Engine($lensConfig->sources);
        $builder = new OpenApiBuilder();
        $spec = $builder->buildFromInfer($engine, $lensConfig->toOpenApiConfig());
        
        return Response::json($spec)
            ->withStatus(Status::OK);
    }

    private function getScalarHtml(string $specUrl, string $title): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <title>{$title}</title>
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
}
