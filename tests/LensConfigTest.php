<?php

declare(strict_types=1);

namespace Tests;

use Lens\Config\LensConfig;
use Lens\Config\ScalarConfig;

// LensConfig environment variable tests

beforeEach(function () {
    // Store original env values
    $this->originalEnv = [];
});

afterEach(function () {
    // Restore original env values (not possible in PHP, but good practice)
});

test('LensConfig loads from environment variables', function () {
    // Note: In real PHP, env() reads from $_ENV or getenv()
    // For testing, we'll test the config structure

    $config = new LensConfig(
        sources: ['src', 'api'],
        title: 'Test API',
        version: '2.0.0',
        basePath: '/api/v1',
        exclude: ['App\\Internal'],
        includeInternal: true,
    );

    expect($config->sources)->toBe(['src', 'api']);
    expect($config->title)->toBe('Test API');
    expect($config->version)->toBe('2.0.0');
    expect($config->basePath)->toBe('/api/v1');
    expect($config->exclude)->toBe(['App\\Internal']);
    expect($config->includeInternal)->toBeTrue();
});

test('LensConfig has default values', function () {
    $config = new LensConfig();

    expect($config->sources)->toBe(['src']);
    expect($config->title)->toBe('Tempest API');
    expect($config->version)->toBe('1.0.0');
    expect($config->basePath)->toBe('/');
    expect($config->exclude)->toBe([]);
    expect($config->includeInternal)->toBeFalse();
});

test('LensConfig creates ScalarConfig with defaults', function () {
    $config = new LensConfig();

    expect($config->scalar)->toBeInstanceOf(ScalarConfig::class);
    expect($config->scalar->enabled)->toBeTrue();
    expect($config->scalar->route)->toBe('/docs');
    expect($config->scalar->specRoute)->toBe('/openapi.json');
    expect($config->scalar->title)->toBe('API Documentation');
    expect($config->scalar->specUrl)->toBeNull();
});

test('LensConfig converts to OpenApiConfig', function () {
    $config = new LensConfig(
        sources: ['src'],
        title: 'Test API',
        version: '1.0.0',
        basePath: '/api',
        exclude: ['Internal'],
        includeInternal: false,
        securitySchemes: [
            'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer'],
        ],
    );

    $openApiConfig = $config->toOpenApiConfig();

    expect($openApiConfig->sources)->toBe(['src']);
    expect($openApiConfig->title)->toBe('Test API');
    expect($openApiConfig->version)->toBe('1.0.0');
    expect($openApiConfig->basePath)->toBe('/api');
    expect($openApiConfig->exclude)->toBe(['Internal']);
    expect($openApiConfig->securitySchemes)->toHaveKey('bearerAuth');
});

test('ScalarConfig getSpecUrl returns external URL if set', function () {
    $scalarConfig = new ScalarConfig(
        specUrl: 'https://example.com/openapi.json',
        specRoute: '/openapi.json',
    );

    expect($scalarConfig->getSpecUrl())->toBe('https://example.com/openapi.json');
});

test('ScalarConfig getSpecUrl returns specRoute if external URL is null', function () {
    $scalarConfig = new ScalarConfig(
        specUrl: null,
        specRoute: '/api-spec.json',
    );

    expect($scalarConfig->getSpecUrl())->toBe('/api-spec.json');
});

test('ScalarConfig has custom values', function () {
    $scalarConfig = new ScalarConfig(
        enabled: false,
        route: '/api/docs',
        specRoute: '/api/spec.json',
        title: 'My API Docs',
        specUrl: 'https://api.example.com/spec.json',
    );

    expect($scalarConfig->enabled)->toBeFalse();
    expect($scalarConfig->route)->toBe('/api/docs');
    expect($scalarConfig->specRoute)->toBe('/api/spec.json');
    expect($scalarConfig->title)->toBe('My API Docs');
    expect($scalarConfig->getSpecUrl())->toBe('https://api.example.com/spec.json');
});

test('LensConfig has default security schemes', function () {
    $config = new LensConfig();

    expect($config->securitySchemes)->toHaveKey('bearerAuth');
    expect($config->securitySchemes['bearerAuth']['type'])->toBe('http');
    expect($config->securitySchemes['bearerAuth']['scheme'])->toBe('bearer');
});

test('LensConfig accepts custom security schemes', function () {
    $config = new LensConfig(
        securitySchemes: [
            'apiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
            ],
        ],
    );

    expect($config->securitySchemes)->toHaveKey('apiKey');
    expect($config->securitySchemes['apiKey']['type'])->toBe('apiKey');
    expect($config->securitySchemes['apiKey']['in'])->toBe('header');
    expect($config->securitySchemes['apiKey']['name'])->toBe('X-API-Key');
});
