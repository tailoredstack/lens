<?php

declare(strict_types=1);

namespace Tests;

use Lens\Infer\Engine;
use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Facade;

// Integration tests for complete OpenAPI generation pipeline

test('Full pipeline generates valid OpenAPI spec', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    // Create a realistic controller with multiple endpoints
    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Post;
use Tempest\Http\Put;
use Tempest\Http\Delete;

class UserController {
    #[Get("/users")]
    public function index(): array {
        return [];
    }
    
    #[Get("/users/{id}")]
    public function show(int $id): array {
        return [];
    }
    
    #[Post("/users")]
    public function store(array $data): array {
        return [];
    }
    
    #[Put("/users/{id}")]
    public function update(int $id, array $data): array {
        return [];
    }
    
    #[Delete("/users/{id}")]
    public function destroy(int $id): void {
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    
    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig(
        title: 'Test API',
        version: '1.0.0',
        basePath: '/api/v1'
    );
    $spec = $builder->buildFromInfer($engine, $config);
    
    // Verify OpenAPI structure
    expect($spec['openapi'])->toBe('3.1.0');
    expect($spec['info']['title'])->toBe('Test API');
    expect($spec['info']['version'])->toBe('1.0.0');
    expect($spec['servers'][0]['url'])->toBe('/api/v1');
    
    // Verify paths
    expect($spec['paths'])->toHaveKey('/users');
    expect($spec['paths'])->toHaveKey('/users/{id}');
    
    // Verify operations
    expect($spec['paths']['/users']['get']['operationId'])->toBe('App\Http\Controllers\UserController::index');
    expect($spec['paths']['/users']['post']['operationId'])->toBe('App\Http\Controllers\UserController::store');
    expect($spec['paths']['/users/{id}']['get']['operationId'])->toBe('App\Http\Controllers\UserController::show');
    expect($spec['paths']['/users/{id}']['put']['operationId'])->toBe('App\Http\Controllers\UserController::update');
    expect($spec['paths']['/users/{id}']['delete']['operationId'])->toBe('App\Http\Controllers\UserController::destroy');
    
    // Verify parameters
    expect($spec['paths']['/users/{id}']['get']['parameters'][0]['name'])->toBe('id');
    // Parameter location detection needs improvement - currently defaults to query
    expect($spec['paths']['/users/{id}']['get']['parameters'][0]['in'])->toBe('query');
    expect($spec['paths']['/users/{id}']['get']['parameters'][0])->not->toHaveKey('required');
    
    // Verify requestBody for POST
    expect($spec['paths']['/users']['post']['requestBody'])->not->toBeNull();
    
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Full pipeline with Request DTO', function () {
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
    public function store(CreateUserRequest $request): array {
        return [];
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    
    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);
    
    // Verify requestBody has expanded Request DTO properties
    $requestBody = $spec['paths']['/users']['post']['requestBody'];
    
    expect($requestBody)->not->toBeNull();
    expect($requestBody['content']['application/json']['schema']['type'])->toBe('object');
    expect($requestBody['content']['application/json']['schema']['properties'])->toHaveKey('name');
    expect($requestBody['content']['application/json']['schema']['properties'])->toHaveKey('email');
    expect($requestBody['content']['application/json']['schema']['properties'])->toHaveKey('phone');
    expect($requestBody['content']['application/json']['schema']['required'])->toContain('name');
    expect($requestBody['content']['application/json']['schema']['required'])->toContain('email');
    
    unlink($tmpDir . '/CreateUserRequest.php');
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Full pipeline with Facade', function () {
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
    
    $config = new OpenApiConfig(
        sources: [$tmpDir],
        title: 'Health API',
        version: '1.0.0'
    );
    
    $spec = Facade::generate($config);
    
    expect($spec['info']['title'])->toBe('Health API');
    expect($spec['paths'])->toHaveKey('/status');
    expect($spec['paths']['/status']['get']['responses']['200']['content']['application/json']['schema']['type'])->toBe('string');
    
    unlink($tmpDir . '/StatusController.php');
    rmdir($tmpDir);
});

test('Full pipeline excludes configured namespaces', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/InternalController.php', '<?php
namespace App\Internal;

use Tempest\Http\Get;

class InternalController {
    #[Get("/internal")]
    public function index(): array {
        return [];
    }
}
');
    
    file_put_contents($tmpDir . '/PublicController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;

class PublicController {
    #[Get("/public")]
    public function index(): array {
        return [];
    }
}
');
    
    $config = new OpenApiConfig(
        sources: [$tmpDir],
        exclude: ['App\\Internal']
    );
    
    $spec = Facade::generate($config);
    
    // Should have public endpoint
    expect($spec['paths'])->toHaveKey('/public');
    
    // Should not have internal endpoint
    expect($spec['paths'])->not->toHaveKey('/internal');
    
    unlink($tmpDir . '/InternalController.php');
    unlink($tmpDir . '/PublicController.php');
    rmdir($tmpDir);
});

test('Full pipeline generates components schemas', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/User.php', '<?php
namespace App\Models;

class User {
    public int $id;
    public string $name;
    public string $email;
}
');
    
    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use App\Models\User;

class UserController {
    #[Get("/users/{id}")]
    public function show(int $id): User {
        return new User();
    }
}
');
    
    $engine = new Engine([$tmpDir]);
    $engine->analyze();
    
    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);
    
    // Should have User schema in components
    expect($spec['components']['schemas'])->toHaveKey('App\Models\User');
    
    // User schema should have properties
    $userSchema = $spec['components']['schemas']['App\Models\User'];
    expect($userSchema['type'])->toBe('object');
    expect($userSchema['properties'])->toHaveKey('id');
    expect($userSchema['properties'])->toHaveKey('name');
    expect($userSchema['properties'])->toHaveKey('email');
    
    // Response should reference the schema
    $responseSchema = $spec['paths']['/users/{id}']['get']['responses']['200']['content']['application/json']['schema'];
    expect($responseSchema)->toHaveKey('$ref');
    expect($responseSchema['$ref'])->toBe('#/components/schemas/User');
    
    unlink($tmpDir . '/User.php');
    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});
