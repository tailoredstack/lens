<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class ScalarType implements Type
{
    private const array SCALARS = ['string', 'int', 'float', 'bool'];

    public function __construct(
        public string $name,
    ) {
        assert(in_array($name, self::SCALARS, true), "Invalid scalar type: {$name}");
    }

    public function isEqual(Type $other): bool
    {
        return $other instanceof self && $this->name === $other->name;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
