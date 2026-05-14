<?php

namespace App\Export;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected ?string $dateFrom,
        protected ?string $dateTo
    ) {}

    public function query()
    {
        return Order::query()
            ->where('status', 'completed')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Customer',
            'Items',
            'Total',
            'Status',
            'Date',
        ];
    }

    public function map($order): array
    {
        return [
            $order->invoice_number,
            $order->customer_name ?? 'Walk-in',
            $order->items->count(),
            $order->total_amount,
            ucfirst($order->status),
            $order->created_at->format('d M Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
