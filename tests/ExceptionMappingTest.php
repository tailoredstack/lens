<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Extensions\Exception\DefaultExceptionToResponse;
use Lens\Extensions\Exception\ExceptionToResponse;
use Lens\Infer\Engine;

// Phase 2.1: Exception to Response mapping tests

test('DefaultExceptionToResponse maps NotFoundException to 404', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('Tempest\\Http\\Exceptions\\NotFoundException');

    expect($result)->toHaveKey('404');
    expect($result['404']['description'])->toContain('Not Found');
});

test('DefaultExceptionToResponse maps ValidationException to 422', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('Tempest\\Http\\Exceptions\\ValidationException');

    expect($result)->toHaveKey('422');
    expect($result['422']['description'])->toContain('Unprocessable Entity');
});

test('DefaultExceptionToResponse maps UnauthorizedException to 401', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('Tempest\\Http\\Exceptions\\UnauthorizedException');

    expect($result)->toHaveKey('401');
    expect($result['401']['description'])->toContain('Unauthorized');
});

test('DefaultExceptionToResponse maps ForbiddenException to 403', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('Tempest\\Http\\Exceptions\\ForbiddenException');

    expect($result)->toHaveKey('403');
    expect($result['403']['description'])->toContain('Forbidden');
});

test('DefaultExceptionToResponse maps BadRequestException to 400', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('Tempest\\Http\\Exceptions\\BadRequestException');

    expect($result)->toHaveKey('400');
    expect($result['400']['description'])->toContain('Bad Request');
});

test('DefaultExceptionToResponse returns empty for unknown exception', function () {
    $extension = new DefaultExceptionToResponse();

    $result = $extension->convert('UnknownException');

    expect($result)->toBe([]);
});

test('ExceptionToResponse interface is implemented', function () {
    $extension = new DefaultExceptionToResponse();

    expect($extension)->toBeInstanceOf(ExceptionToResponse::class);
});

test('Builder includes exception responses from throws attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Throws;
use Tempest\Http\Exceptions\NotFoundException;

class UserController {
    #[Get("/users/{id}")]
    #[Throws(NotFoundException::class)]
    public function show(int $id): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    // Should have 404 response from Throws attribute
    $responses = $spec['paths']['/users/{id}']['get']['responses'];

    expect($responses)->toHaveKey('404');
    expect($responses['404']['description'])->toContain('Not Found');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder includes multiple exception responses', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Post;
use Tempest\Http\Throws;
use Tempest\Http\Exceptions\NotFoundException;
use Tempest\Http\Exceptions\ValidationException;

class UserController {
    #[Post("/users")]
    #[Throws(NotFoundException::class)]
    #[Throws(ValidationException::class)]
    public function store(array $data): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $responses = $spec['paths']['/users']['post']['responses'];

    expect($responses)->toHaveKey('404');
    expect($responses)->toHaveKey('422');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
