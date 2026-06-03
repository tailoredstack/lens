<?php

declare(strict_types=1);

namespace Lens\Types;

/**
 * ObjectType resolved from a known class name.
 *
 * Unlike the base ObjectType which may represent an anonymous/anonymous-like
 * shape, NamedObjectType is always associated with a specific, resolvable class
 * in the project. Used as input for TypeToSchemaExtension chain.
 */
final class NamedObjectType extends ObjectType
{
    public function __construct(
        string $className,
        PropertyType ...$properties,
    ) {
        parent::__construct($className, ...$properties);
    }

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && parent::isEqual($other);
    }
}
