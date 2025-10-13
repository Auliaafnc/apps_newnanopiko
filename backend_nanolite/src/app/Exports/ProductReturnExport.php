<?php

namespace App\Exports;

use App\Models\ProductReturn;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductReturnExport implements FromArray, WithStyles
{
    protected ProductReturn $return;
    protected int $startDataRow = 3;
    protected int $totalRows;

    public function __construct(ProductReturn $return)
    {
        $this->return = $return;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    public function array(): array
    {
        $rows = [
            ['', '', '', '', '', '', '', '', '', 'PRODUCT RETURN', '', '', ''],
            [
                'No.', 'No Return', 'Dibuat Pada', 'Diupdate Pada', 'Department', 'Karyawan', 'Customer', 'Kategori Customer',
                'Phone', 'Alamat', 'Item Description', 'Pcs', 'Alasan Return', 'Catatan', 'Status',
            ],
        ];

        $no = 1;
        $firstAmountShown = false;
        $products = $this->return->productsWithDetails();
        $this->totalRows = count($products);

        foreach ($products as $index => $item) {
            $desc = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} ({$item['color']})";
            $qty = $item['quantity'] ?? 0;

            $rows[] = [
                 $no++,
                $this->dashIfEmpty($this->return->no_return),
                $this->dashIfEmpty(optional($this->return->created_at)->format('Y-m-d H:i')),
                $this->dashIfEmpty(optional($this->return->updated_at)->format('Y-m-d H:i')),
                $this->dashIfEmpty($this->return->department->name ?? null),
                $this->dashIfEmpty($this->return->employee->name ?? null),
                $this->dashIfEmpty($this->return->customer->name ?? null),
                $this->dashIfEmpty($this->return->category->name ?? null),
                $this->dashIfEmpty($this->return->phone ?? '-'),
                $this->dashIfEmpty(is_array($this->return->address) 
                    ? ($this->return->address['alamat'] ?? '-') 
                    : ($this->return->address ?? '-')),
                $this->dashIfEmpty($desc),
                $this->dashIfEmpty($qty),
                $this->dashIfEmpty($this->return->reason ?? '-'),
                $this->dashIfEmpty($this->return->note ?? '-'),
                $this->dashIfEmpty($this->return->status ?? '-'),
            ];

            $firstAmountShown = true;
        }

        // Baris kosong
        $rows[] = array_fill(0, 15, '');
        $rows[] = array_fill(0, 15, '');

        // Baris Total Return
        $rows[] = array_merge(
            array_fill(0, 13, ''),
            ['Total Return:', $this->return->amount > 0 ? 'Rp ' . number_format($this->return->amount, 0, ',', '.') : '-']
        );



        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('J1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $sheet->getStyle('A2:O2')->applyFromArray([
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

        $highestRow = $sheet->getHighestRow();
        $dataEnd = $this->startDataRow + $this->totalRows - 1;

        // Border isi
        foreach (range($this->startDataRow, $dataEnd) as $row) {
            foreach (range('A', 'O') as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        foreach (range($dataEnd + 3, $highestRow) as $row) {
            $sheet->getStyle("O{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $sheet->getStyle("N{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
        }
        

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
