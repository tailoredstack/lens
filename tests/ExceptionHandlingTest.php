<?php

declare(strict_types=1);

namespace Tests;

use Lens\Extensions\Exception\TempestHttpExceptionToResponse;
use Lens\Extensions\Exception\TempestValidationExceptionToResponse;

// Test F01-F06: Exception Handling from SPEC-TEST-MATRIX.md

test('ValidationExceptionToResponse converts to 422 (F01)', function () {
    $extension = new TempestValidationExceptionToResponse();
    $exception = new class(['email' => ['Invalid']]) extends \Exception {
        public function __construct(
            private array $errors,
        ) {
            parent::__construct('Validation failed');
        }
    };

    $response = $extension->convert($exception);

    expect($response)->toHaveKey('422');
    expect($response['422']['description'])->toBe('Validation error');
});

test('ValidationExceptionToResponse includes errors structure (F01)', function () {
    $extension = new TempestValidationExceptionToResponse();
    $exception = new class(['field' => ['error']]) extends \Exception {
        public function __construct(
            private array $errors,
        ) {
            parent::__construct('Validation failed');
        }
    };

    $response = $extension->convert($exception);
    $schema = $response['422']['content']['application/json']['schema'];

    expect($schema['properties'])->toHaveKey('errors');
    expect($schema['properties']['errors']['type'])->toBe('object');
});

test('HttpExceptionToResponse converts 404 (F05)', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(404) extends \Exception {
        public function __construct(
            private int $status,
        ) {
            parent::__construct('Not found');
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    };

    $response = $extension->convert($exception);

    expect($response)->toHaveKey('404');
    expect($response['404']['description'])->toBe('Not found');
});

test('HttpExceptionToResponse converts 401 (F03)', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(401) extends \Exception {
        public function __construct(
            private int $status,
        ) {
            parent::__construct('Unauthorized');
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    };

    $response = $extension->convert($exception);

    expect($response)->toHaveKey('401');
});

test('HttpExceptionToResponse converts 403 (F04)', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(403) extends \Exception {
        public function __construct(
            private int $status,
        ) {
            parent::__construct('Forbidden');
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    };

    $response = $extension->convert($exception);

    expect($response)->toHaveKey('403');
});

test('HttpExceptionToResponse converts 500 (F06)', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(500) extends \Exception {
        public function __construct(
            private int $status,
        ) {
            parent::__construct('Internal error');
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    };

    $response = $extension->convert($exception);

    expect($response)->toHaveKey('500');
});

test('HttpExceptionToResponse includes message in schema (F05)', function () {
    $extension = new TempestHttpExceptionToResponse();
    $exception = new class(500) extends \Exception {
        public function __construct(
            private int $status,
        ) {
            parent::__construct('Error message');
        }

        public function getStatus(): int
        {
            return $this->status;
        }
    };

    $response = $extension->convert($exception);
    $schema = $response['500']['content']['application/json']['schema'];

    expect($schema['properties'])->toHaveKey('message');
    expect($schema['properties']['message']['type'])->toBe('string');
});

test('HttpExceptionToResponse handles all common status codes (F05)', function () {
    $extension = new TempestHttpExceptionToResponse();

    foreach ([400, 401, 403, 404, 422, 429, 500, 502, 503] as $status) {
        $exception = new class($status) extends \Exception {
            public function __construct(
                private int $s,
            ) {
                parent::__construct('Error');
            }

            public function getStatus(): int
            {
                return $this->s;
            }
        };

        $response = $extension->convert($exception);
        expect($response)->toHaveKey((string) $status);
    }
});
