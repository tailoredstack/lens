<?php

declare(strict_types=1);

use Lens\Types\ArrayType;
use Lens\Types\EnumType;
use Lens\Types\GenericType;
use Lens\Types\IntersectionType;
use Lens\Types\IterableType;
use Lens\Types\LiteralInteger;
use Lens\Types\LiteralString;
use Lens\Types\MixedType;
use Lens\Types\NamedObjectType;
use Lens\Types\NeverType;
use Lens\Types\Nullable;
use Lens\Types\NullType;
use Lens\Types\ObjectType;
use Lens\Types\PropertyType;
use Lens\Types\ScalarType;
use Lens\Types\Type;
use Lens\Types\UnionType;
use Lens\Types\VoidType;

test('ScalarType', function () {
    $string = new ScalarType('string');
    $int = new ScalarType('int');
    $float = new ScalarType('float');
    $bool = new ScalarType('bool');

    expect($string->name)->toBe('string');
    expect((string) $string)->toBe('string');
    expect((string) $int)->toBe('int');
    expect((string) $float)->toBe('float');
    expect((string) $bool)->toBe('bool');

    expect($string->isEqual(new ScalarType('string')))->toBeTrue();
    expect($string->isEqual($int))->toBeFalse();
});

test('ArrayType', function () {
    $intArr = new ArrayType(new ScalarType('int'));
    expect((string) $intArr)->toBe('list<int>');
    expect($intArr->isList)->toBeTrue();

    $strArr = new ArrayType(new ScalarType('string'), isList: false);
    expect((string) $strArr)->toBe('array<string>');

    expect($intArr->isEqual(new ArrayType(new ScalarType('int'))))->toBeTrue();
    expect($intArr->isEqual($strArr))->toBeFalse();

    $traversed = [];
    $intArr->traverse(function (Type $t) use (&$traversed) {
        $traversed[] = $t;
    });
    expect($traversed)->toHaveCount(2);
    expect($traversed[0])->toBe($intArr);
    expect($traversed[1])->toBe($intArr->value);
});

test('ObjectType', function () {
    $props = [
        new PropertyType('id', new ScalarType('int')),
        new PropertyType('name', new ScalarType('string')),
    ];
    $obj = new ObjectType('Book', ...$props);

    expect($obj->className)->toBe('Book');
    expect($obj->properties)->toHaveCount(2);
    expect((string) $obj)->toContain('Book');
    expect((string) $obj)->toContain('id: int');

    $same = new ObjectType('Book', ...$props);
    expect($obj->isEqual($same))->toBeTrue();

    $diff = new ObjectType('Author', ...$props);
    expect($obj->isEqual($diff))->toBeFalse();
});

test('NamedObjectType', function () {
    $named = new NamedObjectType('App\Models\Book');
    expect($named->className)->toBe('App\Models\Book');
    expect($named)->toBeInstanceOf(ObjectType::class);
});

test('UnionType', function () {
    $union = new UnionType(new ScalarType('string'), new NullType());
    expect((string) $union)->toBe('string|null');

    expect($union->isEqual(new UnionType(new ScalarType('string'), new NullType())))->toBeTrue();
    expect($union->isEqual(new UnionType(new ScalarType('int'), new NullType())))->toBeFalse();
});

test('IntersectionType', function () {
    $inter = new IntersectionType(new NamedObjectType('A'), new NamedObjectType('B'));
    expect((string) $inter)->toContain('&');

    expect($inter->isEqual(new IntersectionType(new NamedObjectType('A'), new NamedObjectType('B'))))->toBeTrue();
});

test('GenericType', function () {
    $generic = new GenericType('Collection', new NamedObjectType('Book'));
    expect((string) $generic)->toContain('Collection');
    expect((string) $generic)->toContain('Book');

    expect($generic->isEqual(new GenericType('Collection', new NamedObjectType('Book'))))->toBeTrue();
    expect($generic->isEqual(new GenericType('Collection', new NamedObjectType('Author'))))->toBeFalse();
});

test('EnumType', function () {
    $enum = new EnumType('Status', 'draft', 'published', 'archived');
    expect($enum->className)->toBe('Status');
    expect($enum->values)->toBe(['draft', 'published', 'archived']);
    expect((string) $enum)->toContain('draft');

    expect($enum->isEqual(new EnumType('Status', 'draft', 'published', 'archived')))->toBeTrue();
    expect($enum->isEqual(new EnumType('Status', 'draft', 'published')))->toBeFalse();
});

test('LiteralString', function () {
    $lit = new LiteralString('hello');
    expect((string) $lit)->toBe("'hello'");
    expect($lit->value)->toBe('hello');

    expect($lit->isEqual(new LiteralString('hello')))->toBeTrue();
    expect($lit->isEqual(new LiteralString('world')))->toBeFalse();
});

test('LiteralInteger', function () {
    $lit = new LiteralInteger(42);
    expect((string) $lit)->toBe('42');
    expect($lit->value)->toBe(42);

    expect($lit->isEqual(new LiteralInteger(42)))->toBeTrue();
});

test('IterableType', function () {
    $iter = new IterableType(new NamedObjectType('Book'));
    expect((string) $iter)->toContain('iterable');
    expect((string) $iter)->toContain('Book');
});

test('NeverType', function () {
    $never = new NeverType();
    expect((string) $never)->toBe('never');
    expect($never->isEqual(new NeverType()))->toBeTrue();
});

test('VoidType', function () {
    $void = new VoidType();
    expect((string) $void)->toBe('void');
    expect($void->isEqual(new VoidType()))->toBeTrue();
});

test('MixedType', function () {
    $mixed = new MixedType();
    expect((string) $mixed)->toBe('mixed');
    expect($mixed->isEqual(new MixedType()))->toBeTrue();
});

test('NullType', function () {
    $null = new NullType();
    expect((string) $null)->toBe('null');
    expect($null->isEqual(new NullType()))->toBeTrue();
});

test('Nullable wrapper', function () {
    $nullable = new Nullable(new ScalarType('string'));
    expect((string) $nullable)->toBe('?string');

    expect($nullable->isEqual(new Nullable(new ScalarType('string'))))->toBeTrue();
    expect($nullable->isEqual(new Nullable(new ScalarType('int'))))->toBeFalse();

    $traversed = [];
    $nullable->traverse(function (Type $t) use (&$traversed) {
        $traversed[] = $t;
    });
    expect($traversed)->toHaveCount(2);
});

test('PropertyType', function () {
    $prop = new PropertyType('title', new ScalarType('string'));
    expect((string) $prop)->toBe('title: string');
    expect($prop->name)->toBe('title');
});

test('Type equality across different types', function () {
    $types = [
        new ScalarType('string'),
        new ArrayType(new ScalarType('int')),
        new NullType(),
        new VoidType(),
        new NeverType(),
        new MixedType(),
        new ObjectType('Foo'),
        new NamedObjectType('Foo'),
    ];

    foreach ($types as $i => $a) {
        foreach ($types as $j => $b) {
            if ($i === $j) {
                expect($a->isEqual($b))->toBeTrue("Type at index {$i} should equal itself");
            } elseif (get_class($a) === get_class($b)) {
                // Same class, different values — equality depends on constructor args
                expect($a->isEqual($b))
                    ->toBe(
                        (string) $a === (string) $b,
                        'Types of class ' . get_class($a) . ' should match string comparison',
                    );
            } else {
                expect($a->isEqual($b))->toBeFalse('Different types should not be equal');
            }
        }
    }
});
