<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenceCategoryResource\Pages;
use App\Filament\Resources\ExpenceCategoryResource\RelationManagers;
use App\Models\ExpenceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenceCategoryResource extends Resource
{
    protected static ?string $model = ExpenceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->required(),
                Forms\Components\Select::make('type_id')
                    ->relationship('type', 'name')
                    ->required(),
                Forms\Components\TextInput::make('filtered_id')->numeric()->nullable(),
                Forms\Components\TextInput::make('content')->required()->maxLength(255),
                Forms\Components\TextInput::make('icon')->maxLength(255),
                Forms\Components\Toggle::make('deleted')->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('type.name')->label('Type')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('filtered_id')->sortable(),
                Tables\Columns\TextColumn::make('content')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('icon')->sortable(),
                Tables\Columns\BooleanColumn::make('deleted'),
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
            'index' => Pages\ListExpenceCategories::route('/'),
            'create' => Pages\CreateExpenceCategory::route('/create'),
            'edit' => Pages\EditExpenceCategory::route('/{record}/edit'),
        ];
    }
}