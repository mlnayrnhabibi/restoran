<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Details')
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('customer_name')
                            ->default('Walk-in Customer'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(4),

                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity'),
                                TextEntry::make('price')
                                    ->money('IDR'),
                                TextEntry::make('subtotal')
                                    ->money('IDR')
                                    ->weight('bold'),
                            ])
                            ->columns(4),
                    ]),

                Section::make('Summary')
                    ->schema([
                        TextEntry::make('total_amount')
                            ->label('Grand Total')
                            ->money('IDR')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->notes)),
                    ])
                    ->columns(2),
            ]);
    }
}
