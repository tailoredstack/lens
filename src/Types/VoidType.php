<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class VoidType implements Type
{
    public function isEqual(Type $other): bool
    {
        return $other instanceof self;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
    }

    public function __toString(): string
    {
        return 'void';
    }
}
