<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PayrollAmounts
{
    public static function kobo(string $amount): int
    {
        if (! preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Enter a non-negative naira amount with at most two decimal places.']);
        }

        $parts = explode('.', $amount);

        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function calculate(string $basic, array $allowances, array $deductions): array
    {
        $convert = fn (array $items) => array_map(fn (array $item) => [
            'label' => $item['label'],
            'kobo' => self::kobo((string) $item['amount']),
        ], $items);
        $allowances = $convert($allowances);
        $deductions = $convert($deductions);
        $basicKobo = self::kobo($basic);
        $grossKobo = $basicKobo + array_sum(array_column($allowances, 'kobo'));
        $deductionsKobo = array_sum(array_column($deductions, 'kobo'));

        if ($deductionsKobo > $grossKobo) {
            throw ValidationException::withMessages(['deductions' => 'Deductions cannot exceed gross pay.']);
        }

        return [
            'basic_kobo' => $basicKobo,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross_kobo' => $grossKobo,
            'deductions_kobo' => $deductionsKobo,
            'net_kobo' => $grossKobo - $deductionsKobo,
        ];
    }
}
