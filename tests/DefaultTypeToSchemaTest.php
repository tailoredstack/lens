<?php

declare(strict_types=1);

namespace Tests;

use Lens\Extensions\TypeToSchema\DefaultTypeToSchema;
use Lens\Types\ArrayType;
use Lens\Types\EnumType;
use Lens\Types\LiteralInteger;
use Lens\Types\LiteralString;
use Lens\Types\NamedObjectType;
use Lens\Types\Nullable;
use Lens\Types\ScalarType;
use Lens\Types\UnionType;

test('DefaultTypeToSchema supports all types', function () {
    $converter = new DefaultTypeToSchema();

    expect($converter->supports(new ScalarType('string')))->toBeTrue();
    expect($converter->supports(new ArrayType(new ScalarType('int'))))->toBeTrue();
    expect($converter->supports(new NamedObjectType('App\\User')))->toBeTrue();
});

test('DefaultTypeToSchema converts scalar types', function () {
    $converter = new DefaultTypeToSchema();

    expect($converter->convert(new ScalarType('string')))->toBe(['type' => 'string']);
    expect($converter->convert(new ScalarType('int')))->toBe(['type' => 'integer']);
    expect($converter->convert(new ScalarType('float')))->toBe(['type' => 'number']);
    expect($converter->convert(new ScalarType('bool')))->toBe(['type' => 'boolean']);
});

test('DefaultTypeToSchema converts nullable types', function () {
    $converter = new DefaultTypeToSchema();
    $nullable = new Nullable(new ScalarType('string'));

    $result = $converter->convert($nullable);

    expect($result)->toBe(['type' => 'string', 'nullable' => true]);
});

test('DefaultTypeToSchema converts array types', function () {
    $converter = new DefaultTypeToSchema();
    $array = new ArrayType(new ScalarType('string'));

    $result = $converter->convert($array);

    expect($result)->toBe([
        'type' => 'array',
        'items' => ['type' => 'string'],
    ]);
});

test('DefaultTypeToSchema converts enum types', function () {
    $converter = new DefaultTypeToSchema();
    $enum = new EnumType('App\\Status', 'active', 'inactive');

    $result = $converter->convert($enum);

    expect($result)->toBe([
        'type' => 'string',
        'enum' => ['active', 'inactive'],
    ]);
});

test('DefaultTypeToSchema converts integer enum types', function () {
    $converter = new DefaultTypeToSchema();
    $enum = new EnumType('App\\Priority', 1, 2, 3);

    $result = $converter->convert($enum);

    expect($result)->toBe([
        'type' => 'integer',
        'enum' => [1, 2, 3],
    ]);
});

test('DefaultTypeToSchema converts literal string types', function () {
    $converter = new DefaultTypeToSchema();
    $literal = new LiteralString('fixed-value');

    $result = $converter->convert($literal);

    expect($result)->toBe([
        'type' => 'string',
        'enum' => ['fixed-value'],
    ]);
});

test('DefaultTypeToSchema converts literal integer types', function () {
    $converter = new DefaultTypeToSchema();
    $literal = new LiteralInteger(42);

    $result = $converter->convert($literal);

    expect($result)->toBe([
        'type' => 'integer',
        'enum' => [42],
    ]);
});

test('DefaultTypeToSchema converts named object types with $ref', function () {
    $converter = new DefaultTypeToSchema();
    $object = new NamedObjectType('App\\User');

    $result = $converter->convert($object);

    expect($result)->toBe([
        '$ref' => '#/components/schemas/App\\User',
    ]);
});

test('DefaultTypeToSchema converts union types as oneOf', function () {
    $converter = new DefaultTypeToSchema();
    $union = new UnionType(new ScalarType('string'), new ScalarType('int'));

    $result = $converter->convert($union);

    expect($result)->toBe([
        'oneOf' => [
            ['type' => 'string'],
            ['type' => 'integer'],
        ],
    ]);
});

test('DefaultTypeToSchema returns empty array for mixed type', function () {
    $converter = new DefaultTypeToSchema();
    $mixed = new \Lens\Types\MixedType();

    $result = $converter->convert($mixed);

    expect($result)->toBe([]);
});
