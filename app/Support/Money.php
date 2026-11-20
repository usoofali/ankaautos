<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Exact decimal money value object operating in 64-bit integer minor units (kobo = NGN x 100)
 * to avoid floating-point rounding errors across all financial calculations (SSD 18.8).
 */
final readonly class Money
{
    private function __construct(
        private int $kobo,
    ) {}

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromKobo(int $kobo): self
    {
        return new self($kobo);
    }

    /**
     * Parse a decimal string, integer, or numeric value into exact kobo without float drift.
     */
    public static function fromDecimal(string|int|float $amount): self
    {
        if (is_int($amount)) {
            return new self($amount * 100);
        }

        $normalized = is_float($amount)
            ? number_format($amount, 2, '.', '')
            : trim(str_replace([',', '₦', ' '], '', $amount));

        if ($normalized === '' || ! preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException("Invalid monetary amount: [{$amount}].");
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        $parts = explode('.', $normalized, 2);
        $whole = (int) $parts[0];
        $fractionRaw = $parts[1] ?? '';

        if (strlen($fractionRaw) <= 2) {
            $fraction = (int) str_pad($fractionRaw, 2, '0');
        } else {
            // Half-up rounding when more than 2 decimal digits are supplied.
            $twoDigits = (int) substr($fractionRaw, 0, 2);
            $thirdDigit = (int) $fractionRaw[2];
            $fraction = $thirdDigit >= 5 ? $twoDigits + 1 : $twoDigits;

            if ($fraction >= 100) {
                $whole++;
                $fraction -= 100;
            }
        }

        $totalKobo = ($whole * 100) + $fraction;

        return new self($negative ? -$totalKobo : $totalKobo);
    }

    public function toKobo(): int
    {
        return $this->kobo;
    }

    public function add(self $other): self
    {
        return new self($this->kobo + $other->kobo);
    }

    public function subtract(self $other): self
    {
        return new self($this->kobo - $other->kobo);
    }

    /**
     * Compute a percentage of this monetary amount with exact half-up rounding to the nearest kobo.
     * Supports percentage rates with up to 4 decimal places (e.g. "7.5", "12.25").
     */
    public function percentage(string|int|float $rate): self
    {
        $normalized = is_float($rate)
            ? rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.')
            : trim((string) $rate);

        if ($normalized === '' || ! preg_match('/^-?\d+(\.\d{1,4})?$/', $normalized)) {
            throw new InvalidArgumentException("Invalid percentage rate: [{$rate}].");
        }

        $negative = str_starts_with($normalized, '-');
        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        $parts = explode('.', $normalized, 2);
        $whole = (int) $parts[0];
        $frac = (int) str_pad($parts[1] ?? '', 4, '0');

        // Scale rate by 10,000 so percentage = kobo * scaledRate / 1,000,000
        $scaledRate = ($whole * 10_000) + $frac;
        if ($negative) {
            $scaledRate = -$scaledRate;
        }

        $numerator = $this->kobo * $scaledRate;
        $divisor = 1_000_000;

        $quotient = intdiv($numerator, $divisor);
        $remainder = abs($numerator % $divisor);

        if ($remainder * 2 >= $divisor) {
            $quotient += $numerator >= 0 ? 1 : -1;
        }

        return new self($quotient);
    }

    public function max(self $other): self
    {
        return $this->kobo >= $other->kobo ? $this : $other;
    }

    public function min(self $other): self
    {
        return $this->kobo <= $other->kobo ? $this : $other;
    }

    public function isZero(): bool
    {
        return $this->kobo === 0;
    }

    public function isPositive(): bool
    {
        return $this->kobo > 0;
    }

    public function isNegative(): bool
    {
        return $this->kobo < 0;
    }

    public function equals(self $other): bool
    {
        return $this->kobo === $other->kobo;
    }

    public function greaterThan(self $other): bool
    {
        return $this->kobo > $other->kobo;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->kobo >= $other->kobo;
    }

    public function lessThan(self $other): bool
    {
        return $this->kobo < $other->kobo;
    }

    public function lessThanOrEqual(self $other): bool
    {
        return $this->kobo <= $other->kobo;
    }

    /**
     * Format as DECIMAL(15,2) database string (e.g. "12500000.00").
     */
    public function toDecimal(): string
    {
        $sign = $this->kobo < 0 ? '-' : '';
        $abs = abs($this->kobo);
        $naira = intdiv($abs, 100);
        $kobo = $abs % 100;

        return sprintf('%s%d.%02d', $sign, $naira, $kobo);
    }

    /**
     * Format for Nigerian Naira display (e.g. "₦12,500,000.00").
     */
    public function format(string $symbol = '₦'): string
    {
        $sign = $this->kobo < 0 ? '-' : '';
        $abs = abs($this->kobo);
        $naira = intdiv($abs, 100);
        $kobo = $abs % 100;

        return sprintf('%s%s%s.%02d', $sign, $symbol, number_format($naira, 0, '.', ','), $kobo);
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }
}
