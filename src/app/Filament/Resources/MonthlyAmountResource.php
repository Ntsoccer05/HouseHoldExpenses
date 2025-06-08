<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthlyAmountResource\Pages;
use App\Filament\Resources\MonthlyAmountResource\RelationManagers;
use App\Models\MonthlyAmount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MonthlyAmountResource extends Resource
{
    protected static ?string $model = MonthlyAmount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required(),
                Forms\Components\TextInput::make('amount')->numeric()->required(),
                Forms\Components\DateTimePicker::make('recorded_at')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('amount')->sortable(),
                Tables\Columns\TextColumn::make('recorded_at')->sortable()->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->sortable()->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlyAmounts::route('/'),
            'create' => Pages\CreateMonthlyAmount::route('/create'),
            'edit' => Pages\EditMonthlyAmount::route('/{record}/edit'),
        ];
    }
}