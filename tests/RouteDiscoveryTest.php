<?php

declare(strict_types=1);

namespace Tests;

use Lens\Infer\Engine;

// Test A01-A05: Route Discovery from SPEC-TEST-MATRIX.md

test('Engine discovers controller methods with Get attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class UserController {
    #[Get("/users")]
    public function index() {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    expect($operations)->toHaveKey('App\Http\Controllers\UserController');
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('index');
    expect($operations['App\Http\Controllers\UserController']['index']['http'])->toBe('get');
    expect($operations['App\Http\Controllers\UserController']['index']['path'])->toBe('/users');

    // Cleanup
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine discovers controller methods with Post attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Post;

class UserController {
    #[Post("/users")]
    public function store(array $data) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    expect($operations['App\Http\Controllers\UserController']['store']['http'])->toBe('post');
    expect($operations['App\Http\Controllers\UserController']['store']['path'])->toBe('/users');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine discovers controller methods with Put attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Put;

class UserController {
    #[Put("/users/{id}")]
    public function update(int $id, array $data) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    expect($operations['App\Http\Controllers\UserController']['update']['http'])->toBe('put');
    expect($operations['App\Http\Controllers\UserController']['update']['path'])->toBe('/users/{id}');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine discovers controller methods with Delete attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Delete;

class UserController {
    #[Delete("/users/{id}")]
    public function destroy(int $id) {
        return response()->noContent();
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    expect($operations['App\Http\Controllers\UserController']['destroy']['http'])->toBe('delete');
    expect($operations['App\Http\Controllers\UserController']['destroy']['path'])->toBe('/users/{id}');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine extracts method parameters with types', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/BookController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class BookController {
    #[Get("/books/{id}")]
    public function show(int $id, string $include = "") {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    $params = $operations['App\Http\Controllers\BookController']['show']['params'];

    expect($params)->toHaveCount(2);
    expect($params[0]['name'])->toBe('id');
    expect($params[1]['name'])->toBe('include');

    unlink($tmpDir . '/BookController.php');
    rmdir($tmpDir);
});

test('Engine discovers all public methods as operations', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    // Both controller and service should be discovered by Engine
    file_put_contents($tmpDir . '/ProductController.php', '<?php
namespace App\Http\Controllers;
class ProductController {
    public function index() {}
}
');

    file_put_contents($tmpDir . '/UserService.php', '<?php
namespace App\Services;
class UserService {
    public function find() {}
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Engine discovers all classes with public methods
    expect($operations)->toHaveKey('App\Http\Controllers\ProductController');
    expect($operations)->toHaveKey('App\Services\UserService');

    unlink($tmpDir . '/ProductController.php');
    unlink($tmpDir . '/UserService.php');
    rmdir($tmpDir);
});
