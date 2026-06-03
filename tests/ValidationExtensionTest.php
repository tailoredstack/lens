<?php

declare(strict_types=1);

namespace Tests;

use Lens\Extensions\Validation\DefaultValidationRuleToConstraint;

test('DefaultValidationRuleToConstraint supports MinLength rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\MinLength'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports MaxLength rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\MaxLength'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Min rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Min'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Max rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Max'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Email rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Email'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Regex rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Regex'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Enum rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Enum'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint supports Between rule', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Between'))->toBeTrue();
});

test('DefaultValidationRuleToConstraint does not support unknown rules', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Unknown\Rule'))->toBeFalse();
});

test('DefaultValidationRuleToConstraint returns empty array for unsupported rules', function () {
    $extension = new DefaultValidationRuleToConstraint();
    
    // Create a mock rule object
    $rule = new class {
        public string $name = 'test';
    };
    
    // Should return empty array for unsupported rule
    $result = $extension->convert($rule);
    expect($result)->toBe([]);
});
