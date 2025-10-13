<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BrandExport implements FromArray, WithStyles
{
    protected Collection $brands;

    public function __construct(Collection $brands)
    {
        $this->brands = $brands;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    public function array(): array
    {
        $query = Brand::query();
        $brands = $query
                ->orderBy('status')
                ->get();

        $rows = [
            ['', '', '', '', 'DATA BRAND', '', '', '', ''],
            [
                'No.',
                'Nama Brand',
                'Deskripsi',
                'Jumlah Pengguna di Kategori',
                'Jumlah Pengguna di Produk',
                'Status',
                'Dibuat Pada',
                'Diupdate Pada',
            ],
        ];

        $no = 1;
        foreach ($this->brands as $brand) {
            $rows[] = [
                $no++,
                $this->dashIfEmpty($brand->name),
                $this->dashIfEmpty($brand->deskripsi),
                $brand->categories()->count() ?: '-',
                $brand->products()->count() ?: '-',
                $this->dashIfEmpty(ucfirst($brand->status)),
                optional($brand->created_at)->format('Y-m-d H:i'),
                optional($brand->updated_at)->format('Y-m-d H:i'),
            ];
        }

        $rows[] = array_fill(0, 7, ''); // Spacer

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Judul di baris 1
        $sheet->getStyle('E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header baris 2
        $sheet->getStyle('A2:H2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F0F0F0'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Baris data
        $lastRow = $this->brands->count() + 2;
        foreach (range(3, $lastRow) as $row) {
            foreach (range('A', 'H') as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
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
