<?php

declare(strict_types=1);

namespace Lens\Extensions\Exception;

use Throwable;

/**
 * Maps an exception type to an OpenAPI response.
 */
interface ExceptionToResponseExtension
{
    public function supports(Throwable $exception): bool;

    public function convert(Throwable $exception): array;
}
