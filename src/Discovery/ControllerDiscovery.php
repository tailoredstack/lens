<?php

declare(strict_types=1);

namespace Lens\Discovery;

use Lens\Infer\Engine;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;

/**
 * Discovers Tempest controllers and their route attributes.
 */
final class ControllerDiscovery implements Discovery
{
    /** @var array<string, array> */
    private array $controllers = [];

    public function discover(DiscoveryLocation $location): void
    {
        $path = $location->path;

        if (! is_dir($path)) {
            return;
        }

        $engine = new Engine([$path]);
        $engine->analyze();

        $operations = $engine->getOperations();

        foreach ($operations as $fqcn => $methods) {
            // Only process classes that look like controllers
            if (! $this->isController($fqcn, $methods)) {
                continue;
            }

            $this->controllers[$fqcn] = [
                'class' => $fqcn,
                'methods' => $methods,
                'routes' => $this->extractRoutes($methods),
            ];
        }
    }

    public function initialize(): void
    {
        // Controllers already discovered in discover()
    }

    /**
     * @return array<string, array>
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    private function isController(string $fqcn, array $methods): bool
    {
        // Check if class name ends with "Controller"
        $shortName = substr($fqcn, strrpos($fqcn, '\\') + 1);
        if (str_ends_with($shortName, 'Controller')) {
            return true;
        }

        // Check if any method has route attributes
        foreach ($methods as $method => $meta) {
            if (isset($meta['http']) || isset($meta['path'])) {
                return true;
            }
        }

        return false;
    }

    private function extractRoutes(array $methods): array
    {
        $routes = [];

        foreach ($methods as $name => $meta) {
            $http = $meta['http'] ?? 'get';
            $path = $meta['path'] ?? null;

            if ($path !== null) {
                $routes[] = [
                    'method' => $name,
                    'http' => strtoupper($http),
                    'path' => $path,
                    'params' => $meta['params'] ?? [],
                    'return' => $meta['return'] ?? null,
                ];
            }
        }

        return $routes;
    }
}
