<?php

declare(strict_types=1);

namespace Lens;

use Tempest\Core\Kernel;
use function Tempest\app_path;
use function Tempest\root_path;

/**
 * Lens configuration loader.
 * 
 * Loads lens.config.php from app/ or root directory.
 */
final class LensConfigLoader
{
    public function load(Kernel $kernel): void
    {
        // Try app/ directory first, then root
        $configPath = null;
        if (file_exists(app_path('lens.config.php'))) {
            $configPath = app_path('lens.config.php');
        } elseif (file_exists(root_path('lens.config.php'))) {
            $configPath = root_path('lens.config.php');
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
