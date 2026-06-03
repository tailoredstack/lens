<?php

declare(strict_types=1);

namespace Lens\Extensions\Validation;

/**
 * Maps Tempest validation rules to OpenAPI schema constraints.
 */
interface ValidationRuleToConstraint
{
    /**
     * Check if this extension supports the given validation rule class.
     */
    public function supports(string $ruleClass): bool;

    /**
     * Convert the validation rule to OpenAPI schema constraints.
     *
     * @return array{
     *     minLength?: int,
     *     maxLength?: int,
     *     pattern?: string,
     *     minimum?: int|float,
     *     maximum?: int|float,
     *     format?: string,
     *     enum?: array,
     *     ...
     * }
     */
    public function convert(object $rule): array;
}
