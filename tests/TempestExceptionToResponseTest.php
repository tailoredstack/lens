<?php

declare(strict_types=1);

namespace Tests;

use Lens\Extensions\Exception\TempestValidationExceptionToResponse;
use Lens\Extensions\Exception\TempestHttpExceptionToResponse;

test('TempestValidationExceptionToResponse converts validation errors', function () {
    $extension = new TempestValidationExceptionToResponse();
    
    // Mock exception with errors
    $exception = new class(['email' => ['Invalid email']]) extends \Exception {
        public function __construct(private array $errors) {
            parent::__construct('Validation failed');
        }
        public function getErrors(): array {
            return $this->errors;
        }
    };
    
    $response = $extension->convert($exception);
    
    expect($response)->toHaveKey('422');
    expect($response['422']['description'])->toBe('Validation error');
});

test('TempestValidationExceptionToResponse includes error structure', function () {
    $extension = new TempestValidationExceptionToResponse();
    $exception = new class(['field' => ['error']]) extends \Exception {
        public function __construct(private array $errors) {
            parent::__construct('Validation failed');
        }
    };
    
    $response = $extension->convert($exception);
    
    $schema = $response['422']['content']['application/json']['schema'];
    expect($schema['properties']['message']['type'])->toBe('string');
    expect($schema['properties']['errors']['type'])->toBe('object');
});

test('TempestHttpExceptionToResponse converts HTTP exception', function () {
    $extension = new TempestHttpExceptionToResponse();
    
    // Mock HttpException
    $exception = new class(404) extends \Exception {
        public function __construct(private int $status) {
            parent::__construct('Not found');
        }
        public function getStatus(): int {
            return $this->status;
        }
    };
    
    $response = $extension->convert($exception);
    
    expect($response)->toHaveKey('404');
    expect($response['404']['description'])->toBe('Not found');
});

test('TempestHttpExceptionToResponse handles various status codes', function () {
    $extension = new TempestHttpExceptionToResponse();
    
    $statuses = [400, 401, 403, 404, 422, 500, 502, 503];
    
    foreach ($statuses as $status) {
        $exception = new class($status) extends \Exception {
            public function __construct(private int $status) {
                parent::__construct('Error');
            }
            public function getStatus(): int {
                return $this->status;
            }
        };
        
        $response = $extension->convert($exception);
        expect($response)->toHaveKey((string) $status);
    }
});

test('TempestHttpExceptionToResponse includes message and code in schema', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(500) extends \Exception {
        public function __construct(private int $status) {
            parent::__construct('Internal Server Error');
        }
        public function getStatus(): int {
            return $this->status;
        }
    };
    
    $response = $extension->convert($exception);
    
    $schema = $response['500']['content']['application/json']['schema'];
    expect($schema['properties']['message']['type'])->toBe('string');
    expect($schema['properties']['code']['type'])->toBe('integer');
});
