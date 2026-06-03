<?php

declare(strict_types=1);

namespace Lens\Builder;

use Lens\Config\OpenApiConfig;
use Lens\Extensions\TypeToSchema\DefaultTypeToSchema;
use Lens\Extensions\TypeToSchema\TypeToSchemaExtension;
use Lens\Infer\Engine;
use Lens\Types\NamedObjectType;
use Lens\Types\PropertyType;

final class OpenApiBuilder
{
    /** @var TypeToSchemaExtension[] */
    private array $typeToSchemaExtensions = [];

    public function __construct()
    {
        $this->typeToSchemaExtensions[] = new DefaultTypeToSchema();
    }

    public function addTypeToSchemaExtension(TypeToSchemaExtension $extension): void
    {
        array_unshift($this->typeToSchemaExtensions, $extension);
    }

    public function buildFromInfer(Engine $engine, ?OpenApiConfig $config = null): array
    {
        $engine->analyze();
        $types = $engine->getTypes();
        $operations = $engine->getOperations();

        // Initialize extensions from config
        if ($config !== null && $config->extensions !== []) {
            foreach ($config->extensions as $extClass) {
                if (class_exists($extClass)) {
                    $ext = new $extClass();
                    if ($ext instanceof TypeToSchemaExtension) {
                        $this->addTypeToSchemaExtension($ext);
                    }
                }
            }
        }

        $schemas = [];

        foreach ($types as $fqcn => $type) {
            if (! $type instanceof NamedObjectType) {
                continue;
            }

            // skip excluded namespaces
            if ($this->isExcluded($fqcn, $config)) {
                continue;
            }

            $schemas[$fqcn] = $this->schemaFromNamedObject($type);
        }

        $title = $config->title ?? 'Lens OpenAPI';
        $version = $config->version ?? '0.0.0';
        $basePath = $config->basePath ?? '/';
        $apiDomain = $config->apiDomain ?? null;

        // Build servers
        $servers = $config->servers ?? null;
        if ($servers === null) {
            $serverUrl = $apiDomain !== null
                ? rtrim($apiDomain, '/') . $basePath
                : $basePath;
            $servers = [['url' => $serverUrl]];
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $title,
                'version' => $version,
            ],
            'servers' => $servers,
            'paths' => $this->buildPaths($operations, $config),
            'components' => [
                'schemas' => $schemas,
            ],
        ];
    }

    private function isExcluded(string $fqcn, ?OpenApiConfig $config): bool
    {
        if ($config === null) {
            return false;
        }

        foreach ($config->exclude as $pattern) {
            if (str_contains($fqcn, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function buildPaths(array $operations, ?OpenApiConfig $config): array
    {
        $paths = [];
        $includeInternal = $config->includeInternal ?? false;

        foreach ($operations as $fqcn => $methods) {
            // skip excluded namespaces
            if ($this->isExcluded($fqcn, $config)) {
                continue;
            }

            foreach ($methods as $name => $meta) {
                // skip internal methods unless configured otherwise
                if (! $includeInternal && str_starts_with($name, '__')) {
                    continue;
                }

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
            if (! $p->type instanceof \Lens\Types\Nullable) {
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
        foreach ($this->typeToSchemaExtensions as $ext) {
            if ($ext->supports($type)) {
                return $ext->convert($type);
            }
        }

        // Fallback
        return [];
    }
}
