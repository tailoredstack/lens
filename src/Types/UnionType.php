<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class UnionType implements Type
{
    /** @var Type[] */
    public array $types;

    public function __construct(Type ...$types)
    {
        $this->types = $types;
    }

    public function isEqual(Type $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        if (count($this->types) !== count($other->types)) {
            return false;
        }

        foreach ($this->types as $i => $type) {
            if (! $type->isEqual($other->types[$i])) {
                return false;
            }
        }

        return true;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);

        foreach ($this->types as $type) {
            $type->traverse($callback);
        }
    }

    public function __toString(): string
    {
        return implode('|', array_map(fn (Type $t) => (string) $t, $this->types));
    }
}
