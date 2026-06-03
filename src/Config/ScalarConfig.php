<?php

declare(strict_types=1);

namespace Lens\Config;

/**
 * Scalar viewer configuration.
 * 
 * Values are loaded from environment variables with LENS_SCALAR_* prefix.
 */
final readonly class ScalarConfig
{
    public function __construct(
        /**
         * Enable Scalar viewer routes.
         * Env: LENS_SCALAR_ENABLED
         */
        public bool $enabled = true,
        
        /**
         * Route path for Scalar viewer.
         * Env: LENS_SCALAR_ROUTE
         */
        public string $route = '/docs',
        
        /**
         * Route path for OpenAPI JSON spec.
         * Env: LENS_SCALAR_SPEC_ROUTE
         */
        public string $specRoute = '/openapi.json',
        
        /**
         * Page title for Scalar viewer.
         * Env: LENS_SCALAR_TITLE
         */
        public string $title = 'API Documentation',
        
        /**
         * External spec URL (if null, uses internal specRoute).
         * Env: LENS_SCALAR_SPEC_URL
         */
        public ?string $specUrl = null,
    ) {}

    /**
     * Get the spec URL (external or internal).
     */
    public function getSpecUrl(): string
    {
        return $this->specUrl ?? $this->specRoute;
    }
}
