<?php

declare(strict_types=1);

namespace Lens\Extensions\Validation;

/**
 * Default validation rule to OpenAPI constraint mapper.
 * Handles common Tempest validation rules.
 */
final class DefaultValidationRuleToConstraint implements ValidationRuleToConstraint
{
    /**
     * @param string $ruleClass
     */
    public function supports(string $ruleClass): bool
    {
        // Support common Tempest validation rules
        $supported = [
            'Tempest\Validation\Rules\MinLength',
            'Tempest\Validation\Rules\MaxLength',
            'Tempest\Validation\Rules\Min',
            'Tempest\Validation\Rules\Max',
            'Tempest\Validation\Rules\Email',
            'Tempest\Validation\Rules\Regex',
            'Tempest\Validation\Rules\Enum',
            'Tempest\Validation\Rules\Between',
        ];

        return in_array($ruleClass, $supported, true);
    }

    /**
     * @param object $rule
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
    public function convert(object $rule): array
    {
        /** @var class-string $ruleClass */
        $ruleClass = get_class($rule);

        return match ($ruleClass) {
            'Tempest\Validation\Rules\MinLength' => [
                'minLength' => $rule->length ?? $rule->min ?? 0,
            ],
            'Tempest\Validation\Rules\MaxLength' => [
                'maxLength' => $rule->length ?? $rule->max ?? PHP_INT_MAX,
            ],
            'Tempest\Validation\Rules\Min' => [
                'minimum' => $rule->min ?? 0,
            ],
            'Tempest\Validation\Rules\Max' => [
                'maximum' => $rule->max ?? PHP_INT_MAX,
            ],
            'Tempest\Validation\Rules\Email' => [
                'format' => 'email',
            ],
            'Tempest\Validation\Rules\Regex' => [
                'pattern' => $rule->pattern ?? '',
            ],
            'Tempest\Validation\Rules\Enum' => [
                'enum' => $this->getEnumValues($rule),
            ],
            'Tempest\Validation\Rules\Between' => [
                'minimum' => $rule->min ?? 0,
                'maximum' => $rule->max ?? PHP_INT_MAX,
            ],
            default => [],
        };
    }

    /**
     * @param object $rule
     * @return array
     */
    private function getEnumValues(object $rule): array
    {
        // Try to extract enum values from the rule
        if (isset($rule->enum)) {
            $enumClass = $rule->enum;
            if (is_string($enumClass) && enum_exists($enumClass)) {
                return array_map(static fn ($case) => $case->value ?? $case->name, $enumClass::cases());
            }
        }

        return [];
    }
}
