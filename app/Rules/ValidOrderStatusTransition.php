<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOrderStatusTransition implements ValidationRule
{
    protected $currentStatus;

    public function __construct($currentStatus)
    {
        $this->currentStatus = $currentStatus;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowedTransitions = [
            'cart' => ['pending'],
            'pending' => ['dijemput', 'diproses', 'dibatalkan'],
            'dijemput' => ['diproses', 'dibatalkan'],
            'diproses' => ['selesai'],
            'selesai' => ['diantar'], // Wait, what if there's no delivery? Then it stays in selesai.
            'diantar' => ['selesai'],
            'dibatalkan' => [], // Cannot move out of canceled
        ];

        // If the status isn't changing, it's valid
        if ($this->currentStatus === $value) {
            return;
        }

        $validNextStates = $allowedTransitions[$this->currentStatus] ?? [];

        if (!in_array($value, $validNextStates)) {
            $fail("Transisi status dari '{$this->currentStatus}' ke '{$value}' tidak diizinkan.");
        }
    }
}
