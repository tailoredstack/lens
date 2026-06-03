<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class IterableType implements Type
{
    public function __construct(
        public Type $value,
    ) {}

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->value->isEqual($other->value);
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
        $this->value->traverse($callback);
    }

    public function __toString(): string
    {
        return "iterable<{$this->value}>";
    }
}
