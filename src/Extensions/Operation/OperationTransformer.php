<?php

declare(strict_types=1);

namespace Lens\Extensions\Operation;

use Lens\Infer\Engine;
use Lens\Types\Type;

/**
 * Transforms a controller method into an OpenAPI operation.
 */
interface OperationTransformer
{
    /**
     * @param array<array{name: string, type: Type}> $params
     * @param \Closure(Type): array $schemaConverter
     * @param array $meta Additional metadata (docblock, attributes)
     */
    public function transform(
        Engine $engine,
        string $controllerClass,
        string $method,
        Type $returnType,
        array $params,
        \Closure $schemaConverter,
        array $meta = []
    ): ?array;
}
