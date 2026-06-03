<?php

declare(strict_types=1);

namespace Lens\Types;

class ObjectType implements Type
{
    /** @var PropertyType[] */
    public readonly array $properties;

    public function __construct(
        public readonly string $className,
        PropertyType ...$properties,
    ) {
        $this->properties = $properties;
    }

    public function isEqual(Type $other): bool
    {
        if (! $other instanceof self || $other::class !== static::class) {
            return false;
        }

        if ($this->className !== $other->className) {
            return false;
        }

        if (count($this->properties) !== count($other->properties)) {
            return false;
        }

        foreach ($this->properties as $i => $prop) {
            if (! $prop->isEqual($other->properties[$i])) {
                return false;
            }
        }

        return true;
    }

    public function traverse(callable $callback): void
    {
        $callback($this);

        foreach ($this->properties as $property) {
            $property->type->traverse($callback);
        }
    }

    public function __toString(): string
    {
        if ($this->properties === []) {
            return $this->className;
        }

        $props = implode(', ', array_map(
            fn (PropertyType $p) => (string) $p,
            $this->properties,
        ));

        return "{$this->className}{{$props}}";
    }
}
