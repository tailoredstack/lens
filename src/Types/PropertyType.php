<?php

declare(strict_types=1);

namespace Lens\Types;

/**
 * DTO pairing a property name with its inferred type.
 */
final readonly class PropertyType
{
    public function __construct(
        public string $name,
        public Type $type,
    ) {}

    public function isEqual(PropertyType $other): bool
    {
        return $this->name === $other->name && $this->type->isEqual($other->type);
    }

    public function __toString(): string
    {
        return "{$this->name}: {$this->type}";
    }
}
