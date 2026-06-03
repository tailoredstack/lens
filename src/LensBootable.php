<?php

declare(strict_types=1);

namespace Lens;

use Lens\Config\LensConfig;
use Tempest\Container\Container;
use Tempest\Core\Bootable;

/**
 * Lens bootable class.
 * 
 * Loads configuration when the Tempest application boots.
 * Priority: 1) Environment variables, 2) Config file, 3) Defaults
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
        // First, try to load from config file (app/ or root)
        $configPath = null;
        
        if (function_exists('Tempest\app_path') && file_exists(\Tempest\app_path('lens.config.php'))) {
            $configPath = \Tempest\app_path('lens.config.php');
        } elseif (function_exists('Tempest\root_path') && file_exists(\Tempest\root_path('lens.config.php'))) {
            $configPath = \Tempest\root_path('lens.config.php');
        }

        // If config file exists, load it (it may use fromEnv() internally)
        if ($configPath !== null && file_exists($configPath)) {
            $config = require $configPath;
            
            if ($config instanceof LensConfig) {
                return $config;
            }
        }

        // Fallback: load from environment variables directly
        return LensConfig::fromEnv();
    }
}
