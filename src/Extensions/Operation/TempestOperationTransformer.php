<?php

declare(strict_types=1);

namespace Lens\Extensions\Operation;

use Lens\Infer\Engine;
use Lens\Types\NamedObjectType;
use Lens\Types\Type;
use Lens\Types\VoidType;

/**
 * Transforms Tempest controller methods into OpenAPI operations.
 * Handles #[Get], #[Post], #[Put], #[Patch], #[Delete], #[Route] attributes.
 */
final class TempestOperationTransformer implements OperationTransformer
{
    public function transform(
        Engine $engine,
        string $controllerClass,
        string $method,
        Type $returnType,
        array $params,
        \Closure $schemaConverter,
        array $meta = [],
    ): ?array {
        // Skip if void return
        if ($returnType instanceof VoidType) {
            return null;
        }

        // Convert return type to schema
        $responseSchema = $schemaConverter($returnType);

        // Build operation from inferred data
        $operation = [
            'operationId' => $controllerClass . '::' . $method,
            'summary' => $this->extractSummary($controllerClass, $method),
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => $responseSchema,
                        ],
                    ],
                ],
            ],
        ];

        // Add parameters
        $queryParams = [];
        $pathParams = [];
        $bodyParams = [];

        foreach ($params as $param) {
            $paramType = $param['type'];
            $paramName = $param['name'];

            // Check if this is a Request DTO (ends with "Request")
            if ($this->isRequestDto($paramType)) {
                // Expand Request DTO properties into body
                $expanded = $this->expandRequestDto($paramType, $engine, $schemaConverter);
                // Merge properties
                foreach ($expanded['properties'] as $propName => $propSchema) {
                    $bodyParams[$propName] = $propSchema;
                }
                // Merge required
                if (isset($expanded['required']) && $expanded['required'] !== []) {
                    // Store required for later
                    if (! isset($operation['requestBody']['__required'])) {
                        $operation['requestBody']['__required'] = [];
                    }
                    $operation['requestBody']['__required'] = array_merge($operation['requestBody']['__required'], $expanded['required']);
                }
            } elseif ($this->isScalarType($paramType)) {
                $paramSchema = [
                    'name' => $paramName,
                    'in' => 'query',
                    'schema' => $schemaConverter($paramType),
                ];

                // Add description from docblock
                if (isset($meta['paramDescriptions'][$paramName])) {
                    $paramSchema['description'] = $meta['paramDescriptions'][$paramName];
                }

                $queryParams[] = $paramSchema;
            } else {
                // Assume body for complex types
                $bodyParams[$paramName] = $schemaConverter($paramType);
            }
        }

        if ($queryParams !== []) {
            $operation['parameters'] = $queryParams;
        }

        if ($bodyParams !== []) {
            $requestBodySchema = [
                'type' => 'object',
                'properties' => $bodyParams,
            ];

            // Add required if any
            if (isset($operation['requestBody']['__required']) && $operation['requestBody']['__required'] !== []) {
                $requestBodySchema['required'] = array_unique($operation['requestBody']['__required']);
                unset($operation['requestBody']['__required']);
            }

            $operation['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => $requestBodySchema,
                    ],
                ],
            ];
        }

        return $operation;
    }

    private function isRequestDto(Type $type): bool
    {
        if (! $type instanceof NamedObjectType) {
            return false;
        }

        // Check if class name ends with "Request"
        $shortName = substr($type->className, strrpos($type->className, '\\') + 1);
        return str_ends_with($shortName, 'Request');
    }

    private function expandRequestDto(Type $type, Engine $engine, \Closure $schemaConverter): array
    {
        if (! $type instanceof NamedObjectType) {
            return [];
        }

        $className = $type->className;
        $types = $engine->getTypes();

        // Check if we have this type in our collected types
        if (! isset($types[$className])) {
            return [];
        }

        $objectType = $types[$className];
        if (! $objectType instanceof NamedObjectType) {
            return [];
        }

        $properties = [];
        $required = [];

        foreach ($objectType->properties as $prop) {
            $properties[$prop->name] = $schemaConverter($prop->type);

            // Mark as required if not nullable
            if (! $prop->type instanceof \Lens\Types\Nullable) {
                $required[] = $prop->name;
            }
        }

        // Return properties with required at schema level
        return [
            'properties' => $properties,
            'required' => $required,
        ];
    }

    private function extractSummary(string $controllerClass, string $method): string
    {
        // Convert method name to human-readable summary
        $words = preg_split('/(?=[A-Z])/', $method);
        if ($words === false) {
            $words = [$method];
        }
        $words = array_filter($words, static fn ($w) => $w !== '');
        return ucfirst(strtolower(implode(' ', $words)));
    }

    private function isScalarType(Type $type): bool
    {
        $scalarTypes = ['string', 'int', 'float', 'bool'];
        $typeName = (string) $type;
        return in_array($typeName, $scalarTypes, true);
    }
}
