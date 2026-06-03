<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

// Test B01-B04, E02: Validation Rules → OpenAPI Constraints

test('Engine infers string parameter type', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/SearchController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class SearchController {
    #[Get("/search")]
    public function search(string $query) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    $params = $operations['App\Http\Controllers\SearchController']['search']['params'];

    expect($params[0]['type'])->toBeObject();
    expect((string) $params[0]['type'])->toBe('string');

    unlink($tmpDir . '/SearchController.php');
    rmdir($tmpDir);
});

test('Engine infers int parameter type', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/PaginationController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class PaginationController {
    #[Get("/items")]
    public function index(int $page) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    $operations = $engine->getOperations();

    $params = $operations['App\Http\Controllers\PaginationController']['index']['params'];

    expect((string) $params[0]['type'])->toBe('int');

    unlink($tmpDir . '/PaginationController.php');
    rmdir($tmpDir);
});

test('Builder converts string type to OpenAPI string schema', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/SearchController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class SearchController {
    #[Get("/search")]
    public function search(string $query) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $params = $spec['paths']['/search']['get']['parameters'];

    expect($params[0]['schema']['type'])->toBe('string');

    unlink($tmpDir . '/SearchController.php');
    rmdir($tmpDir);
});

test('Builder converts int type to OpenAPI integer schema', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/PaginationController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class PaginationController {
    #[Get("/items")]
    public function index(int $page, int $limit = 10) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $params = $spec['paths']['/items']['get']['parameters'];

    expect($params[0]['schema']['type'])->toBe('integer');
    expect($params[1]['schema']['type'])->toBe('integer');

    unlink($tmpDir . '/PaginationController.php');
    rmdir($tmpDir);
});

test('Builder converts bool type to OpenAPI boolean schema', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/FilterController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class FilterController {
    #[Get("/items")]
    public function index(bool $active) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $params = $spec['paths']['/items']['get']['parameters'];

    expect($params[0]['schema']['type'])->toBe('boolean');

    unlink($tmpDir . '/FilterController.php');
    rmdir($tmpDir);
});

test('Builder adds schema for array body parameter', function () {
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

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $requestBody = $spec['paths']['/users']['post']['requestBody'];

    expect($requestBody['content']['application/json']['schema']['type'])->toBe('object');
    expect($requestBody['content']['application/json']['schema']['properties']['data']['type'])->toBe('array');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder generates path with controller prefix', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/ProductController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class ProductController {
    #[Get("/products")]
    public function index() {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    expect($spec['paths'])->toHaveKey('/products');

    unlink($tmpDir . '/ProductController.php');
    rmdir($tmpDir);
});

test('Builder generates path with {id} for show method', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/ProductController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;

class ProductController {
    #[Get("/products/{id}")]
    public function show(int $id) {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    expect($spec['paths'])->toHaveKey('/products/{id}');

    unlink($tmpDir . '/ProductController.php');
    rmdir($tmpDir);
});
