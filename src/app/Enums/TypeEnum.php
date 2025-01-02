<?php

namespace App\Enums;

enum TypeEnum: int
{
    case INCOME = 1;
    case EXPENSE = 2;

    public function label(): ?string
    {
        return match ($this) {
            self::INCOME => 'income',
            self::EXPENSE => 'expense',
            default => null,
        };
    }

    public static function fromLabel(string $label): ?self
    {
        return match ($label) {
            'income' => self::INCOME,
            'expense' => self::EXPENSE,
            default => null,
        };
    }
}