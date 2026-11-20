<?php

use App\Support\Money;

test('money parses decimals and performs exact integer-kobo arithmetic without floating point drift', function () {
    $a = Money::fromDecimal('12500000.10');
    $b = Money::fromDecimal('0.20');

    expect($a->add($b)->toDecimal())->toBe('12500000.30')
        ->and($a->add($b)->format())->toBe('₦12,500,000.30')
        ->and(Money::fromDecimal('₦1,500,250.5')->toDecimal())->toBe('1500250.50')
        ->and(Money::fromDecimal('100.005')->toDecimal())->toBe('100.01');
});

test('money calculates exact percentages with half-up rounding', function () {
    $salePrice = Money::fromDecimal('15500000.00');

    expect($salePrice->percentage('5')->toDecimal())->toBe('775000.00')
        ->and($salePrice->percentage('7.5')->toDecimal())->toBe('1162500.00')
        ->and(Money::fromDecimal('100.01')->percentage('5')->toDecimal())->toBe('5.00')
        ->and(Money::fromDecimal('100.10')->percentage('5')->toDecimal())->toBe('5.01');
});

test('money handles negative values and rejects malformed input', function () {
    $loss = Money::fromDecimal('500000.00')->subtract(Money::fromDecimal('750000.25'));

    expect($loss->isNegative())->toBeTrue()
        ->and($loss->toDecimal())->toBe('-250000.25')
        ->and($loss->format())->toBe('-₦250,000.25');

    expect(fn () => Money::fromDecimal('not-a-number'))
        ->toThrow(InvalidArgumentException::class);
});
