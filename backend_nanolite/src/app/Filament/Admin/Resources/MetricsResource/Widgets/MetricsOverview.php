<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

// models
use App\Models\Order;
use App\Models\Garansi;
use App\Models\ProductReturn;
use App\Models\Customer;
use App\Models\Perbaikandata;

// resources (penting untuk diarahkan)
use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Admin\Resources\GaransiResource;
use App\Filament\Admin\Resources\ProductReturnResource;
use App\Filament\Admin\Resources\CustomerResource;
use App\Filament\Admin\Resources\PerbaikandataResource;

class MetricsOverview extends BaseWidget
{
    protected static ?int $sort = 10;

    protected function getCards(): array
    {
        $pendingOrders = Order::where('status_order', 'pending')->count();
        $pendingGaransi = Garansi::where('status_garansi', 'pending')->count();
        $pendingReturn  = ProductReturn::where('status_return', 'pending')->count();
        $pendingCustomer = Customer::where('status', 'pending')->count();
        $pendingPerbaikandata = Perbaikandata::where('status_pengajuan', 'pending')->count();

        return [
            Card::make('Order Pending', $pendingOrders)
                ->description('Pengajuan order menunggu konfirmasi')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->url(OrderResource::getUrl('index')), // <— klik ke tabel Orders

            Card::make('Garansi Pending', $pendingGaransi)
                ->description('Pengajuan garansi belum diproses')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->url(GaransiResource::getUrl('index')),

            Card::make('Return Pending', $pendingReturn)
                ->description('Pengajuan return belum disetujui')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->url(ProductReturnResource::getUrl('index')),

            Card::make('Customers', $pendingCustomer)
                ->description('Pengajuan customer menunggu disetujui')
                ->icon('heroicon-o-user')
                ->color('info')
                ->url(CustomerResource::getUrl('index')),

            Card::make('Perbaikan Data', $pendingPerbaikandata)
                ->description('Pengajuan perbaikan data menunggu disetujui')
                ->icon('heroicon-o-book-open')
                ->color('warning')
                ->url(PerbaikandataResource::getUrl('index')),
        ];
    }
}
