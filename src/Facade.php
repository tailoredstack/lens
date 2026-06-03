<?php

declare(strict_types=1);

namespace Lens;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Discovery\OpenApiDiscovery;
use Lens\Extensions\Exception\ExceptionToResponseExtension;
use Lens\Extensions\Operation\OperationTransformer;
use Lens\Extensions\TypeToSchema\TypeToSchemaExtension;
use Lens\Infer\Engine;

/**
 * Facade for OpenAPI generation.
 */
final class Facade
{
    private static ?OpenApiBuilder $builder = null;
    private static ?OpenApiConfig $config = null;

    public static function configure(OpenApiConfig $config): void
    {
        self::$config = $config;
    }

    public static function getConfig(): OpenApiConfig
    {
        return self::$config ?? new OpenApiConfig();
    }

    public static function getBuilder(): OpenApiBuilder
    {
        if (self::$builder === null) {
            self::$builder = new OpenApiBuilder();
        }
        return self::$builder;
    }

    public static function addTypeToSchemaExtension(TypeToSchemaExtension $extension): void
    {
        self::getBuilder()->addTypeToSchemaExtension($extension);
    }

    public static function addOperationTransformer(OperationTransformer $transformer): void
    {
        // Future: wire operation transformers
    }

    public static function addExceptionToResponseExtension(ExceptionToResponseExtension $extension): void
    {
        // Future: wire exception mappers
    }

    public static function generate(?OpenApiConfig $config = null): array
    {
        $config = $config ?? self::getConfig();
        $engine = new Engine($config->sources ?: [getcwd() . '/src']);
        $builder = self::getBuilder();

        return $builder->buildFromInfer($engine, $config);
    }

    public static function export(?string $path = null): string
    {
        $config = self::getConfig();
        $path = $path ?? $config->exportPath;

        $spec = self::generate();

        if ($config->outputFormat === 'yaml' || str_ends_with($path, '.yaml') || str_ends_with($path, '.yml')) {
            $content = \Symfony\Component\Yaml\Yaml::dump($spec, 10, 2);
        } else {
            $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        file_put_contents($path, $content . PHP_EOL);

        return $path;
    }

    public static function reset(): void
    {
        self::$builder = null;
        self::$config = null;
    }
}
