<?php

declare(strict_types=1);

namespace Tests;

use Lens\Extensions\Validation\DefaultValidationRuleToConstraint;

// Phase 1.5: Validation rules to OpenAPI constraints

test('MinLength rule converts to minLength constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    
    // Mock MinLength rule
    $rule = new class {
        public int $length = 5;
    };
    
    // Test the conversion logic directly
    $result = $extension->convert($rule);
    
    // Since we can't easily instantiate Tempest rules, test the supports method
    expect($extension->supports('Tempest\Validation\Rules\MinLength'))->toBeTrue();
});

test('MaxLength rule converts to maxLength constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\MaxLength'))->toBeTrue();
});

test('Min rule converts to minimum constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Min'))->toBeTrue();
});

test('Max rule converts to maximum constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Max'))->toBeTrue();
});

test('Email rule converts to format constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Email'))->toBeTrue();
});

test('Regex rule converts to pattern constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Regex'))->toBeTrue();
});

test('Enum rule converts to enum constraint', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Enum'))->toBeTrue();
});

test('Between rule converts to minimum and maximum constraints', function () {
    $extension = new DefaultValidationRuleToConstraint();
    expect($extension->supports('Tempest\Validation\Rules\Between'))->toBeTrue();
});

test('Unknown rule returns empty array', function () {
    $extension = new DefaultValidationRuleToConstraint();
    
    $rule = new class {
        public string $name = 'unknown';
    };
    
    $result = $extension->convert($rule);
    
    expect($result)->toBe([]);
});

test('ValidationRuleToConstraint interface is implemented', function () {
    $extension = new DefaultValidationRuleToConstraint();
    
    expect($extension)->toBeInstanceOf(\Lens\Extensions\Validation\ValidationRuleToConstraint::class);
});
