<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        // Today's stats
        $todayRevenue = Order::completed()->whereDate('created_at', today())->sum('total_amount');
        $todayOrders = Order::whereDate('created_at', today())->count();

        // Month stats
        $monthRevenue = Order::completed()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // Yesterday comparison
        $yesterdayRevenue = Order::completed()->whereDate('created_at', today()->subDay())->sum('total_amount');
        $revenueChange = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : 0;

        // Low stock alert
        $lowStock = Product::where('is_active', true)
            ->where('stock', '<=', 10)
            ->where('stock', '>', 0)
            ->count();

        $outOfStock = Product::where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        return [
            Stat::make('Today\'s Revenue', 'Rp ' . number_format($todayRevenue, 0, ',', '.'))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% from yesterday" : "{$revenueChange}% from yesterday")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([7, 4, 6, 8, 5, 9, $todayOrders > 0 ? 10 : 3]),

            Stat::make('Today\'s Orders', $todayOrders)
                ->description('Transactions today')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make('Monthly Revenue', 'Rp ' . number_format($monthRevenue, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Stock Alerts', $lowStock + $outOfStock)
                ->description("{$lowStock} low, {$outOfStock} out")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outOfStock > 0 ? 'danger' : ($lowStock > 0 ? 'warning' : 'success')),
        ];
    }
}
