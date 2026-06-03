<?php

declare(strict_types=1);

use Lens\Config\OpenApiConfig;
use function Tempest\env;

return new OpenApiConfig(
    // Source directories to analyze for OpenAPI generation
    sources: [base_path('app')],

    // API metadata
    title: env('OPENAPI_TITLE', 'Lens OpenAPI'),
    version: env('OPENAPI_VERSION', '0.0.0'),
    basePath: env('OPENAPI_BASE_PATH', '/api'),

    // Namespaces to exclude from analysis
    exclude: [
        // 'App\\Http\\Requests',
        // 'App\\Jobs',
        // 'App\\Events',
    ],

    // Include __magic methods (e.g., __construct, __toString)
    includeInternal: env('OPENAPI_INCLUDE_INTERNAL', false),

    // Output format: 'json' or 'yaml'
    outputFormat: env('OPENAPI_OUTPUT_FORMAT', 'json'),

    // Export path (relative to project root)
    exportPath: env('OPENAPI_EXPORT_PATH', 'openapi.json'),

    // API domain for server URL generation
    apiDomain: env('OPENAPI_API_DOMAIN'),

    // Custom servers (overrides basePath + apiDomain)
    // Example:
    // servers: [
    //     'Production' => 'https://api.example.com/v1',
    //     'Staging' => 'https://api-staging.example.com/v1',
    // ],
    servers: null,

    // Extension classes to customize behavior
    extensions: [
        // Operation transformers
        // \App\OpenApi\CustomOperationTransformer::class,

        // Type-to-schema converters
        // \App\OpenApi\CustomTypeToSchema::class,

        // Exception-to-response mappers
        // \App\OpenApi\CustomExceptionToResponse::class,
    ],
);
