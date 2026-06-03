<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class LiteralInteger extends LiteralType
{
    public function __construct(
        public int $value,
    ) {}

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
