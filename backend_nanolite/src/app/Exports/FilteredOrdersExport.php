<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FilteredOrdersExport implements FromArray, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = Order::with([
            'customer.customerCategory',
            'employee',
            'customerProgram',
        ]);

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }
        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }
        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }
        if (!empty($this->filters['customer_categories_id'])) {
            $query->where('customer_categories_id', $this->filters['customer_categories_id']);
        }
        if (!empty($this->filters['customer_program_id'])) {
            $query->where('customer_program_id', $this->filters['customer_program_id']);
        }
        if (!empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }
        if (!empty($this->filters['status_pembayaran'])) {
            $query->where('status_pembayaran', $this->filters['status_pembayaran']);
        }

        if (isset($this->filters['has_diskon'])) {
            $query->where('diskons_enabled', $this->filters['has_diskon'] === 'ya');
        }

        if (isset($this->filters['has_program_point'])) {
            $query->where('program_enabled', $this->filters['has_program_point'] === 'ya');
        }

        if (isset($this->filters['has_reward_point'])) {
            $query->where('reward_enabled', $this->filters['has_reward_point'] === 'ya');
        }

        // Ambil data dari database
        $orders = $query->get();

        // Filter manual: brand
        if (!empty($this->filters['brand_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $item) {
                    if (($item['brand_id'] ?? null) == $this->filters['brand_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Filter manual: kategori
        if (!empty($this->filters['category_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $item) {
                    if (($item['category_id'] ?? null) == $this->filters['category_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Filter manual: produk
        if (!empty($this->filters['product_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $item) {
                    if (($item['product_id'] ?? null) == $this->filters['product_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        $rows = [
            ['', '', '', '', '', '', '', '', '', '', '', 'SALES ORDER', '', '', '', '', '', '', '', '', ''],
            [
                'No.', 'No Order', 'Tanggal Dibuat', 'Tanggal Diupdate', 'Department', 'Karyawan', 'Customer', 'Kategori Customer',
                'Customer Program', 'Phone', 'Alamat', 'Item Description', 'Pcs', 'Unit Price', 'Total Awal',
                'Program Point', 'Reward Point', 'Disc%', 'Penjelasan Diskon', 'Total Akhir', 'Metode Pembayaran', 'Status Pembayaran', 'Status'
            ],
        ];

        $no = 1;
        foreach ($orders as $order) {
            $diskon1 = (float) $order->diskon_1;
            $diskon2 = (float) $order->diskon_2;
            $diskonGabungan = collect([$diskon1, $diskon2])
                ->filter(fn($v) => $v > 0)
                ->map(fn($v) => "{$v}%")
                ->implode(' + ') ?: '0%';

            $penjelasanDiskon = collect([
                trim($order->penjelasan_diskon_1 ?? '-'),
                trim($order->penjelasan_diskon_2 ?? '-')
            ])->filter()->implode(' + ');

            $deskripsiProduk = [];
            $hargaProduk = [];
            $totalPcs = 0;
            $totalAwalSemuaProduk = 0;

            foreach ($order->productsWithDetails() as $item) {
                $desc = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} {$item['color']}";
                $qty = (int) $item['quantity'];
                $harga = (int) $item['price'];
                $totalAwal = $qty * $harga;

                $totalPcs += $qty;
                $totalAwalSemuaProduk += $totalAwal;

                $deskripsiProduk[] = "$desc ({$qty} pcs)";
                $hargaProduk[] = "Rp " . number_format($harga, 0, ',', '.') . " x {$qty} = Rp " . number_format($totalAwal, 0, ',', '.');
            }

            $rows[] = [
                $no++,
                $order->no_order ?? '-',
                optional($order->created_at)->format('Y-m-d H:i'),
                optional($order->updated_at)->format('Y-m-d H:i'),
                $order->department->name ?? '-',
                $order->employee->name ?? '-',
                $order->customer->name ?? '-',
                $order->customer->customerCategory->name ?? '-',
                $order->customerProgram->name ?? 'Tidak Ikut Program',
                $order->phone ?? '-',
                $order->address ?? '-',
                implode("\n", $deskripsiProduk),
                $totalPcs,
                implode("\n", $hargaProduk),
                'Rp ' . number_format($totalAwalSemuaProduk, 0, ',', '.'),
                ucfirst($order->jumlah_program ?? '-'),
                ucfirst($order->reward_point ?? '-'),
                $diskonGabungan,
                $penjelasanDiskon,
                'Rp ' . number_format($order->totalAfterDiscount, 0, ',', '.'),
                ucfirst($order->payment_method ?? '-'),
                ucfirst($order->status_pembayaran ?? '-'),
                ucfirst($order->status ?? '-'),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('L1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:W2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $highestRow = $sheet->getHighestRow();

        foreach (range(3, $highestRow) as $row) {
            foreach (range('A', 'W') as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                ]);
            }
        }

        foreach (range('A', 'W') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
