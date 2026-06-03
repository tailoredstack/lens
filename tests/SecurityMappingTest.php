<?php

declare(strict_types=1);

namespace Tests;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;

// Phase 2.2: Security attributes tests

test('Builder maps Auth attribute to security requirement', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Auth;

class UserController {
    #[Get("/users")]
    #[Auth]
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

    // Should have security requirement
    $operation = $spec['paths']['/users']['get'];

    expect($operation)->toHaveKey('security');
    expect($operation['security'])->toBe([['bearerAuth' => []]]);

    // Should have security scheme in components
    expect($spec['components']['securitySchemes'])->toHaveKey('bearerAuth');
    expect($spec['components']['securitySchemes']['bearerAuth']['type'])->toBe('http');
    expect($spec['components']['securitySchemes']['bearerAuth']['scheme'])->toBe('bearer');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps Auth with guard parameter', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Auth;

class UserController {
    #[Get("/users")]
    #[Auth("api")]
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

    // Should have security requirement with api guard
    expect($operation)->toHaveKey('security');
    expect($operation['security'])->toBe([['apiAuth' => []]]);

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps AllowGuest to no security', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\AllowGuest;

class UserController {
    #[Get("/public")]
    #[AllowGuest]
    public function public(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $operation = $spec['paths']['/public']['get'];

    // AllowGuest should explicitly mark as public (no security)
    // This is indicated by empty security array
    expect($operation)->toHaveKey('security');
    expect($operation['security'])->toBe([]);

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps Can permission attribute', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Auth;
use Tempest\Http\Can;

class UserController {
    #[Get("/users")]
    #[Auth]
    #[Can("users.view")]
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

    // Should have security requirement
    expect($operation)->toHaveKey('security');

    // Should have permission in extensions
    expect($operation)->toHaveKey('x-permissions');
    expect($operation['x-permissions'])->toContain('users.view');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder maps multiple Can permissions', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_test_' . uniqid();
    mkdir($tmpDir, 0777, true);

    file_put_contents($tmpDir . '/UserController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Post;
use Tempest\Http\Auth;
use Tempest\Http\Can;

class UserController {
    #[Post("/users")]
    #[Auth]
    #[Can("users.create")]
    #[Can("users.manage")]
    public function store(): array {
        return [];
    }
}
');

    $engine = new Engine([$tmpDir]);
    $engine->analyze();

    $builder = new OpenApiBuilder();
    $config = new OpenApiConfig();
    $spec = $builder->buildFromInfer($engine, $config);

    $operation = $spec['paths']['/users']['post'];

    expect($operation['x-permissions'])->toContain('users.create');
    expect($operation['x-permissions'])->toContain('users.manage');

    unlink($tmpDir . '/UserController.php');
    rmdir($tmpDir);
});

test('Builder adds security schemes from config', function () {
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
        securitySchemes: [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
            'apiKey' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Key',
            ],
        ],
    );

    $builder = new OpenApiBuilder();
    $spec = $builder->buildFromInfer(new Engine([$tmpDir]), $config);

    expect($spec['components']['securitySchemes'])->toHaveKey('bearerAuth');
    expect($spec['components']['securitySchemes'])->toHaveKey('apiKey');
    expect($spec['components']['securitySchemes']['bearerAuth']['bearerFormat'])->toBe('JWT');
    expect($spec['components']['securitySchemes']['apiKey']['name'])->toBe('X-API-Key');

    unlink($tmpDir . '/StatusController.php');
    rmdir($tmpDir);
});
