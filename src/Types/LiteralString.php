<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class LiteralString extends LiteralType
{
    public function __construct(
        public string $value,
    ) {}

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return "'{$this->value}'";
    }
}
