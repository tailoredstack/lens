<?php

declare(strict_types=1);

namespace Lens\Extensions\Request;

/**
 * Default implementation that transforms Tempest Request DTOs into OpenAPI parameters.
 */
final class DefaultRequestToParameters implements RequestToParametersExtension
{
    public function supports(string $requestClass): bool
    {
        // Support any class that ends with "Request"
        return str_ends_with($requestClass, 'Request');
    }

    public function convert(string $requestClass): array
    {
        // For now, return empty - would need to analyze the Request class properties
        // This is a placeholder for future implementation
        return [
            'requestBody' => [
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ],
            ],
        ];
    }
}
