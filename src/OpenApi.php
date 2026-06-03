<?php

declare(strict_types=1);

namespace Lens;

use Lens\Infer\Engine;
use Lens\Builder\OpenApiBuilder;

final class OpenApi
{
    public static function generate(array $sources = []): array
    {
        $engine = new Engine($sources);
        $builder = new OpenApiBuilder();

        return $builder->buildFromInfer($engine);
    }
}
