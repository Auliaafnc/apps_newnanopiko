<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
 
class ProductExport implements FromArray, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    public function array(): array
    {
        $query = Product::with(['brand', 'category']);

        if (!empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }
        if (!empty($this->filters['status'])) {
                $query->where('status', $this->filters['status']);
        }

        $products = $query
            ->orderBy('brand_id')
            ->orderBy('category_id')
            ->orderBy('status')
            ->orderBy('name')
            ->get();



        $rows = [
            ['', '', '', '', '', '', 'DATA PRODUK', '', '', '', '', '', ''],
            [
                'No.', 'Brand', 'Kategori', 'Nama Produk', 'Warna', 'Harga', 'Deskripsi', 'Status', 'Created At', 'Updated At',
            ],
        ];

        $no = 1;
        foreach ($products as $product) {
            $rows[] = [
                $no++,
                $this->dashIfEmpty($product->brand->name ?? '-'),
                $this->dashIfEmpty($product->category->name ?? '-'),
                $this->dashIfEmpty($product->name),
                is_array($product->colors) ? implode(', ', $product->colors) : '-',
                'Rp ' . number_format($product->price, 0, ',', '.'),
                $this->dashIfEmpty($product->description ?? '-'),
                $this->dashIfEmpty(ucfirst($product->status)),
                optional($product->created_at)->format('Y-m-d H:i'),
                optional($product->updated_at)->format('Y-m-d H:i'),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Judul
        
        $sheet->getStyle('G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $sheet->getStyle('A2:J2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $highestRow = $sheet->getHighestRow();
        foreach (range(3, $highestRow) as $row) {
            foreach (range('A', 'J') as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            }
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
