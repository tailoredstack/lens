<?php

declare(strict_types=1);

namespace Tempest\OpenApi\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for tests that do NOT need a full Tempest boot.
 *
 * For integration tests requiring the Tempest kernel, use:
 *   Tempest\Framework\Testing\IntegrationTest
 * directly instead.
 */
class TestCase extends PHPUnitTestCase
{
}
