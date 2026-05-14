<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Order Information')
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->default(fn () => Order::generateInvoiceNumber())
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(ignoreRecord: true),

                                TextInput::make('customer_name')
                                    ->maxLength(255)
                                    ->placeholder('Walk-in Customer')
                                    ->helperText('Leave empty for walk-in customer'),

                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false),

                                Textarea::make('notes')
                                    ->maxLength(500)
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->placeholder('Additional notes...'),
                            ])
                            ->columns(2),

                        Section::make('Order Items')
                            ->schema([
                                Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Product')
                                            ->options(function () {
                                                return Product::query()
                                                    ->where('is_active', true)
                                                    ->where('stock', '>', 0)
                                                    ->get()
                                                    ->mapWithKeys(fn ($product) => [
                                                        $product->id => "{$product->name} (Stock: {$product->stock}) - {$product->formatted_price}"
                                                    ]);
                                            })
                                            ->required()
                                            ->searchable()
                                            ->native(false)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('price', $product->price);
                                                        $quantity = $get('quantity') ?? 1;
                                                        $set('subtotal', $product->price * $quantity);
                                                    }
                                                }
                                            })
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->columnSpan(3),

                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                $price = $get('price') ?? 0;
                                                $set('subtotal', $price * ($state ?? 1));
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('price')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('subtotal')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),
                                    ])
                                    ->columns(8)
                                    ->addActionLabel('+ Add Item')
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->cloneable()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateTotalAmount($get, $set);
                                    })
                                    ->deleteAction(
                                        fn ($action) => $action->after(fn (Get $get, Set $set) =>
                                            self::updateTotalAmount($get, $set)
                                        )
                                    )
                                    ->itemLabel(fn (array $state): ?string =>
                                        $state['product_id']
                                            ? Product::find($state['product_id'])?->name
                                            : null
                                    ),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // Right Column - Summary
                Group::make()
                    ->schema([
                        Section::make('Order Summary')
                            ->schema([
                                TextEntry::make('items_count')
                                    ->label('Total Items')
                                    ->state(function (Get $get): string {
                                        $items = $get('items') ?? [];
                                        $totalQty = collect($items)->sum('quantity');
                                        return $totalQty . ' item(s)';
                                    }),

                                TextEntry::make('total_display')
                                    ->label('Grand Total')
                                    ->state(function (Get $get): string {
                                        $items = $get('items') ?? [];
                                        $total = collect($items)->sum('subtotal');
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),

                                Hidden::make('total_amount')
                                    ->default(0),
                            ]),

                        Section::make('Quick Info')
                            ->schema([
                                TextEntry::make('created_info')
                                    ->label('Created')
                                    ->state(fn (?Order $record): string =>
                                        $record ? $record->created_at->format('d M Y H:i') : 'New Order'
                                    )
                                    ->visible(fn (?Order $record): bool => $record !== null),
                            ])
                            ->visible(fn (?Order $record): bool => $record !== null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
    
    public static function updateTotalAmount(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $total = collect($items)->sum('subtotal');
        $set('total_amount', $total);
    }

}
