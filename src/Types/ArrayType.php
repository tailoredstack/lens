<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class ArrayType implements Type
{
    public function __construct(
        public Type $value,
        public bool $isList = true,
    ) {}

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->isList === $other->isList && $this->value->isEqual($other->value);
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
        $this->value->traverse($callback);
    }

    public function __toString(): string
    {
        $prefix = $this->isList ? 'list<' : 'array<';

        return $prefix . (string) $this->value . '>';
    }
}
