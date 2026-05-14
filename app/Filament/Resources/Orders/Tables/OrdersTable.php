<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\UserRole;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Invoice copied!')
                    ->icon('heroicon-o-document-text'),

                TextColumn::make('customer_name')
                    ->default('Walk-in Customer')
                    ->searchable()
                    ->icon('heroicon-o-user'),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending'    => 'heroicon-o-clock',
                        'processing' => 'heroicon-o-fire',
                        'completed'  => 'heroicon-o-check-circle',
                        'cancelled'  => 'heroicon-o-x-circle',
                        default      => 'heroicon-o-question-mark-circle',
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->description(fn (Order $record): string => $record->created_at->diffForHumans()
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ])
                    ->native(false),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From: '.Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until: '.Carbon::parse($data['until'])->format('d M Y');
                        }

                        return $indicators;
                    }),

                Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make()
                        ->visible(fn (Order $record): bool =>
                            $record->status === 'pending' &&
                            in_array(auth()->user()?->role, [UserRole::Manager, UserRole::Cashier])
                        ),

                    // Kitchen: start cooking (pending → processing)
                    Action::make('start_cooking')
                        ->label('Start Cooking')
                        ->icon('heroicon-o-fire')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Start Cooking')
                        ->modalDescription('Mark this order as being prepared by the kitchen?')
                        ->visible(fn (Order $record): bool =>
                            $record->status === 'pending' &&
                            in_array(auth()->user()?->role, [UserRole::Kitchen, UserRole::Manager])
                        )
                        ->action(function (Order $record) {
                            $record->markAsProcessing();

                            Notification::make()
                                ->title('Cooking Started')
                                ->body("Order {$record->invoice_number} is now being prepared.")
                                ->warning()
                                ->send();
                        }),

                    // Kitchen / Manager: mark complete (processing → completed)
                    Action::make('complete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Order')
                        ->modalDescription('Mark this order as completed and ready to serve?')
                        ->visible(fn (Order $record): bool =>
                            $record->status === 'processing' &&
                            in_array(auth()->user()?->role, [UserRole::Kitchen, UserRole::Manager])
                        )
                        ->action(function (Order $record) {
                            $record->markAsCompleted();

                            Notification::make()
                                ->title('Order Completed')
                                ->body("Order {$record->invoice_number} is ready to serve!")
                                ->success()
                                ->send();
                        }),

                    // Manager / Cashier: cancel
                    Action::make('cancel')
                        ->label('Cancel Order')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Order')
                        ->modalDescription('Are you sure? Stock will be restored.')
                        ->visible(fn (Order $record): bool =>
                            in_array($record->status, ['pending', 'processing']) &&
                            in_array(auth()->user()?->role, [UserRole::Manager, UserRole::Cashier])
                        )
                        ->action(function (Order $record) {
                            $record->markAsCancelled();

                            Notification::make()
                                ->title('Order Cancelled')
                                ->body("Order {$record->invoice_number} has been cancelled. Stock restored.")
                                ->warning()
                                ->send();
                        }),

                    Action::make('print')
                        ->label('Print Invoice')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->url(fn (Order $record): string => route('invoice.print', $record))
                        ->openUrlInNewTab()
                        ->visible(false), // Enable when route is created

                    DeleteAction::make()
                        ->visible(fn (Order $record): bool =>
                            $record->status === 'cancelled' &&
                            auth()->user()?->role === UserRole::Manager
                        ),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('complete_all')
                        ->label('Complete Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool =>
                            in_array(auth()->user()?->role, [UserRole::Kitchen, UserRole::Manager])
                        )
                        ->action(fn ($records) => $records->each(
                            fn ($record) => $record->status === 'processing' && $record->markAsCompleted()
                        ))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->poll('30s');
    }
}
