<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->square()
                    ->size(50)
                    ->defaultImageUrl(fn () => '<https://via.placeholder.com/50?text=No+Image>'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => $record->category?->name ?? 'No Category'
                    ),

                TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn (int $state): string => match (true) {
                        $state <= 0 => 'heroicon-o-x-circle',
                        $state <= 10 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-check-circle',
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->native(false),

                Filter::make('low_stock')
                    ->label('Low Stock (≤ 10)')
                    ->query(fn ($query) => $query->where('stock', '<=', 10)->where('stock', '>', 0))
                    ->toggle(),

                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn ($query) => $query->where('stock', '<=', 0))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('adjustStock')
                        ->label('Adjust Stock')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('warning')
                        ->schema([
                            Radio::make('type')
                                ->options([
                                    'add' => 'Add Stock',
                                    'subtract' => 'Subtract Stock',
                                    'set' => 'Set Stock',
                                ])
                                ->default('add')
                                ->required()
                                ->inline(),
                            TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0),
                        ])
                        ->action(function (Product $record, array $data) {
                            $quantity = (int) $data['quantity'];

                            match ($data['type']) {
                                'add' => $record->increment('stock', $quantity),
                                'subtract' => $record->decrement('stock', $quantity),
                                'set' => $record->update(['stock' => $quantity]),
                            };
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),

                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped();
    }
}
