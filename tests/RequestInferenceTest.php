<?php

declare(strict_types=1);

namespace Tests;

use Lens\Infer\Engine;

// Test B01-B05: Request Inference from SPEC-TEST-MATRIX.md

test('Engine infers request body from method parameters', function () {
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
    
    $params = $operations['App\Http\Controllers\UserController']['store']['params'];
    
    expect($params)->toHaveCount(1);
    expect($params[0]['name'])->toBe('data');
    
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine infers typed request parameters', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/SearchController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class SearchController {
    #[Get("/search")]
    public function search(string $query, int $page = 1, int $limit = 10) {
        return [];
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();
    
    $params = $operations['App\Http\Controllers\SearchController']['search']['params'];
    
    expect($params)->toHaveCount(3);
    expect($params[0]['name'])->toBe('query');
    expect($params[1]['name'])->toBe('page');
    expect($params[2]['name'])->toBe('limit');
    
    unlink($tmpDir . '/SearchController.php');
    rmdir($tmpDir);
});

test('Engine infers nullable parameters', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/FilterController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class FilterController {
    #[Get("/items")]
    public function index(?string $category = null, ?int $min = null) {
        return [];
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();
    
    $params = $operations['App\Http\Controllers\FilterController']['index']['params'];
    
    expect($params)->toHaveCount(2);
    
    unlink($tmpDir . '/FilterController.php');
    rmdir($tmpDir);
});

test('Engine infers return type from method', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/StatusController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class StatusController {
    #[Get("/status")]
    public function health(): string {
        return "ok";
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();
    
    $returnType = $operations['App\Http\Controllers\StatusController']['health']['return'];
    
    expect($returnType)->toBeObject();
    expect((string) $returnType)->toBe('string');
    
    unlink($tmpDir . '/StatusController.php');
    rmdir($tmpDir);
});

test('Engine infers array return type', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
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
    
    $returnType = $operations['App\Http\Controllers\UserController']['index']['return'];
    
    expect($returnType)->toBeObject();
    expect($returnType)->toBeInstanceOf(\Lens\Types\ArrayType::class);
    
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Engine infers object return type', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class User {
    public int $id;
    public string $name;
}

class UserController {
    #[Get("/users/{id}")]
    public function show(int $id): User {
        return new User();
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();
    
    $returnType = $operations['App\Http\Controllers\UserController']['show']['return'];
    
    expect($returnType)->toBeObject();
    expect((string) $returnType)->toContain('User');
    
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
