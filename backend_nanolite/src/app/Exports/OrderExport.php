<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class OrderExport implements FromArray, WithStyles, WithEvents
{
    protected Order $order;

    /** @var array<int, string> */
    protected array $imagePaths = [];

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    protected function parseImagePaths($images): array
    {
        if (is_string($images) && str_starts_with($images, '[')) {
            $images = json_decode($images, true);
        }
        $arr = [];
        if (is_array($images)) $arr = $images;
        elseif (is_string($images) && $images !== '') $arr = [$images];

        $paths = [];
        foreach ($arr as $p) {
            $p = preg_replace('#^/?storage/#', '', $p);
            $abs = storage_path('app/public/' . ltrim($p, '/'));
            if (is_file($abs)) $paths[] = $abs;
            if (count($paths) >= 3) break;
        }
        return $paths;
    }

    public function array(): array
    {
        $headers = [
            'No.','No Order','Tanggal Dibuat','Tanggal Diupdate','Department','Karyawan','Customer','Kategori Customer',
            'Customer Program','Phone','Alamat','Item Description','Pcs','Unit Price','Total Awal',
            'Program Point','Reward Point','Disc%','Penjelasan Diskon','Metode Pembayaran','Jatuh Tempo',
            'Status Pembayaran','Status Pengajuan','Status Produk','Status Order','Batas Hold','Alasan Hold','Bukti Pengiriman'
        ];

        $rows = [
            array_pad([], max(1, count($headers) - 12), ''),
            $headers,
        ];
        $rows[0][(int) floor(count($headers) / 2)] = 'SALES ORDER';

        $this->imagePaths = $this->parseImagePaths($this->order->delivery_images);

        $no = 1;
        $diskons = collect([
            (float) ($this->order->diskon_1 ?? 0),
            (float) ($this->order->diskon_2 ?? 0),
            (float) ($this->order->diskon_3 ?? 0),
            (float) ($this->order->diskon_4 ?? 0),
        ]);
        $diskonGabungan = $diskons->filter(fn ($v) => $v > 0)->map(fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . '%')->implode(' + ') ?: '0%';

        $penjelasanDiskon = collect([
            trim($this->order->penjelasan_diskon_1 ?? ''),
            trim($this->order->penjelasan_diskon_2 ?? ''),
            trim($this->order->penjelasan_diskon_3 ?? ''),
            trim($this->order->penjelasan_diskon_4 ?? ''),
        ])->filter()->implode(' + ');

        $subTotal = 0;
        foreach ($this->order->productsWithDetails() as $item) {
            $desc      = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} {$item['color']}";
            $qty       = (int) ($item['quantity'] ?? 0);
            $harga     = (int) ($item['price'] ?? 0);
            $totalAwal = $qty * $harga;
            $subTotal += $totalAwal;

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
                $this->dashIfEmpty(is_array($this->order->address) ? ($this->order->address['detail_alamat'] ?? null) : ($this->order->address ?? null)),
                $this->dashIfEmpty($desc),
                $this->dashIfEmpty($qty),
                $this->dashIfEmpty($harga),
                $this->dashIfEmpty($totalAwal),

                $this->dashIfEmpty($this->order->jumlah_program),
                $this->dashIfEmpty($this->order->reward_point),
                $this->dashIfEmpty($diskonGabungan),
                $this->dashIfEmpty($penjelasanDiskon ?: '-'),
                $this->dashIfEmpty($this->order->payment_method),
                $this->dashIfEmpty(optional($this->order->payment_due_until)?->format('Y-m-d')),
                $this->dashIfEmpty(ucfirst($this->order->status_pembayaran ?? '')),
                $this->dashIfEmpty(match ($this->order->status_pengajuan) {
                    'pending'  => 'Pending',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default    => ucfirst((string) $this->order->status_pengajuan),
                }),
                $this->dashIfEmpty(match ($this->order->status_product) {
                    'pending'     => 'Pending',
                    'ready_stock' => 'Ready Stock',
                    'sold_out'    => 'Sold Out',
                    'rejected'    => 'Ditolak',
                    default       => ucfirst((string) $this->order->status_product),
                }),
                $this->dashIfEmpty(match ($this->order->status_order) {
                    'pending'    => 'Pending',
                    'confirmed'  => 'Confirmed',
                    'processing' => 'Processing',
                    'on_hold'    => 'On Hold',
                    'delivered'  => 'Delivered',
                    'completed'  => 'Completed',
                    'cancelled'  => 'Cancelled',
                    'rejected'   => 'Ditolak',
                    default      => ucfirst((string) $this->order->status_order),
                }),
                $this->dashIfEmpty(optional($this->order->on_hold_until)?->format('Y-m-d')),
                $this->dashIfEmpty($this->order->on_hold_comment ?: '-'),
                empty($this->imagePaths) ? '-' : '',
            ];
        }

        // total akhir dari diskon bertingkat
        $after = (float) $subTotal;
        if ($this->order->diskons_enabled) {
            foreach ($diskons as $d) {
                $d = max(0, min(100, (float) $d));
                if ($d > 0) $after -= $after * ($d / 100);
            }
        }
        $totalAfterDiscount = (int) round($after);
        $discountAmount = max(0, $subTotal - $totalAfterDiscount);

        $cols = count($headers);
        $rows[] = array_fill(0, $cols, '');
        $rows[] = array_fill(0, $cols, '');
        $rows[] = array_merge(array_fill(0, $cols - 2, ''), ['Sub Total:', 'Rp ' . number_format($subTotal, 0, ',', '.')]);
        $rows[] = array_merge(array_fill(0, $cols - 2, ''), ['Discount:',  $discountAmount > 0 ? 'Rp ' . number_format($discountAmount, 0, ',', '.') : '-']);
        $rows[] = array_merge(array_fill(0, $cols - 2, ''), ['Total Akhir:', 'Rp ' . number_format($totalAfterDiscount, 0, ',', '.')]);

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastColIndex = Coordinate::columnIndexFromString($lastCol);

                // baris data mulai baris 3 s.d. 3 + (jumlah item produk) - 1
                $startRow = 3;
                $dataRows = max(0, count($this->order->productsWithDetails()));
                $endRow = $startRow + $dataRows - 1;

                if ($dataRows === 0) {
                    // tak ada baris data, tidak ada gambar
                    return;
                }

                $imgCol = Coordinate::stringFromColumnIndex($lastColIndex); // kolom "Bukti Pengiriman"
                // set lebar kolom & tinggi baris (untuk semua baris item)
                $sheet->getColumnDimension($imgCol)->setWidth(40);
                for ($row = $startRow; $row <= $endRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(65);
                }

                if (!empty($this->imagePaths)) {
                    $offsetX = 5;
                    foreach (array_slice($this->imagePaths, 0, 3) as $path) {
                        $drawing = new Drawing();
                        $drawing->setPath($path);
                        $drawing->setWorksheet($sheet);
                        $drawing->setCoordinates($imgCol . $startRow);
                        $drawing->setOffsetX($offsetX);
                        $drawing->setOffsetY(3);
                        $drawing->setHeight(55);
                        $offsetX += 60;
                    }
                } else {
                    // kalau tak ada gambar, isi '-' di baris pertama
                    $sheet->setCellValue($imgCol . $startRow, '-');
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
{
    // Hitung kolom terakhir (sesuai jumlah header yang dibuat di array())
    $lastCol = $sheet->getHighestColumn();

    // Merge seluruh baris pertama lalu isi judul
    $sheet->mergeCells("A1:{$lastCol}1");
    $sheet->setCellValue('A1', 'SALES ORDER');

    // Style judul: bold, ukuran 14, rata tengah (horizontal & vertical)
    $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
    ]);

    // Header (baris ke-2)
    $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
        'font'      => ['bold' => true],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'wrapText'   => true,
        ],
        'fill'      => [
            'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'F0F0F0'],
        ],
        'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
    ]);

    // Border + wrap seluruh data mulai baris 3
    $highestRow = $sheet->getHighestRow();
    $lastColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);
    for ($row = 3; $row <= $highestRow; $row++) {
        for ($i = 1; $i <= $lastColIndex; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getStyle("{$col}{$row}")->applyFromArray([
                'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    'wrapText'   => true,
                ],
            ]);
        }
    }

    // Autosize semua kolom kecuali kolom gambar (kolom terakhir yang sudah fixed di AfterSheet)
    for ($i = 1; $i <= $lastColIndex - 1; $i++) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    return [];
}

}
