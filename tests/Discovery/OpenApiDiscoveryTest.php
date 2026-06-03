<?php

declare(strict_types=1);

namespace Tests\Discovery;

use Lens\Discovery\OpenApiDiscovery;
use Lens\Config\OpenApiConfig;
use Lens\Facade;

test('OpenApiDiscovery generates spec', function () {
    $discovery = new OpenApiDiscovery();
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        title: 'Test',
        version: '1.0.0',
    );

    $spec = $discovery->generateOpenApi($config);

    expect($spec)->toBeArray();
    expect($spec['openapi'])->toBe('3.1.0');
    expect($spec['info']['title'])->toBe('Test');
});

test('Discovery uses Facade internally', function () {
    $discovery = new OpenApiDiscovery();
    $config = new OpenApiConfig(
        sources: [__DIR__ . '/../src/Types'],
        exclude: [],
    );

    // Should not throw
    $spec = $discovery->generateOpenApi($config);

    expect($spec['components']['schemas'])->toBeArray();
});
