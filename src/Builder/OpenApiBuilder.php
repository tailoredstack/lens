<?php

declare(strict_types=1);

namespace Lens\Builder;

use Lens\Builder;
use Lens\Config\OpenApiConfig;
use Lens\Extensions\Exception\ExceptionToResponse;
use Lens\Extensions\Exception\DefaultExceptionToResponse;
use Lens\Extensions\Operation\OperationTransformer;
use Lens\Extensions\Operation\TempestOperationTransformer;
use Lens\Extensions\TypeToSchema\DefaultTypeToSchema;
use Lens\Extensions\TypeToSchema\TypeToSchemaExtension;
use Lens\Infer\Engine;
use Lens\Types\NamedObjectType;
use Lens\Types\PropertyType;

final class OpenApiBuilder
{
    /** @var TypeToSchemaExtension[] */
    private array $typeToSchemaExtensions = [];

    /** @var OperationTransformer[] */
    private array $operationTransformers = [];

    /** @var ExceptionToResponse[] */
    private array $exceptionToResponseExtensions = [];

    public function __construct()
    {
        $this->typeToSchemaExtensions[] = new DefaultTypeToSchema();
        $this->operationTransformers[] = new TempestOperationTransformer();
        $this->exceptionToResponseExtensions[] = new DefaultExceptionToResponse();
    }

    public function addTypeToSchemaExtension(TypeToSchemaExtension $extension): void
    {
        array_unshift($this->typeToSchemaExtensions, $extension);
    }

    public function addOperationTransformer(OperationTransformer $transformer): void
    {
        array_unshift($this->operationTransformers, $transformer);
    }

    public function addExceptionToResponseExtension(ExceptionToResponse $extension): void
    {
        array_unshift($this->exceptionToResponseExtensions, $extension);
    }

