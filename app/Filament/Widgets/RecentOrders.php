<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('customer_name')
                    ->default('Walk-in')
                    ->searchable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn (Order $record) => route('filament.admin.resources.orders.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated(false);
    }
}
