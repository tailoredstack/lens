<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

// Phase 1.3: Request mapping tests

test('Builder maps Request DTO properties to requestBody', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/CreateUserRequest.php', '<?php
namespace App\Http\Requests;

class CreateUserRequest {
    public string $name;
    public string $email;
    public ?string $phone = null;
}
');

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Post;
use App\Http\Requests\CreateUserRequest;

class UserController {
    #[Post("/users")]
    public function store(CreateUserRequest $request) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $requestBody = $spec['paths']['/users']['post']['requestBody'];

    expect($requestBody)->not->toBeNull();
    expect($requestBody['content']['application/json']['schema']['type'])->toBe('object');
    expect($requestBody['content']['application/json']['schema']['properties'])->toHaveKey('name');
    expect($requestBody['content']['application/json']['schema']['properties'])->toHaveKey('email');

    unlink($tmpDir . '/CreateUserRequest.php');
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder marks required Request DTO properties', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/CreateUserRequest.php', '<?php
namespace App\Http\Requests;

class CreateUserRequest {
    public string $name;
    public string $email;
    public ?string $phone = null;
}
');

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Post;
use App\Http\Requests\CreateUserRequest;

class UserController {
    #[Post("/users")]
    public function store(CreateUserRequest $request) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $schema = $spec['paths']['/users']['post']['requestBody']['content']['application/json']['schema'];

    expect($schema['required'])->toContain('name');
    expect($schema['required'])->toContain('email');
    expect($schema['required'])->not->toContain('phone');

    unlink($tmpDir . '/CreateUserRequest.php');
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder infers types from Request DTO properties', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/SearchRequest.php', '<?php
namespace App\Http\Requests;

class SearchRequest {
    public string $query;
    public int $page;
    public int $limit;
    public ?bool $active = null;
}
');

    file_put_contents($tmpDir . '/SearchController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;
use App\Http\Requests\SearchRequest;

class SearchController {
    #[Get("/search")]
    public function search(SearchRequest $request) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $schema = $spec['paths']['/search']['get']['requestBody']['content']['application/json']['schema'];

    expect($schema['properties']['query']['type'])->toBe('string');
    expect($schema['properties']['page']['type'])->toBe('integer');
    expect($schema['properties']['limit']['type'])->toBe('integer');
    expect($schema['properties']['active']['type'])->toBe('boolean');
    expect($schema['properties']['active']['nullable'])->toBeTrue();

    unlink($tmpDir . '/SearchRequest.php');
    unlink($tmpDir . '/SearchController.php');
    rmdir($tmpDir);
});
