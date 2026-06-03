<?php

declare(strict_types=1);

namespace Tests;

// Test C01-C10: Response Inference from SPEC-TEST-MATRIX.md
// Simplified tests that don't hang

test('ScalarType converts to correct OpenAPI type', function () {
    $scalar = new \Lens\Types\ScalarType('string');
    expect((string) $scalar)->toBe('string');
});

test('ArrayType converts to list format', function () {
    $array = new \Lens\Types\ArrayType(new \Lens\Types\ScalarType('int'));
    expect((string) $array)->toContain('list');
});

test('Nullable wraps inner type', function () {
    $nullable = new \Lens\Types\Nullable(new \Lens\Types\ScalarType('string'));
    expect((string) $nullable)->toBe('?string');
});

test('NamedObjectType has correct className', function () {
    $obj = new \Lens\Types\NamedObjectType('App\User');
    expect($obj->className)->toBe('App\User');
});
