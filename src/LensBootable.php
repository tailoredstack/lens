<?php

declare(strict_types=1);

namespace Lens;

use Lens\Config\LensConfig;
use Tempest\Container\Container;
use Tempest\Core\Bootable;

use function Tempest\app_path;
use function Tempest\root_path;

/**
 * Lens bootable class.
 *
 * Loads configuration when the Tempest application boots.
 * Priority: 1) Config file (app/lens.config.php), 2) Environment variables
 */
final class LensBootable implements Bootable
{
    public function boot(Container $container): void
    {
        $config = $this->loadConfig();

        if ($config !== null) {
            $container->config($config);
        }
    }

    private function loadConfig(): ?LensConfig
    {
        // Try to load from config file first (user customization)
        $configPath = null;

        if (function_exists('Tempest\app_path') && file_exists(\Tempest\app_path('lens.config.php'))) {
            $configPath = \Tempest\app_path('lens.config.php');
        } elseif (function_exists('Tempest\root_path') && file_exists(\Tempest\root_path('lens.config.php'))) {
            $configPath = \Tempest\root_path('lens.config.php');
        }

        if ($configPath !== null) {
            $config = require $configPath;

            if ($config instanceof LensConfig) {
                return $config;
            }
        }

        // Fallback to environment variables
        return LensConfig::fromEnv();
    }
}
