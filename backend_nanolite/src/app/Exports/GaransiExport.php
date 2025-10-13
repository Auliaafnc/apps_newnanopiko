<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GaransiExport implements FromArray, WithStyles
{
    protected $garansi;
    protected int $startDataRow = 3;
    protected int $totalRows;

    public function __construct($garansi)
    {
        $this->garansi = $garansi;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }


    public function array(): array
    {
        $garansi = $this->garansi;
        $products = $garansi->productsWithDetails();

        $rows = [
            ['', '', '', '', '', '', '', '', '', 'GARANSI', '', '', '', ''],
            [
                'No.',
                'No Garansi',
                'Tanggal Dibuat',
                'Tanggal Diupdate',
                'Department',
                'Karyawan',
                'Customer',
                'Kategori Customer',
                'Phone',
                'Alamat',
                'Tanggal Pembelian',
                'Tanggal Klaim',
                'Item Description',
                'Pcs',
                'Alasan Klaim',
                'Catatan',
                'Status',
            ],
        ];

        $no = 1;
        $firstAmountShown = false;
        $this->totalRows = count($products);

        foreach ($products as $item) {
            $desc = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} ({$item['color']})";

            $rows[] = [
                $no++,
                $this->dashIfEmpty($this->garansi->no_garansi),
                $this->dashIfEmpty(optional($this->garansi->created_at)->format('Y-m-d H:i')),
                $this->dashIfEmpty(optional($this->garansi->updated_at)->format('Y-m-d H:i')),
                $this->dashIfEmpty($this->garansi->department->name ?? null),
                $this->dashIfEmpty($this->garansi->employee->name ?? null),
                $this->dashIfEmpty($this->garansi->customer->name ?? null),
                $this->dashIfEmpty($this->garansi->customerCategory->name ?? null),
                $this->dashIfEmpty($this->garansi->phone ?? '-'),
                $this->dashIfEmpty(is_array($this->garansi->address) 
                    ? ($this->garansi->address['alamat'] ?? '-') 
                    : ($this->garansi->address ?? '-')),
                $this->dashIfEmpty(optional($this->garansi->purchase_date)->format('Y-m-d H:i')),
                $this->dashIfEmpty(optional($this->garansi->claim_date)->format('Y-m-d H:i')),
                $this->dashIfEmpty($desc),
                $this->dashIfEmpty($item['quantity']),
                $this->dashIfEmpty($this->garansi->reason ?? '-'),
                $this->dashIfEmpty($this->garansi->note ?? '-'),
                $this->dashIfEmpty($this->garansi->status ?? '-'),
            ];
        }

        // Baris kosong
        $rows[] = array_fill(0, 16, '');

       

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $sheet->getStyle('A2:Q2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $dataEnd = $this->startDataRow + $this->totalRows - 1;

        // Border isi
        foreach (range($this->startDataRow, $dataEnd) as $row) {
            foreach (range('A', 'Q') as $col) {
                if (!empty($sheet->getCell("{$col}{$row}")->getValue()) || $col === 'K') {
                    $sheet->getStyle("{$col}{$row}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }
            }
        }

        
        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
