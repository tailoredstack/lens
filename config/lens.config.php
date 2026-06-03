<?php

declare(strict_types=1);

namespace Lens\Config;

/**
 * Lens OpenAPI configuration.
 * 
 * Copy this file to your app/ directory as `lens.config.php` to customize.
 * 
 * Environment variables (LENS_*) take precedence over file values.
 * 
 * Available environment variables:
 * - LENS_SOURCES: Comma-separated list of source directories (default: "src")
 * - LENS_TITLE: API title (default: "Tempest API")
 * - LENS_VERSION: API version (default: "1.0.0")
 * - LENS_BASE_PATH: Base path for API (default: "/")
 * - LENS_EXCLUDE: Comma-separated list of namespaces to exclude
 * - LENS_INCLUDE_INTERNAL: Include internal methods (default: false)
 * - LENS_SCALAR_ENABLED: Enable Scalar viewer (default: true)
 * - LENS_SCALAR_ROUTE: Scalar viewer route (default: "/docs")
 * - LENS_SCALAR_SPEC_ROUTE: OpenAPI JSON route (default: "/openapi.json")
 * - LENS_SCALAR_TITLE: Scalar page title (default: "API Documentation")
 * - LENS_SCALAR_SPEC_URL: External spec URL (default: null)
 * - LENS_SECURITY_SCHEMES: JSON string of security schemes
 */

// Recommended: Mix of environment variables with fallbacks
return new LensConfig(
    // Use LENS_TITLE env var, or derive from APP_NAME, or use default
    title: env('LENS_TITLE', env('APP_NAME', 'Tempest') . ' API'),
    
    // Use LENS_VERSION env var, or use default
    version: env('LENS_VERSION', '1.0.0'),
    
    // Use LENS_BASE_PATH env var, or use default
    basePath: env('LENS_BASE_PATH', '/'),
    
    // Use LENS_SOURCES env var (comma-separated), or use default
    sources: explode(',', env('LENS_SOURCES', 'src')),
    
    // Use LENS_EXCLUDE env var (comma-separated), or use default
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

// Alternative: Load entirely from environment variables
// return LensConfig::fromEnv();

// Alternative: Fully customized values
// return new LensConfig(
//     sources: ['src', 'modules'],
//     title: 'My API',
//     version: '2.0.0',
//     basePath: '/api/v1',
//     exclude: ['App\\Internal'],
//     includeInternal: false,
//     
//     scalar: new ScalarConfig(
//         enabled: true,
//         route: '/docs',
//         specRoute: '/openapi.json',
//         title: 'API Documentation',
//         specUrl: null,
//     ),
//     
//     securitySchemes: [
//         'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
//         'apiKey' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-API-Key'],
//     ],
// );
