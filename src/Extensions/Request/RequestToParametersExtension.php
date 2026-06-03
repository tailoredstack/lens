<?php

declare(strict_types=1);

namespace Lens\Extensions\Request;

/**
 * Transforms a Tempest Request class into OpenAPI parameters.
 */
interface RequestToParametersExtension
{
    public function supports(string $requestClass): bool;

    public function convert(string $requestClass): array;
}
