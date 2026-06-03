<?php

declare(strict_types=1);

namespace Lens;

use Tempest\Container\Container;
use Tempest\Core\Bootable;

/**
 * Lens bootable class.
 * 
 * Loads configuration when the Tempest application boots.
 */
final class LensBootable implements Bootable
{
    public function boot(Container $container): void
    {
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        // Try app/ directory first, then root
        $configPath = null;
        
        if (function_exists('Tempest\app_path') && file_exists(\Tempest\app_path('lens.config.php'))) {
            $configPath = \Tempest\app_path('lens.config.php');
        } elseif (function_exists('Tempest\root_path') && file_exists(\Tempest\root_path('lens.config.php'))) {
            $configPath = \Tempest\root_path('lens.config.php');
        } else {
            // Fallback to package default
            $configPath = __DIR__ . '/../config/lens.config.php';
        }

        if (!file_exists($configPath)) {
            return;
        }

        $config = require $configPath;
        
        // Store in config registry
        config()->set('lens', $config);
    }
}
