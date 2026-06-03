<?php

declare(strict_types=1);

namespace Lens\Config;

use function Tempest\env;

/**
 * Lens OpenAPI configuration.
 * 
 * Customize values here or use environment variables (LENS_*).
 * 
 * Environment variables available:
 * - LENS_TITLE: API title
 * - LENS_VERSION: API version
 * - LENS_BASE_PATH: Base path
 * - LENS_SOURCES: Comma-separated source directories
 * - LENS_EXCLUDE: Comma-separated namespaces to exclude
 * - LENS_SCALAR_ENABLED: Enable Scalar viewer
 * - LENS_SCALAR_ROUTE: Scalar viewer route
 * - LENS_SCALAR_SPEC_ROUTE: OpenAPI JSON route
 * - LENS_SCALAR_TITLE: Scalar page title
 * - LENS_SCALAR_SPEC_URL: External spec URL
 * - LENS_SECURITY_SCHEMES: JSON string of security schemes
 */
return new LensConfig(
    // Basic configuration
    title: env('LENS_TITLE', env('APP_NAME', 'Tempest') . ' API'),
    version: env('LENS_VERSION', '1.0.0'),
    basePath: env('LENS_BASE_PATH', '/'),
    sources: explode(',', env('LENS_SOURCES', 'src')),
    exclude: array_filter(explode(',', env('LENS_EXCLUDE', ''))),
    
    // Scalar viewer configuration
    scalar: new ScalarConfig(
        enabled: env('LENS_SCALAR_ENABLED', true),
        route: env('LENS_SCALAR_ROUTE', '/docs'),
        specRoute: env('LENS_SCALAR_SPEC_ROUTE', '/openapi.json'),
        title: env('LENS_SCALAR_TITLE', env('APP_NAME', 'API') . ' Documentation'),
        specUrl: env('LENS_SCALAR_SPEC_URL'),
    ),
    
    // Security schemes
    securitySchemes: [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
        ],
    ],
);
