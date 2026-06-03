<?php

declare(strict_types=1);

namespace Tests;

use Lens\Infer\Engine;

// Test H01-H04: Performance & Caching from SPEC-TEST-MATRIX.md

test('Engine analyzes single file quickly (H01)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/TestController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class TestController {
    #[Get("/test")]
    public function index(): array {
        return [];
    }
}
');

    $startTime = microtime(true);

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $elapsed = microtime(true) - $startTime;

    // Should complete in under 1 second for a single file
    expect($elapsed)->toBeLessThan(1.0);

    unlink($tmpDir . '/TestController.php');
    rmdir($tmpDir);
});

test('Engine handles multiple files (H02)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    // Create 5 controller files
    for ($i = 0; $i < 5; $i++) {
        file_put_contents($tmpDir . "/Controller{$i}.php", "<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class Controller{$i} {
    #[Get('/route{$i}')]
    public function index(): array {
        return [];
    }
}
");
    }

    $startTime = microtime(true);

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    $elapsed = microtime(true) - $startTime;

    // Should discover all 5 controllers
    expect($operations)->toHaveCount(5);
    // Should complete in reasonable time
    expect($elapsed)->toBeLessThan(2.0);

    for ($i = 0; $i < 5; $i++) {
        unlink($tmpDir . "/Controller{$i}.php");
    }
    rmdir($tmpDir);
});

test('Engine skips non-PHP files (H03)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/TestController.php', '<?php
namespace App\Http\Controllers;
class TestController {
    public function index() {}
}
');
    file_put_contents($tmpDir . '/readme.txt', 'This is not PHP');
    file_put_contents($tmpDir . '/config.json', '{"key": "value"}');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Should only find the PHP controller
    expect($operations)->toHaveCount(1);
    expect($operations)->toHaveKey('App\Http\Controllers\TestController');

    unlink($tmpDir . '/TestController.php');
    unlink($tmpDir . '/readme.txt');
    unlink($tmpDir . '/config.json');
    rmdir($tmpDir);
});

test('Engine handles empty directory gracefully (H03)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Should return empty array, not throw
    expect($operations)->toBeArray();
    expect($operations)->toBeEmpty();

    rmdir($tmpDir);
});

test('Engine handles non-existent directory gracefully (H03)', function () {
    $engine = new Engine(['/nonexistent/path']);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Should return empty array, not throw
    expect($operations)->toBeArray();
});

test('Engine caches operations in memory (H04)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/TestController.php', '<?php
namespace App\Http\Controllers;
class TestController {
    public function index() {}
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    // First call
    $operations1 = $engine->getOperations();

    // Second call should return same data (cached)
    $operations2 = $engine->getOperations();

    expect($operations1)->toBe($operations2);

    unlink($tmpDir . '/TestController.php');
    rmdir($tmpDir);
});

test('Engine processes nested directories (H02)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir . '/Api/V1', 0777, true);

    file_put_contents($tmpDir . '/Api/V1/UserController.php', '<?php
namespace App\Http\Controllers\Api\V1;
use Tempest\Http\Get;

class UserController {
    #[Get("/users")]
    public function index(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Should find nested controller
    expect($operations)->toHaveKey('App\Http\Controllers\Api\V1\UserController');

    unlink($tmpDir . '/Api/V1/UserController.php');
    rmdir($tmpDir . '/Api/V1');
    rmdir($tmpDir . '/Api');
    rmdir($tmpDir);
});

test('Engine handles class with multiple methods (H02)', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;
use Tempest\Http\Post;
use Tempest\Http\Put;
use Tempest\Http\Delete;

class UserController {
    #[Get("/users")]
    public function index(): array { return []; }
    
    #[Get("/users/{id}")]
    public function show(int $id): array { return []; }
    
    #[Post("/users")]
    public function store(array $data): array { return []; }
    
    #[Put("/users/{id}")]
    public function update(int $id, array $data): array { return []; }
    
    #[Delete("/users/{id}")]
    public function destroy(int $id): void {}
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    // Should find all 5 methods
    expect($operations['App\Http\Controllers\UserController'])->toHaveCount(5);
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('index');
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('show');
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('store');
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('update');
    expect($operations['App\Http\Controllers\UserController'])->toHaveKey('destroy');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
