<?php

declare(strict_types=1);

namespace Lens\Types;

final readonly class EnumType implements Type
{
    /** @var array<array-key, string|int> */
    public array $values;

    public function __construct(
        public string $className,
        string|int ...$values,
    ) {
        $this->values = $values;
    }

    public function isEqual(Type $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        if ($this->className !== $other->className) {
            return false;
        }

        if (count($this->values) !== count($other->values)) {
            return false;
        }

        foreach ($this->values as $i => $value) {
            if ($value !== $other->values[$i]) {
                return false;
            }
        }

        return true;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);
    }

    public function __toString(): string
    {
        $vals = implode(', ', array_map(
            fn (string|int $v) => is_string($v) ? "'{$v}'" : (string) $v,
            $this->values,
        ));

        return "{$this->className}[{$vals}]";
    }
}
