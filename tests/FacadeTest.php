<?php

declare(strict_types=1);

use Lens\Config\OpenApiConfig;
use Lens\Facade;
use Lens\Infer\Engine;

test('Facade generates OpenAPI spec', function () {
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        title: 'Test API',
        version: '1.0.0',
        basePath: '/api/v1',
        exclude: [],
    );

    $spec = Facade::generate($config);

    expect($spec)->toBeArray();
    expect($spec['openapi'])->toBe('3.1.0');
    expect($spec['info']['title'])->toBe('Test API');
    expect($spec['info']['version'])->toBe('1.0.0');
    expect($spec['servers'][0]['url'])->toBe('/api/v1');
    expect($spec['components']['schemas'])->toBeArray();
});

test('Facade exports to file', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'openapi_test_');

    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        exportPath: $tmpFile,
        exclude: [],
    );

    Facade::configure($config);
    $result = Facade::export($tmpFile);

    expect($result)->toBe($tmpFile);
    expect(file_exists($tmpFile))->toBeTrue();

    $content = file_get_contents($tmpFile);
    expect($content)->toContain('"openapi": "3.1.0"');

    unlink($tmpFile);
});

test('Engine infers types from source files', function () {
    $engine = new Engine([__DIR__ . '/../src/Types']);
    $engine->analyze();

    $types = $engine->getTypes();

    expect($types)->toBeArray();
    expect($types)->not->toBeEmpty();

    // Should have inferred ScalarType, ObjectType, etc.
    $classNames = array_keys($types);
    expect($classNames)->toContain('Lens\Types\ScalarType');
    expect($classNames)->toContain('Lens\Types\ObjectType');
});

test('Builder respects exclude patterns', function () {
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        exclude: ['Lens\Types\Scalar'], // Exclude ScalarType
    );

    $spec = Facade::generate($config);

    $schemas = $spec['components']['schemas'];
    expect($schemas)->not->toHaveKey('Lens\Types\ScalarType');
});

test('Builder includes servers from config', function () {
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        apiDomain: 'https://api.example.com',
        basePath: '/v1',
    );

    $spec = Facade::generate($config);

    expect($spec['servers'][0]['url'])->toBe('https://api.example.com/v1');
});

test('Builder uses custom servers when provided', function () {
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        servers: [
            'Production' => 'https://api.prod.com/v1',
            'Staging' => 'https://api.staging.com/v1',
        ],
    );

    $spec = Facade::generate($config);

    expect($spec['servers'])->toHaveCount(2);
    expect($spec['servers'])->toHaveKey('Production');
    expect($spec['servers'])->toHaveKey('Staging');
});
