<?php

namespace App\Exports;

use App\Models\Garansi;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FilteredGaransiExport implements FromArray, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = Garansi::with([
            'customer.customerCategory',
            'employee',
        ]);

        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }
        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }
        if (!empty($this->filters['customer_categories_id'])) {
            $query->where('customer_categories_id', $this->filters['customer_categories_id']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $garansis = $query->get();

        // Filter manual berdasarkan brand, kategori, produk
        if (!empty($this->filters['brand_id'])) {
            $garansis = $garansis->filter(function ($garansi) {
                foreach ($garansi->productsWithDetails() as $item) {
                    if (($item['brand_id'] ?? null) == $this->filters['brand_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        if (!empty($this->filters['category_id'])) {
            $garansis = $garansis->filter(function ($garansi) {
                foreach ($garansi->productsWithDetails() as $item) {
                    if (($item['category_id'] ?? null) == $this->filters['category_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        if (!empty($this->filters['product_id'])) {
            $garansis = $garansis->filter(function ($garansi) {
                foreach ($garansi->productsWithDetails() as $item) {
                    if (($item['product_id'] ?? null) == $this->filters['product_id']) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Header dan judul
        $rows = [
            ['', '', '', '', '', '', '', '', '', 'GARANSI', '', '', '', '', ''],
            [
                'No.', 'No Garansi', 'Tanggal Dibuat', 'Tanggal Diupdate', 'Department', 'Karyawan', 'Customer', 'Kategori Customer',
                'Phone', 'Alamat', 'Tanggal Pembelian', 'Tanggal Klaim', 'Item Description', 'Pcs', 'Alasan Klaim',
                'Catatan', 'Status'
            ],
        ];

        $no = 1;
        foreach ($garansis as $garansi) {
            $itemDetails = $garansi->productsWithDetails();
            $desc = collect($itemDetails)->map(function ($item) {
                return "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} – {$item['color']}";
            })->implode("\n");

            $itemCount = collect($itemDetails)->sum('quantity');

            $rows[] = [
                $no++,
                    $garansi->no_garansi ?? '-',
                    optional($garansi->created_at)->format('Y-m-d H:i'),
                    optional($garansi->updated_at)->format('Y-m-d H:i'),
                    $garansi->department->name ?? '-',
                    $garansi->employee->name ?? '-',
                    $garansi->customer->name ?? '-',
                    $garansi->customerCategory->name ?? '-',
                    $garansi->phone ?? '-',
                    is_array($garansi->address) ? implode(', ', $garansi->address) : $garansi->address,
                    optional($garansi->purchase_date)->format('Y-m-d'),
                    optional($garansi->claim_date)->format('Y-m-d'),
                    $desc,
                    $itemCount,
                    $garansi->reason ?? '-',
                    $garansi->note ?? '-',
                    ucfirst($garansi->status),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:Q2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $highestRow = $sheet->getHighestRow();
        foreach (range(3, $highestRow) as $row) {
            foreach (range('A', 'Q') as $col) {
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

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }


        return [];
    }
}
