<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryExport implements FromArray, WithStyles
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
        $query = Category::with(['brand', 'products']);

        if (!empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        $categories = $query
            ->orderBy('brand_id')
            ->orderBy('status')
            ->orderBy('name')
            ->get();

        $rows = [
            ['', '', '', '', 'DATA KATEGORI', '', '', '', '', ''],
            [
                'No.',
                'Brand',
                'Nama Kategori',
                'Deskripsi',
                'Jumlah Pengguna',
                'Status',
                'Created At',
                'Updated At',
            ],
        ];

        $no = 1;
        foreach ($categories as $category) {
            $rows[] = [
                $no++,
                $this->dashIfEmpty($category->brand->name ?? '-'),
                $this->dashIfEmpty($category->name),
                $this->dashIfEmpty($category->deskripsi),
                $category->products->count() ?: '-',
                $this->dashIfEmpty(ucfirst($category->status)),
                optional($category->created_at)->format('Y-m-d H:i'),
                optional($category->updated_at)->format('Y-m-d H:i'),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:H2')->applyFromArray([
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
            foreach (range('A', 'H') as $col) {
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

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
