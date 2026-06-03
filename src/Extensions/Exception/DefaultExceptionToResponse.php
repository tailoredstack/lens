<?php

declare(strict_types=1);

namespace Lens\Extensions\Exception;

/**
 * Default implementation mapping common Tempest exceptions to OpenAPI responses.
 */
class DefaultExceptionToResponse implements ExceptionToResponse
{
    /** @var array<string, array{status: int, description: string, content?: array}> */
    private const EXCEPTION_MAP = [
        'Tempest\Http\Exceptions\NotFoundException' => [
            'status' => 404,
            'description' => 'Not Found - The requested resource was not found',
        ],
        'Tempest\Http\Exceptions\ValidationException' => [
            'status' => 422,
            'description' => 'Unprocessable Entity - Validation failed',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
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
        'Tempest\Http\Exceptions\UnauthorizedException' => [
            'status' => 401,
            'description' => 'Unauthorized - Authentication required',
        ],
        'Tempest\Http\Exceptions\ForbiddenException' => [
            'status' => 403,
            'description' => 'Forbidden - Access denied',
        ],
        'Tempest\Http\Exceptions\BadRequestException' => [
            'status' => 400,
            'description' => 'Bad Request - Invalid request syntax',
        ],
    ];

    public function supports(string $exceptionClass): bool
    {
        return isset(self::EXCEPTION_MAP[$exceptionClass]);
    }

    /**
     * @param string $exceptionClass
     * @return array<string, array{description: string, content?: array}>
     */
    public function convert(string $exceptionClass): array
    {
        if (! isset(self::EXCEPTION_MAP[$exceptionClass])) {
            return [];
        }

        /** @var array{status: int, description: string, content?: array} $mapping */
        $mapping = self::EXCEPTION_MAP[$exceptionClass];
        $status = (string) $mapping['status'];

        /** @var array{description: string, content?: array} $response */
        $response = [
            'description' => $mapping['description'],
        ];

        if (isset($mapping['content'])) {
            $response['content'] = $mapping['content'];
        }

        return [$status => $response];
    }
}
