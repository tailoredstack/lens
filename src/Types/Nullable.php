<?php

declare(strict_types=1);

namespace Lens\Types;

/**
 * Wraps any Type with a nullable flag.
 *
 * Represents `?Type` or `Type|null` in PHP type declarations.
 * The nullable flag is separate from the inner type so that
 * the TypeToSchema chain can unwrap it and set `nullable: true`
 * on the resulting schema.
 */
final readonly class Nullable implements Type
{
    public function __construct(
        public Type $inner,
    ) {}

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->inner->isEqual($other->inner);
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
        $this->inner->traverse($callback);
    }

    public function __toString(): string
    {
        return "?{$this->inner}";
    }
}
