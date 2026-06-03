<?php

declare(strict_types=1);

namespace Lens;

use Lens\Infer\Engine;
use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;

final class OpenApi
{
    public static function generate(OpenApiConfig $config): array
    {
        $engine = new Engine($config->sources ?: [getcwd() . '/src']);
        $builder = new OpenApiBuilder();

        return $builder->buildFromInfer($engine, $config);
    }
}
