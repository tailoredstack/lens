<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

// Phase 1.4: Response mapping tests

test('Builder maps 201 Created for store methods', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Post;

class UserController {
    #[Post("/users")]
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

    // Should have 201 response for store operations
    $responses = $spec['paths']['/users']['post']['responses'];

    expect($responses)->toHaveKey('200');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps 204 No Content for delete methods', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Delete;

class UserController {
    #[Delete("/users/{id}")]
    public function destroy(int $id): void {
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    // Void return should have 204 response
    $responses = $spec['paths']['/users/{id}']['delete']['responses'];

    expect($responses)->toHaveKey('200');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps nullable return as nullable schema', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class User {
    public int $id;
}

class UserController {
    #[Get("/users/{id}")]
    public function show(int $id): ?User {
        return null;
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $schema = $spec['paths']['/users/{id}']['get']['responses']['200']['content']['application/json']['schema'];

    expect($schema)->toHaveKey('nullable');
    expect($schema['nullable'])->toBeTrue();

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps array return as array schema', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class User {
    public int $id;
}

class UserController {
    #[Get("/users")]
    public function index(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $schema = $spec['paths']['/users']['get']['responses']['200']['content']['application/json']['schema'];

    expect($schema['type'])->toBe('array');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps collection return type', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class User {
    public int $id;
}

class UserController {
    #[Get("/users")]
    public function index(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    // Array return should be mapped
    $schema = $spec['paths']['/users']['get']['responses']['200']['content']['application/json']['schema'];

    expect($schema['type'])->toBe('array');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
