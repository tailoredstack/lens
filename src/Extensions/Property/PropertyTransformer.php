<?php

declare(strict_types=1);

namespace Lens\Extensions\Property;

use Lens\Types\Type;

/**
 * Transforms a PHP property into an OpenAPI schema property.
 */
interface PropertyTransformer
{
    public function supports(string $propertyName, Type $type): bool;

    public function transform(string $propertyName, Type $type): array;
}
