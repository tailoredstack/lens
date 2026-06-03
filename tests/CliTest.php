<?php

declare(strict_types=1);

namespace Tests;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Lens\Cli\GenerateCommand;

// Phase 3.1: CLI tests

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/lens_cli_test_' . uniqid();
    mkdir($this->tmpDir, 0777, true);
    
    file_put_contents($this->tmpDir . '/TestController.php', '<?php
namespace App\Http\Controllers;

use Tempest\Http\Get;
use Tempest\Http\Post;

class TestController {
    #[Get("/test")]
    public function index(): array {
        return [];
    }
    
    #[Post("/test")]
    public function store(array $data): array {
        return [];
    }
}
');
    
    $this->outputFile = sys_get_temp_dir() . '/test-openapi-' . uniqid() . '.json';
});

afterEach(function () {
    unlink($this->tmpDir . '/TestController.php');
    rmdir($this->tmpDir);
    
    if (file_exists($this->outputFile)) {
        unlink($this->outputFile);
    }
});

test('CLI generates OpenAPI spec', function () {
    $application = new Application();
    $command = new GenerateCommand();
    $application->addCommand($command);
    $application->setDefaultCommand('generate', true);
    
    $commandTester = new CommandTester($command);
    
    $commandTester->execute([
        '--source' => [$this->tmpDir],
        '--output' => $this->outputFile,
        '--title' => 'Test CLI API',
        '--api-version' => '2.0.0',
    ]);
    
    $commandTester->assertCommandIsSuccessful();
    
    $output = $commandTester->getDisplay();
    expect($output)->toContain('OpenAPI specification generated successfully');
    expect($output)->toContain($this->outputFile);
    
    // Verify output file exists and is valid JSON
    expect(file_exists($this->outputFile))->toBeTrue();
    
    $spec = json_decode(file_get_contents($this->outputFile), true);
    expect($spec['info']['title'])->toBe('Test CLI API');
    expect($spec['info']['version'])->toBe('2.0.0');
    expect($spec['paths'])->toHaveKey('/test');
});

test('CLI excludes namespaces', function () {
    $application = new Application();
    $command = new GenerateCommand();
    $application->addCommand($command);
    $application->setDefaultCommand('generate', true);
    
    $commandTester = new CommandTester($command);
    
    $commandTester->execute([
        '--source' => [$this->tmpDir],
        '--output' => $this->outputFile,
        '--exclude' => ['App\\Http'],
    ]);
    
    $commandTester->assertCommandIsSuccessful();
    
    $spec = json_decode(file_get_contents($this->outputFile), true);
    
    // Should have no paths since we excluded the namespace
    expect($spec['paths'])->toBeEmpty();
});

test('CLI outputs YAML format', function () {
    $application = new Application();
    $command = new GenerateCommand();
    $application->addCommand($command);
    $application->setDefaultCommand('generate', true);
    
    $commandTester = new CommandTester($command);
    
    $yamlOutput = sys_get_temp_dir() . '/test-openapi-' . uniqid() . '.yaml';
    
    $commandTester->execute([
        '--source' => [$this->tmpDir],
        '--output' => $yamlOutput,
        '--format' => 'yaml',
        '--title' => 'Test CLI API',
    ]);
    
    $commandTester->assertCommandIsSuccessful();
    
    expect(file_exists($yamlOutput))->toBeTrue();
    
    $content = file_get_contents($yamlOutput);
    expect($content)->toContain('openapi: 3.1.0');
    expect($content)->toContain('Test CLI API');
    
    unlink($yamlOutput);
});

test('CLI fails with invalid source', function () {
    $application = new Application();
    $command = new GenerateCommand();
    $application->addCommand($command);
    $application->setDefaultCommand('generate', true);
    
    $commandTester = new CommandTester($command);
    
    $commandTester->execute([
        '--source' => ['/nonexistent/path'],
        '--output' => $this->outputFile,
    ]);
    
    expect($commandTester->getStatusCode())->toBe(1);
    
    $output = $commandTester->getDisplay();
    expect($output)->toContain('Source directory not found');
});

test('CLI includes base path in servers', function () {
    $application = new Application();
    $command = new GenerateCommand();
    $application->addCommand($command);
    $application->setDefaultCommand('generate', true);
    
    $commandTester = new CommandTester($command);
    
    $commandTester->execute([
        '--source' => [$this->tmpDir],
        '--output' => $this->outputFile,
        '--base-path' => '/api/v1',
    ]);
    
    $commandTester->assertCommandIsSuccessful();
    
    $spec = json_decode(file_get_contents($this->outputFile), true);
    expect($spec['servers'][0]['url'])->toBe('/api/v1');
});
