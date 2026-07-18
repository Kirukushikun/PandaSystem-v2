<?php

use App\Enums\ActionType;
use App\Enums\ConfidentialityTag;

test('there are exactly 13 action types, matching the mockup selects', function () {
    expect(ActionType::cases())->toHaveCount(13);
});

test('only Regularization auto-finalizes employment status to Regular and carries Leave Credits', function () {
    foreach (ActionType::cases() as $type) {
        $isRegularization = $type === ActionType::Regularization;

        expect($type->autoFinalizesToRegular())->toBe($isRegularization)
            ->and($type->includesLeaveCredits())->toBe($isRegularization);
    }
});

test('only Wage Order carries a wage number', function () {
    foreach (ActionType::cases() as $type) {
        expect($type->requiresWageNumber())->toBe($type === ActionType::WageOrder);
    }
});

test('confidentiality tags are Untagged, Tarlac (routine), and Manila (confidential)', function () {
    expect(array_column(ConfidentialityTag::cases(), 'value'))
        ->toBe(['untagged', 'tarlac', 'manila']);
});
