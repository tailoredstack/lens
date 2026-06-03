<?php

declare(strict_types=1);

namespace Lens\Builder;

use Lens\Infer\Engine;
use Lens\Config\OpenApiConfig;
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
    public function buildFromInfer(Engine $engine, ?OpenApiConfig $config = null): array
    {
        $engine->analyze();
        $types = $engine->getTypes();
        $operations = $engine->getOperations();

        $schemas = [];

        foreach ($types as $fqcn => $type) {
            if (! $type instanceof NamedObjectType) {
                continue;
            }

            $schemas[$fqcn] = $this->schemaFromNamedObject($type);
        }

        $title = $config->title ?? 'Lens OpenAPI';
        $version = $config->version ?? '0.0.0';

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $title,
                'version' => $version,
            ],
            'servers' => [
                ['url' => $config->basePath ?? '/'],
            ],
            'paths' => $this->buildPaths($operations, $types),
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }

    private function buildPaths(array $operations, array $types): array
    {
        $paths = [];

        foreach ($operations as $fqcn => $methods) {
            foreach ($methods as $name => $meta) {
                $http = $meta['http'] ?? null;
                $path = $meta['path'] ?? null;

                if ($path === null) {
                    $path = '/' . str_replace('\\', '/', strtolower($fqcn)) . '/' . $name;
                }

                $verb = $http ?? 'get';

                $responses = [
                    '200' => [
                        'description' => 'OK',
                        'content' => [
                            'application/json' => [
                                'schema' => $this->schemaFromType($meta['return']),
                            ],
                        ],
                    ],
                ];

                $params = [];
                foreach ($meta['params'] as $p) {
                    $params[] = [
                        'name' => $p['name'],
                        'in' => 'query',
                        'schema' => $this->schemaFromType($p['type']),
                    ];
                }

                $paths[$path] = [
                    $verb => [
                        'operationId' => $fqcn . '::' . $name,
                        'responses' => $responses,
                        'parameters' => $params,
                    ],
                ];
            }
        }

        return $paths;
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
