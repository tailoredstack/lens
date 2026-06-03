<?php

declare(strict_types=1);

namespace Lens\Config;

/**
 * Lens OpenAPI configuration.
 * 
 * Values are loaded from environment variables with LENS_* prefix
 * or from lens.config.php file.
 */
final readonly class LensConfig
{
    public function __construct(
        /**
         * Directories to scan for Tempest controllers.
         * Env: LENS_SOURCES (comma-separated paths)
         * @var string[]
         */
        public array $sources = ['src'],
        
        /**
         * API title for OpenAPI spec.
         * Env: LENS_TITLE
         */
        public string $title = 'Tempest API',
        
        /**
         * API version for OpenAPI spec.
         * Env: LENS_VERSION
         */
        public string $version = '1.0.0',
        
        /**
         * Base path for API endpoints.
         * Env: LENS_BASE_PATH
         */
        public string $basePath = '/',
        
        /**
         * Namespace patterns to exclude from OpenAPI generation.
         * Env: LENS_EXCLUDE (comma-separated patterns)
         * @var string[]
         */
        public array $exclude = [],
        
        /**
         * Include internal methods (starting with __).
         * Env: LENS_INCLUDE_INTERNAL
         */
        public bool $includeInternal = false,
        
        /**
         * Scalar viewer configuration.
         */
        public ScalarConfig $scalar = new ScalarConfig(),
        
        /**
         * Security schemes for OpenAPI spec.
         * @var array<string, array>
         */
        public array $securitySchemes = [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
        ],
    ) {}

    /**
     * Create configuration from environment variables.
     */
    public static function fromEnv(): self
    {
        $sources = env('LENS_SOURCES');
        $sourcesArray = $sources !== null && $sources !== ''
            ? array_map('trim', explode(',', $sources))
            : ['src'];

        $exclude = env('LENS_EXCLUDE');
        $excludeArray = $exclude !== null && $exclude !== ''
            ? array_map('trim', explode(',', $exclude))
            : [];

        return new self(
            sources: $sourcesArray,
            title: env('LENS_TITLE', 'Tempest API'),
            version: env('LENS_VERSION', '1.0.0'),
            basePath: env('LENS_BASE_PATH', '/'),
            exclude: $excludeArray,
            includeInternal: env('LENS_INCLUDE_INTERNAL', false),
            scalar: new ScalarConfig(
                enabled: env('LENS_SCALAR_ENABLED', true),
                route: env('LENS_SCALAR_ROUTE', '/docs'),
                specRoute: env('LENS_SCALAR_SPEC_ROUTE', '/openapi.json'),
                title: env('LENS_SCALAR_TITLE', 'API Documentation'),
                specUrl: env('LENS_SCALAR_SPEC_URL'),
            ),
            securitySchemes: self::parseSecuritySchemesFromEnv(),
        );
    }

    /**
     * Parse security schemes from environment variable.
     * Expects JSON string in LENS_SECURITY_SCHEMES.
     */
    private static function parseSecuritySchemesFromEnv(): array
    {
        $securitySchemes = env('LENS_SECURITY_SCHEMES');
        
        if ($securitySchemes === null || $securitySchemes === '') {
            return [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ];
        }

        $decoded = json_decode($securitySchemes, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
        ];
    }

    /**
     * Convert to OpenApiConfig for generation.
     */
    public function toOpenApiConfig(): OpenApiConfig
    {
        return new OpenApiConfig(
            sources: $this->sources,
            title: $this->title,
            version: $this->version,
            basePath: $this->basePath,
            exclude: $this->exclude,
            includeInternal: $this->includeInternal,
            securitySchemes: $this->securitySchemes,
        );
    }
}
