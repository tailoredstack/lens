<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Lens OpenAPI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Lens OpenAPI generator and Scalar viewer.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Source Directories
    |--------------------------------------------------------------------------
    |
    | Directories to scan for Tempest controllers when generating OpenAPI spec.
    |
    */
    'sources' => ['src'],

    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    */
    'title' => 'Tempest API',
    'version' => '1.0.0',
    'base_path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Exclude Patterns
    |--------------------------------------------------------------------------
    |
    | Namespace patterns to exclude from OpenAPI generation.
    |
    */
    'exclude' => [],

    /*
    |--------------------------------------------------------------------------
    | Scalar Viewer Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Scalar OpenAPI viewer.
    |
    */
    'scalar' => [
        /*
        |--------------------------------------------------------------------------
        | Enable Scalar Viewer
        |--------------------------------------------------------------------------
        |
        | Set to false to disable the Scalar viewer routes.
        |
        */
        'enabled' => true,

        /*
        |--------------------------------------------------------------------------
        | Viewer Route Path
        |--------------------------------------------------------------------------
        |
        | The route where the Scalar viewer will be accessible.
        |
        */
        'route' => '/docs',

        /*
        |--------------------------------------------------------------------------
        | Specification Route Path
        |--------------------------------------------------------------------------
        |
        | The route where the OpenAPI JSON spec will be served.
        |
        */
        'spec_route' => '/openapi.json',

        /*
        |--------------------------------------------------------------------------
        | Page Title
        |--------------------------------------------------------------------------
        */
        'title' => 'API Documentation',

        /*
        |--------------------------------------------------------------------------
        | External Spec URL
        |--------------------------------------------------------------------------
        |
        | If set, Scalar will load the spec from this URL instead of the
        | internal spec_route. Useful for hosting spec separately.
        | Leave null to use internal spec.
        |
        */
        'spec_url' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Schemes
    |--------------------------------------------------------------------------
    |
    | Define security schemes for your API.
    |
    */
    'security_schemes' => [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
        ],
    ],
];
