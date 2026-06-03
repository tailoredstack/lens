<?php

declare(strict_types=1);

namespace Lens\Extensions\Operation;

use Lens\Infer\Engine;
use Lens\Types\Type;
use Lens\Types\VoidType;

/**
 * Transforms Tempest controller methods into OpenAPI operations.
 * Handles #[Get], #[Post], #[Put], #[Patch], #[Delete], #[Route] attributes.
 */
final class TempestOperationTransformer implements OperationTransformer
{
    public function transform(Engine $engine, string $controllerClass, string $method, Type $returnType, array $params): ?array
    {
        // Skip if void return
        if ($returnType instanceof VoidType) {
            return null;
        }

        // Build operation from inferred data
        $operation = [
            'operationId' => $controllerClass . '::' . $method,
            'summary' => $this->extractSummary($controllerClass, $method),
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [], // Will be filled by TypeToSchema
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
            // Simple heuristic: scalar types → query/path, objects → body
            $paramType = $param['type'];
            $paramName = $param['name'];

            if ($this->isScalarType($paramType)) {
                $queryParams[] = [
                    'name' => $paramName,
                    'in' => 'query',
                    'schema' => [], // Will be filled by TypeToSchema
                ];
            } else {
                // Assume body for complex types
                $bodyParams[$paramName] = []; // Will be filled by TypeToSchema
            }
        }

        if ($queryParams !== []) {
            $operation['parameters'] = $queryParams;
        }

        if ($bodyParams !== []) {
            $operation['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $bodyParams,
                        ],
                    ],
                ],
            ];
        }

        return $operation;
    }

    private function extractSummary(string $controllerClass, string $method): string
    {
        // Convert method name to human-readable summary
        $words = preg_split('/(?=[A-Z])/', $method);
        $words = array_filter($words, fn ($w) => $w !== '');
        return ucfirst(strtolower(implode(' ', $words)));
    }

    private function isScalarType(Type $type): bool
    {
        $scalarTypes = ['string', 'int', 'float', 'bool'];
        $typeName = (string) $type;
        return in_array($typeName, $scalarTypes, true);
    }
}
