<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Export\SalesExport;
use App\Models\Order;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithForms;
   use InteractsWithTable;
    protected string $view = 'filament.pages.sales-report';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Sales Report';

    protected static UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 10;

    /**
     * Only Managers can access the Sales Report.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Manager;
    }

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter')
                    ->schema([
                        DatePicker::make('dateFrom')
                            ->label('From Date')
                            ->default(now()->startOfMonth())
                            ->reactive(),
                        DatePicker::make('dateTo')
                            ->label('To Date')
                            ->default(now())
                            ->reactive(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->where('status', 'completed')
                    ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
                    ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->default('Walk-in'),
                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->summarize(Sum::make()->money('IDR')),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function getSummary(): array
    {
        $query = Order::where('status', 'completed')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total_amount'),
            'average_order' => $query->avg('total_amount') ?? 0,
        ];
    }

    public function export()
    {
        return Excel::download(
            new SalesExport($this->dateFrom, $this->dateTo),
            'sales-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
