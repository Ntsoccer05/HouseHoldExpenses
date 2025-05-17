<?php

namespace App\Filament\Resources\ExpenceCategoryResource\Pages;

use App\Filament\Resources\ExpenceCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenceCategories extends ListRecords
{
    protected static string $resource = ExpenceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
