<?php

declare(strict_types=1);

namespace Lens\Types;

abstract readonly class LiteralType implements Type
{
    public function traverse(callable $callback): void
    {
        $callback($this);
    }
}
