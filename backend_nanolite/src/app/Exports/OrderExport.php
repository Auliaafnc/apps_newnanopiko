<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderExport implements FromArray, WithStyles
{
    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }


    public function array(): array
    {
        $rows = [
            ['', '', '', '', '', '', '', '', '', '', '', 'SALES ORDER', '', '', '','', '', '','',''],
            [
                'No.',
                'No Order',
                'Tanggal Dibuat',
                'Tanggal Diupdate',
                'Department',
                'Karyawan',
                'Customer',
                'Kategori Customer',
                'Customer Program',
                'Phone',
                'Alamat',
                'Item Description',
                'Pcs',
                'Unit Price',
                'Total Awal',
                'Program Point',
                'Reward Point',
                'Disc%',
                'Penjelasan Diskon',
                'Metode Pembayaran',
                'Status Pembayaran',
                'Status',
            ],
        ];

        $no = 1;
        $diskon1 = (float) $this->order->diskon_1;
        $diskon2 = (float) $this->order->diskon_2;
        $diskonGabungan = collect([$diskon1, $diskon2])
            ->filter(fn($v) => $v > 0)
            ->map(fn($v) => "{$v}%")
            ->implode(' + ') ?: '0%';

        $penjelasanDiskon = collect([
            trim($this->order->penjelasan_diskon_1 ?? '-'),
            trim($this->order->penjelasan_diskon_2 ?? '-')
        ])
            ->filter()
            ->implode(' + ');

        $subTotal = 0;
        $totalAfterDiscount = 0;

        foreach ($this->order->productsWithDetails() as $item) {
            $desc = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} {$item['color']}";
            $qty = (int) $item['quantity'];
            $harga = (int) $item['price'];
            $totalAwal = $qty * $harga;

            $afterFirst = $totalAwal * (1 - ($diskon1 / 100));
            $afterSecond = $afterFirst * (1 - ($diskon2 / 100));
            $amount = (int) round($afterSecond);

            $subTotal += $totalAwal;
            $totalAfterDiscount += $amount;

            $rows[] = [
                $no++,
                $this->dashIfEmpty($this->order->no_order),
                $this->dashIfEmpty(optional($this->order->created_at)->format('Y-m-d H:i')),
                $this->dashIfEmpty(optional($this->order->updated_at)->format('Y-m-d H:i')),
                 $this->dashIfEmpty($this->order->department->name ?? null),
                $this->dashIfEmpty($this->order->employee->name ?? null),
                $this->dashIfEmpty($this->order->customer->name ?? null),
                $this->dashIfEmpty($this->order->customerCategory->name ?? null),
                $this->dashIfEmpty(optional($this->order->customer?->customerProgram)->name ?? 'Tidak Ikut Program'),
                $this->dashIfEmpty($this->order->phone ?? null),
                $this->dashIfEmpty(is_array($this->order->address)
                    ? ($this->order->address['detail_alamat'] ?? null)
                    : ($this->order->address ?? null)),
                $this->dashIfEmpty($desc),
                $this->dashIfEmpty($qty),
                $this->dashIfEmpty($harga),
                $this->dashIfEmpty($totalAwal),
                $this->dashIfEmpty($this->order->jumlah_program),
                $this->dashIfEmpty($this->order->reward_point),
                $this->dashIfEmpty($diskonGabungan),
                $this->dashIfEmpty($penjelasanDiskon),
                $this->dashIfEmpty($this->order->payment_method),
                $this->dashIfEmpty(ucfirst($this->order->status_pembayaran ?? '')),
                $this->dashIfEmpty(ucfirst($this->order->status ?? '')),
            ];
        }


        $discountAmount = $subTotal - $totalAfterDiscount;


        $rows[] = array_fill(0, 22, '');
        $rows[] = array_fill(0, 22, '');

        $rows[] = array_merge(
            array_fill(0, 20, ''),
            ['Sub Total:', 'Rp ' . number_format($subTotal, 0, ',', '.')]
        );

        $rows[] = array_merge(
            array_fill(0, 20, ''),
            ['Discount:', $discountAmount > 0 ? 'Rp ' . number_format($discountAmount, 0, ',', '.') : '-']
        );

        $rows[] = array_merge(
            array_fill(0, 20, ''),
            ['Total Akhir:', 'Rp ' . number_format($totalAfterDiscount, 0, ',', '.')]
        );


        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('L1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:V2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $highestRow = $sheet->getHighestRow();
        $dataEndRow = $highestRow - 5;

        foreach (range(3, $dataEndRow) as $row) {
            foreach (range('A', 'V') as $col) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        foreach (range($dataEndRow + 3, $highestRow) as $row) {
            $sheet->getStyle("V{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $sheet->getStyle("U{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
        }

        foreach (range('A', 'V') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
