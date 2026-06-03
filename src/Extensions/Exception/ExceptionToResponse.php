<?php

declare(strict_types=1);

namespace Lens\Extensions\Exception;

/**
 * Extension point for mapping exceptions to OpenAPI responses.
 * 
 * Implementations define which exceptions they support and how to convert
 * them to OpenAPI response specifications.
 */
interface ExceptionToResponse
{
    /**
     * Check if this extension supports the given exception class.
     * 
     * @param string $exceptionClass Fully qualified exception class name
     * @return bool
     */
    public function supports(string $exceptionClass): bool;
    
    /**
     * Convert exception to OpenAPI response specification.
     * 
     * @param string $exceptionClass Fully qualified exception class name
     * @return array<string, array{description: string, content?: array}>
     */
    public function convert(string $exceptionClass): array;
}
