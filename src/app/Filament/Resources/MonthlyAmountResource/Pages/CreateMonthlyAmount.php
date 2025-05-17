<?php

namespace App\Filament\Resources\MonthlyAmountResource\Pages;

use App\Filament\Resources\MonthlyAmountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMonthlyAmount extends CreateRecord
{
    protected static string $resource = MonthlyAmountResource::class;
}
