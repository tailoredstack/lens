<?php

declare(strict_types=1);

namespace Lens\Extensions\Exception;

use Tempest\Http\HttpException;

/**
 * Maps Tempest HttpException to OpenAPI responses.
 */
final class TempestHttpExceptionToResponse implements ExceptionToResponseExtension
{
    public function supports(\Throwable $exception): bool
    {
        return $exception instanceof HttpException;
    }

    public function convert(\Throwable $exception): array
    {
        $status = $exception->getStatus();
        $code = is_int($status) ? $status : 500;

        return [
            (string) $code => [
                'description' => $exception->getMessage(),
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'code' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