    public function buildFromInfer(Engine $engine, ?OpenApiConfig $config = null): array
    {
        $engine->analyze();
        $types = $engine->getTypes();
        $operations = $engine->getOperations();

        // Initialize extensions from config
        if ($config !== null && $config->extensions !== []) {
            foreach ($config->extensions as $extClass) {
                if (class_exists($extClass)) {
                    $ext = new $extClass();
                    if ($ext instanceof TypeToSchemaExtension) {
                        $this->addTypeToSchemaExtension($ext);
                    }
                    if ($ext instanceof ExceptionToResponse) {
                        $this->addExceptionToResponseExtension($ext);
                    }
                }
            }
        }

        $schemas = [];

        foreach ($types as $fqcn => $type) {
            if (! $type instanceof NamedObjectType) {
                continue;
            }

            // skip excluded namespaces
            if ($this->isExcluded($fqcn, $config)) {
                continue;
            }

            $schemas[$fqcn] = $this->schemaFromNamedObject($type);
        }

        $title = $config->title ?? 'Lens OpenAPI';
        $version = $config->version ?? '0.0.0';
        $basePath = $config->basePath ?? '/';
        $apiDomain = $config->apiDomain ?? null;

        // Build servers
        $servers = $config->servers ?? null;
        if ($servers === null) {
            $serverUrl = $apiDomain !== null
                ? rtrim($apiDomain, '/') . $basePath
                : $basePath;
            $servers = [['url' => $serverUrl]];
        }

        // Build components
        $components = ['schemas' => $schemas];
        
        // Add security schemes from config or defaults
        $securitySchemes = $config->securitySchemes ?? [];
        
        // Add default bearer auth if not provided and security is used
        if (!isset($securitySchemes['bearerAuth'])) {
            $securitySchemes['bearerAuth'] = [
                'type' => 'http',
                'scheme' => 'bearer',
            ];
        }
        
        if ($securitySchemes !== []) {
            $components['securitySchemes'] = $securitySchemes;
        }

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => $title,
                'version' => $version,
            ],
            'servers' => $servers,
            'paths' => $this->buildPaths($operations, $config, $engine),
            'components' => $components,
        ];
    }

    private function isExcluded(string $fqcn, ?OpenApiConfig $config): bool
    {
        if ($config === null) {
            return false;
        }

        foreach ($config->exclude as $pattern) {
            if (str_contains($fqcn, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function buildPaths(array $operations, ?OpenApiConfig $config, Engine $engine): array
    {
        $paths = [];
        $includeInternal = $config->includeInternal ?? false;

        foreach ($operations as $fqcn => $methods) {
            // skip excluded namespaces
            if ($this->isExcluded($fqcn, $config)) {
                continue;
            }

            // Extract controller name from FQCN
            $shortName = substr($fqcn, strrpos($fqcn, '\\') + 1);
            $isController = str_ends_with($shortName, 'Controller');
            $controllerBase = $isController
                ? lcfirst(str_replace('Controller', '', $shortName))
                : strtolower($shortName);

            foreach ($methods as $name => $meta) {
                $methodName = is_string($name) ? $name : (string) $name;
                
                // skip internal methods unless configured otherwise
                if (! $includeInternal && str_starts_with($methodName, '__')) {
                    continue;
                }

                $http = (string) ($meta['http'] ?? 'get');
                $path = $meta['path'] ?? null;

                // Generate path from controller/method if not specified
                if ($path === null) {
                    if ($isController) {
                        // UserController + index → GET /users
                        // UserController + show → GET /users/{id}
                        $path = '/' . $controllerBase;

                        // Add resource ID for show/update/delete methods
                        if (in_array($methodName, ['show', 'update', 'patch', 'delete', 'destroy'])) {
                            $path .= '/{id}';
                        }

                        // For methods that aren't standard REST, append method name
                        if (! in_array($methodName, ['index', 'show', 'store', 'update', 'patch', 'delete', 'destroy'])) {
                            $path .= '/' . $methodName;
                        }
                    } else {
                        $path = '/' . str_replace('\\', '/', strtolower($fqcn)) . '/' . $methodName;
                    }
                }

                $verb = strtolower($http);

                // Use operation transformers to build the operation
                $operation = null;
                $schemaConverter = \Closure::fromCallable([$this, 'schemaFromType']);
                
                foreach ($this->operationTransformers as $transformer) {
                    $operation = $transformer->transform(
                        $engine,
                        $fqcn,
                        $methodName,
                        $meta['return'],
                        $meta['params'],
                        $schemaConverter
                    );
                    if ($operation !== null) {
                        break;
                    }
                }

                // Fallback to default operation building
                if ($operation === null) {
                    $responses = [
                        '200' => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => $this->schemaFromType($meta['return']),
                                ],
                            ],
                        ],
                    ];

                    // Build parameters
                    $params = [];
                    foreach ($meta['params'] as $p) {
                        $paramType = $p['type'];
                        $paramName = is_string($p['name']) ? $p['name'] : (string) $p['name'];

                        // Check if param should be path parameter
                        $isPathParam = str_contains($path, '{' . $paramName . '}');

                        $params[] = [
                            'name' => $paramName,
                            'in' => $isPathParam ? 'path' : 'query',
                            'required' => $isPathParam,
                            'schema' => $this->schemaFromType($paramType),
                        ];
                    }

                    $operation = [
                        'operationId' => $fqcn . '::' . $methodName,
                        'summary' => $this->generateSummary($controllerBase, $methodName),
                        'responses' => $responses,
                    ];

                    if ($params !== []) {
                        $operation['parameters'] = $params;
                    }
                }

                // Process #[Throws] attributes and add exception responses
                $throwsAttr = $meta['throws'] ?? null;
                if ($throwsAttr !== null) {
                    $exceptionClasses = is_array($throwsAttr) ? $throwsAttr : [$throwsAttr];
                    foreach ($exceptionClasses as $exceptionClass) {
                        foreach ($this->exceptionToResponseExtensions as $ext) {
                            if ($ext->supports($exceptionClass)) {
                                $exceptionResponses = $ext->convert($exceptionClass);
                                foreach ($exceptionResponses as $statusCode => $responseSpec) {
                                    $operation['responses'][$statusCode] = $responseSpec;
                                }
                            }
                        }
                    }
                }

                // Process security attributes
                if (isset($meta['allowGuest']) && $meta['allowGuest'] === true) {
                    // Explicitly mark as public (no security required)
                    $operation['security'] = [];
                } elseif (isset($meta['auth'])) {
                    // Add security requirement based on guard
                    $guard = $meta['auth'];
                    $securityScheme = $guard === 'default' ? 'bearerAuth' : $guard . 'Auth';
                    $operation['security'] = [[$securityScheme => []]];
                }

                // Process permissions
                if (isset($meta['permissions']) && is_array($meta['permissions'])) {
                    $operation['x-permissions'] = $meta['permissions'];
                }

                $paths[$path][$verb] = $operation;
            }
        }

        return $paths;
    }

    private function generateSummary(string $resource, string $method): string
    {
        $summaries = [
            'index' => "List all {$resource}",
            'show' => "Get a specific {$resource}",
            'store' => "Create a new {$resource}",
            'update' => "Update a {$resource}",
            'patch' => "Partially update a {$resource}",
            'delete' => "Delete a {$resource}",
            'destroy' => "Delete a {$resource}",
        ];

        if (isset($summaries[$method])) {
            return $summaries[$method];
        }

        // Convert method name to human-readable
        $words = preg_split('/(?=[A-Z])/', $method);
        if ($words === false) {
            $words = [$method];
        }
        $words = array_filter($words, static fn ($w) => $w !== '');
        return ucfirst(strtolower(implode(' ', $words)));
    }

    private function schemaFromNamedObject(NamedObjectType $obj): array
    {
        $props = [];
        $required = [];

        foreach ($obj->properties as $p) {
            $props[$p->name] = $this->schemaFromType($p->type);

            // property required if not nullable
            if (! $p->type instanceof \Lens\Types\Nullable) {
                $required[] = $p->name;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $props,
        ];

        if ($required !== []) {
            $schema['required'] = array_values($required);
        }

        return $schema;
    }

    private function schemaFromType(\Lens\Types\Type $type): array
    {
        foreach ($this->typeToSchemaExtensions as $ext) {
            if ($ext->supports($type)) {
                return $ext->convert($type);
            }
        }

        // Fallback
        return [];
    }
}
