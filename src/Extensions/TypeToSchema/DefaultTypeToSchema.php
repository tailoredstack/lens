<?php

declare(strict_types=1);

namespace Lens\Extensions\TypeToSchema;

use Lens\Types\ArrayType;
use Lens\Types\EnumType;
use Lens\Types\LiteralInteger;
use Lens\Types\LiteralString;
use Lens\Types\NamedObjectType;
use Lens\Types\Nullable;
use Lens\Types\ObjectType;
use Lens\Types\ScalarType;
use Lens\Types\Type;
use Lens\Types\UnionType;

/**
 * Default Type→Schema converter for common PHP types.
 */
final class DefaultTypeToSchema implements TypeToSchemaExtension
{
    public function supports(Type $type): bool
    {
        return true; // Handles all types with fallback
    }

    public function convert(Type $type): array
    {
        if ($type instanceof Nullable) {
            $inner = $this->convert($type->inner);
            $inner['nullable'] = true;
            return $inner;
        }

        if ($type instanceof ScalarType) {
            return $this->scalarToSchema($type->name);
        }

        if ($type instanceof ArrayType) {
            return [
                'type' => 'array',
                'items' => $this->convert($type->value),
            ];
        }

        if ($type instanceof EnumType) {
            $first = $type->values[0] ?? null;
            $t = is_int($first) ? 'integer' : 'string';
            return [
                'type' => $t,
                'enum' => $type->values,
            ];
        }

        if ($type instanceof LiteralString) {
            return ['type' => 'string', 'enum' => [$type->value]];
        }

        if ($type instanceof LiteralInteger) {
            return ['type' => 'integer', 'enum' => [$type->value]];
        }

        if ($type instanceof NamedObjectType) {
            return ['$ref' => "#/components/schemas/{$type->className}"];
        }

        if ($type instanceof ObjectType) {
            $props = [];
            foreach ($type->properties as $prop) {
                $props[$prop->name] = $this->convert($prop->type);
            }
            return [
                'type' => 'object',
                'properties' => $props,
            ];
        }

        if ($type instanceof UnionType) {
            $schemas = array_map(fn ($t) => $this->convert($t), $type->types);
            return ['oneOf' => $schemas];
        }

        // Fallback for MixedType, VoidType, NeverType, etc.
        return [];
    }

    private function scalarToSchema(string $name): array
    {
        $map = [
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number'],
            'bool' => ['type' => 'boolean'],
            'string' => ['type' => 'string'],
        ];
        return $map[$name] ?? ['type' => 'string'];
    }
}
