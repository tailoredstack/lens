<?php

declare(strict_types=1);

use Lens\Config\OpenApiConfig;

return new OpenApiConfig(
    sources: [getcwd() . '/src'],
    title: env('OPENAPI_TITLE', 'Lens OpenAPI'),
    version: env('OPENAPI_VERSION', '0.0.0'),
    basePath: env('OPENAPI_BASE_PATH', '/api'),
    exclude: [
        // 'App\\Internal',
        // 'App\\Tests',
    ],
    includeInternal: false,
    outputFormat: 'json',

    // Export path for generated spec (relative to project root)
    exportPath: env('OPENAPI_EXPORT_PATH', 'openapi.json'),

    // API domain for server URL generation
    apiDomain: env('OPENAPI_API_DOMAIN'),

    // Additional servers (if null, generated from basePath and apiDomain)
    servers: null,

    // UI customization (for future web UI)
    ui: [
        'title' => null,
        'theme' => 'system',
        'hide_try_it' => false,
        'hide_schemas' => true,
        'logo' => '',
        'layout' => 'responsive',
    ],

    // Extension classes to customize behavior
    extensions: [
        // Operation transformers
        // \Lens\Extensions\Operation\CustomOperationTransformer::class,

        // Type-to-schema converters
        // \Lens\Extensions\TypeToSchema\CustomTypeToSchema::class,

        // Exception-to-response mappers
        // \Lens\Extensions\Exception\CustomExceptionToResponse::class,
    ],
);
