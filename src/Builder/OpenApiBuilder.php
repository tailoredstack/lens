<?php

declare(strict_types=1);

namespace Lens\Builder;

use Lens\Infer\Engine;
use Lens\Types\NamedObjectType;
use Lens\Types\ObjectType;
use Lens\Types\PropertyType;
use Lens\Types\ScalarType;
use Lens\Types\Nullable;
use Lens\Types\MixedType;
use Lens\Types\ArrayType;
use Lens\Types\UnionType;
use Lens\Types\NamedObjectType as RefNamedObject;
use Lens\Types\EnumType;
use Lens\Types\LiteralString;
use Lens\Types\LiteralInteger;
use Lens\Types\LiteralType;

final class OpenApiBuilder
{
    public function buildFromInfer(Engine $engine): array
    {
        $engine->analyze();
        $types = $engine->getTypes();

        $schemas = [];

        foreach ($types as $fqcn => $type) {
            if (! $type instanceof NamedObjectType) {
                continue;
            }

            $schemas[$fqcn] = $this->schemaFromNamedObject($type);
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Lens OpenAPI',
                'version' => '0.0.0',
            ],
            'paths' => new \ArrayObject(),
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }

    private function schemaFromNamedObject(NamedObjectType $obj): array
    {
        $props = [];
        $required = [];

        foreach ($obj->properties as $p) {
            $props[$p->name] = $this->schemaFromType($p->type);

            // property required if not nullable
            if (! $p->type instanceof Nullable) {
                $required[] = $p->name;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $props,
        ];

        if ($required !== []) {
            $schema['required'] = array_values($required);
        }

        return $schema;
    }

    private function schemaFromType($type): array
    {
        // Enums and literals handled early to produce concise schemas
        if ($type instanceof EnumType) {
            $vals = $type->values;
            // infer underlying primitive type from first value
            $first = $vals[0] ?? null;
            $t = is_int($first) ? 'integer' : 'string';
            return [
                'type' => $t,
                'enum' => $vals,
            ];
        }

        if ($type instanceof LiteralType) {
            if ($type instanceof LiteralString) {
                return ['type' => 'string', 'enum' => [$type->value]];
            }

            if ($type instanceof LiteralInteger) {
                return ['type' => 'integer', 'enum' => [$type->value]];
            }
        }

        if ($type instanceof Nullable) {
            $inner = $this->schemaFromType($type->inner);
            $inner['nullable'] = true;
            return $inner;
        }

        if ($type instanceof ScalarType) {
            $map = [
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'string' => 'string',
            ];

            return ['type' => $map[$type->name] ?? 'string'];
        }

        if ($type instanceof ArrayType) {
            return [
                'type' => 'array',
                'items' => $this->schemaFromType($type->value),
            ];
        }

        if ($type instanceof UnionType) {
            // Simplify: represent union as oneOf
            $schemas = [];
            foreach ($type->types as $t) {
                $schemas[] = $this->schemaFromType($t);
            }
            return ['oneOf' => $schemas];
        }

        if ($type instanceof RefNamedObject) {
            return ['$ref' => "#/components/schemas/{$type->className}"];
        }

        if ($type instanceof MixedType) {
            return [];
        }

        // Fallback
        return [];
    }
}
