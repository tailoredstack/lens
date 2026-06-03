<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

// Phase 2.3: Docblock parsing tests (tags, descriptions, summaries)

test('Builder extracts summary from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * List all active users
     */
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

    $operation = $spec['paths']['/users']['get'];

    expect($operation['summary'])->toBe('List all active users');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts description from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * List all active users
     * 
     * Returns a paginated list of users filtered by status.
     * Only returns users with active status by default.
     */
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

    $operation = $spec['paths']['/users']['get'];

    expect($operation['summary'])->toBe('List all active users');
    expect($operation['description'])->toContain('Returns a paginated list');
    expect($operation['description'])->toContain('Only returns users with active status');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts tags from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * List all users
     * 
     * @tag Users
     * @tag Admin
     */
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

    $operation = $spec['paths']['/users']['get'];

    expect($operation['tags'])->toContain('Users');
    expect($operation['tags'])->toContain('Admin');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts parameter descriptions from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * Get a specific user
     * 
     * @param int $id The user ID to retrieve
     */
    #[Get("/users/{id}")]
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

    $param = $spec['paths']['/users/{id}']['get']['parameters'][0];

    expect($param['name'])->toBe('id');
    expect($param['description'])->toBe('The user ID to retrieve');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts response description from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * Get user details
     * 
     * @return array User data with profile information
     */
    #[Get("/users/{id}")]
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

    $response = $spec['paths']['/users/{id}']['get']['responses']['200'];

    expect($response['description'])->toContain('User data with profile information');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts deprecated flag from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * Get old user endpoint
     * 
     * @deprecated Use /api/v2/users instead
     */
    #[Get("/users/old")]
    public function old(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $operation = $spec['paths']['/users/old']['get'];

    expect($operation['deprecated'])->toBeTrue();

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts example from docblock', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Post;

class UserController {
    /**
     * Create a new user
     * 
     * @example {"name": "John", "email": "john@example.com"}
     */
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

    $requestBody = $spec['paths']['/users']['post']['requestBody'];

    expect($requestBody['content']['application/json']['example'])->toBe('{"name": "John", "email": "john@example.com"}');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder extracts multiple param descriptions', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class UserController {
    /**
     * Search users
     * 
     * @param string $query Search query string
     * @param int $limit Maximum results to return
     * @param int $offset Pagination offset
     */
    #[Get("/users/search")]
    public function search(string $query, int $limit, int $offset): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $params = $spec['paths']['/users/search']['get']['parameters'];

    expect($params[0]['description'])->toBe('Search query string');
    expect($params[1]['description'])->toBe('Maximum results to return');
    expect($params[2]['description'])->toBe('Pagination offset');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
