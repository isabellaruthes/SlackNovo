<?php

namespace App\Support;

final class MoneyRules
{
    public static function rules(bool $allowZero = false): array
    {
        return [
            'bail',
            'required',
            'numeric',
            'regex:/\A[0-9]+(?:\.[0-9]{1,2})?\z/',
            $allowZero ? 'min:0' : 'min:0.01',
            'max:99999999.99',
        ];
    }
}
