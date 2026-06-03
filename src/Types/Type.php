<?php

declare(strict_types=1);

namespace Lens\Types;

/**
 * Base interface for all inferred PHP types.
 *
 * Every type in the inference system implements this interface,
 * providing equality comparison, traversal, and string representation.
 */
interface Type
{
    public function isEqual(Type $other): bool;

    public function traverse(callable $callback): void;

    public function __toString(): string;
}
