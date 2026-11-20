<?php

use App\Enums\CommissionType;
use App\Services\CommissionCalculator;
use App\Support\Money;

test('commission calculator handles fixed, percentage, excess over minimum, and custom modes accurately', function () {
    $calculator = new CommissionCalculator;
    $salePrice = Money::fromDecimal('16000000.00');

    expect($calculator->calculate(CommissionType::Fixed, $salePrice, '750000.00')->toDecimal())
        ->toBe('750000.00')
        ->and($calculator->calculate(CommissionType::Percentage, $salePrice, '5.5')->toDecimal())
        ->toBe('880000.00')
        ->and($calculator->calculate(CommissionType::ExcessOverMinimum, $salePrice, '0', '14500000.00')->toDecimal())
        ->toBe('1500000.00')
        ->and($calculator->calculate(CommissionType::ExcessOverMinimum, Money::fromDecimal('14000000.00'), '0', '14500000.00')->toDecimal())
        ->toBe('0.00')
        ->and($calculator->calculate(CommissionType::Custom, $salePrice, '0', null, Money::fromDecimal('625000.50'))->toDecimal())
        ->toBe('625000.50');
});
