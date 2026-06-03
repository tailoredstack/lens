<?php

declare(strict_types=1);

namespace Lens\Extensions\Exception;

use Tempest\Validation\Exceptions\ValidationException;

/**
 * Maps Tempest ValidationException to 422 response.
 */
final class TempestValidationExceptionToResponse implements ExceptionToResponseExtension
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof ValidationException;
    }

    public function convert(\Throwable $exception): array
    {
        return [
            '422' => [
                'description' => 'Validation error',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'errors' => [
                                    'type' => 'object',
                                    'additionalProperties' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
