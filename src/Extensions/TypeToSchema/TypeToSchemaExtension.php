<?php

declare(strict_types=1);

namespace Lens\Extensions\TypeToSchema;

use Lens\Types\Type;

/**
 * Converts a Lens Type into an OpenAPI Schema object.
 */
interface TypeToSchemaExtension
{
    public function supports(Type $type): bool;

    public function convert(Type $type): array;
}
