<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class GenericType implements Type
{
    /** @var Type[] */
    public array $generics;

    public function __construct(
        public string $name,
        Type ...$generics,
    ) {
        $this->generics = $generics;
    }

    public function isEqual(Type $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if (count($this->generics) !== count($other->generics)) {
            return false;
        }

        foreach ($this->generics as $i => $generic) {
            if (! $generic->isEqual($other->generics[$i])) {
                return false;
            }
        }

        return true;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);

        foreach ($this->generics as $generic) {
            $generic->traverse($callback);
        }
    }

    public function __toString(): string
    {
        if ($this->generics === []) {
            return $this->name;
        }

        $inner = implode(', ', array_map(fn (Type $t) => (string) $t, $this->generics));

        return "{$this->name}<{$inner}>";
    }
}
