<?php

namespace App\Filament\Resources\MonthlyAmountResource\Pages;

use App\Filament\Resources\MonthlyAmountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyAmount extends EditRecord
{
    protected static string $resource = MonthlyAmountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
