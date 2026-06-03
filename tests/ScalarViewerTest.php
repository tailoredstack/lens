<?php

declare(strict_types=1);

namespace Tests;

use Lens\Integration\ScalarViewer;

// Phase 3.2: Scalar viewer tests

test('ScalarViewer generates valid HTML', function () {
    $viewer = new ScalarViewer();
    $html = $viewer->getHtml();
    
    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('@scalar/api-reference');
    expect($html)->toContain('data-url="/openapi.json"');
});

test('ScalarViewer uses custom route path', function () {
    $viewer = new ScalarViewer(routePath: '/api/docs');
    
    expect($viewer->getRoutePath())->toBe('/api/docs');
});

test('ScalarViewer uses custom spec path', function () {
    $viewer = new ScalarViewer(specPath: '/api/spec.json');
    
    expect($viewer->getSpecPath())->toBe('/api/spec.json');
});

test('ScalarViewer uses custom title', function () {
    $viewer = new ScalarViewer(title: 'My API Docs');
    $html = $viewer->getHtml();
    
    expect($html)->toContain('<title>My API Docs</title>');
});

test('ScalarViewer uses external spec URL', function () {
    $viewer = new ScalarViewer(specUrl: 'https://example.com/openapi.json');
    $html = $viewer->getHtml();
    
    expect($html)->toContain('data-url="https://example.com/openapi.json"');
});

test('ScalarViewer generates spec from sources', function () {
    $tmpDir = sys_get_temp_dir() . '/lens_scalar_test_' . uniqid();
    mkdir($tmpDir, 0777, true);
    
    file_put_contents($tmpDir . '/TestController.php', '<?php
namespace App\Http\Controllers;
use Tempest\Http\Get;
class TestController {
    #[Get("/test")]
    public function index(): array { return []; }
}
');
    
    $viewer = new ScalarViewer();
    $config = new \Lens\Config\OpenApiConfig(sources: [$tmpDir]);
    
    $spec = $viewer->generateSpec([$tmpDir], $config);
    
    expect($spec['openapi'])->toBe('3.1.0');
    expect($spec['paths'])->toHaveKey('/test');
    
    unlink($tmpDir . '/TestController.php');
    rmdir($tmpDir);
});
