<?php

declare(strict_types=1);

namespace Tests;

use Lens\Config\OpenApiConfig;

test('OpenApiConfig title default', function () {
    $config = new OpenApiConfig();
    expect($config->title)->toBe('Lens OpenAPI');
});

test('OpenApiConfig version default', function () {
    $config = new OpenApiConfig();
    expect($config->version)->toBe('0.0.0');
});

test('OpenApiConfig basePath default', function () {
    $config = new OpenApiConfig();
    expect($config->basePath)->toBe('/');
});

test('OpenApiConfig custom values', function () {
    $config = new OpenApiConfig(
        title: 'Test',
        version: '1.0.0',
        basePath: '/api'
    );
    expect($config->title)->toBe('Test');
    expect($config->version)->toBe('1.0.0');
    expect($config->basePath)->toBe('/api');
});

test('OpenApiConfig exclude array', function () {
    $config = new OpenApiConfig(exclude: ['App\\Test']);
    expect($config->exclude)->toBe(['App\\Test']);
});

test('OpenApiConfig servers array', function () {
    $config = new OpenApiConfig(servers: ['Prod' => 'https://api.prod.com']);
    expect($config->servers['Prod'])->toBe('https://api.prod.com');
});
