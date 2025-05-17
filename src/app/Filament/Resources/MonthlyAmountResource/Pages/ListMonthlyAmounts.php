<?php

namespace App\Filament\Resources\MonthlyAmountResource\Pages;

use App\Filament\Resources\MonthlyAmountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyAmounts extends ListRecords
{
    protected static string $resource = MonthlyAmountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
