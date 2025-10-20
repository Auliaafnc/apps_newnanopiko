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

class FilteredOrdersExport implements FromArray, WithStyles, WithEvents
{
    protected array $filters;

    /** @var array<int, array<int, string>> Map: rowIndex => [imgPath1, imgPath2, ...] */
    protected array $imageMap = [];

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /** parse images field menjadi array absolute path lokal (maks 3, hanya yang exist) */
    protected function parseImagePaths($images): array
    {
        if (is_string($images) && str_starts_with($images, '[')) {
            $images = json_decode($images, true);
        }
        $arr = [];
        if (is_array($images)) {
            $arr = $images;
        } elseif (is_string($images) && $images !== '') {
            $arr = [$images];
        }

        $paths = [];
        foreach ($arr as $p) {
            // buang prefix 'storage/' jika ada, lalu rute ke storage_path('app/public/...').
            $p = preg_replace('#^/?storage/#', '', $p);
            $abs = storage_path('app/public/' . ltrim($p, '/'));
            if (is_file($abs)) {
                $paths[] = $abs;
            }
            if (count($paths) >= 3) break; // batasi 3 gambar
        }
        return $paths;
    }

    protected function applyManualFilters($orders)
    {
        if (!empty($this->filters['brand_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $i) {
                    if (($i['brand_id'] ?? null) == $this->filters['brand_id']) return true;
                }
                return false;
            });
        }
        if (!empty($this->filters['category_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $i) {
                    if (($i['category_id'] ?? null) == $this->filters['category_id']) return true;
                }
                return false;
            });
        }
        if (!empty($this->filters['product_id'])) {
            $orders = $orders->filter(function ($order) {
                foreach ($order->productsWithDetails() as $i) {
                    if (($i['product_id'] ?? null) == $this->filters['product_id']) return true;
                }
                return false;
            });
        }
        return $orders;
    }

    public function array(): array
    {
        $q = Order::with(['customer.customerCategory', 'employee', 'customerProgram', 'department'])
            ->orderBy('created_at', 'asc'); // urutkan lama -> baru

        // filter sesuai Resource
        if (!empty($this->filters['customer_id']))            $q->where('customer_id', $this->filters['customer_id']);
        if (!empty($this->filters['department_id']))          $q->where('department_id', $this->filters['department_id']);
        if (!empty($this->filters['employee_id']))            $q->where('employee_id', $this->filters['employee_id']);
        if (!empty($this->filters['customer_categories_id'])) $q->where('customer_categories_id', $this->filters['customer_categories_id']);
        if (!empty($this->filters['customer_program_id']))    $q->where('customer_program_id', $this->filters['customer_program_id']);
        if (!empty($this->filters['payment_method']))         $q->where('payment_method', $this->filters['payment_method']);
        if (!empty($this->filters['status_pembayaran']))      $q->where('status_pembayaran', $this->filters['status_pembayaran']);
        if (!empty($this->filters['status_order']))           $q->where('status_order', $this->filters['status_order']);
        if (!empty($this->filters['status_product']))         $q->where('status_product', $this->filters['status_product']);
        if (!empty($this->filters['status_pengajuan']))        $q->where('status_pengajuan', $this->filters['status_pengajuan']);
        if (isset($this->filters['has_diskon']))              $q->where('diskons_enabled', $this->filters['has_diskon'] === 'ya');
        if (isset($this->filters['has_program_point']))       $q->where('program_enabled', $this->filters['has_program_point'] === 'ya');
        if (isset($this->filters['has_reward_point']))        $q->where('reward_enabled', $this->filters['has_reward_point'] === 'ya');

        $orders = $this->applyManualFilters($q->get());

        $headers = [
            'No.', 'No Order', 'Tanggal Dibuat', 'Tanggal Diupdate', 'Department', 'Karyawan', 'Customer', 'Kategori Customer',
            'Customer Program', 'Phone', 'Alamat', 'Item Description', 'Pcs', 'Unit Price', 'Total Awal',
            'Program Point', 'Reward Point', 'Disc%', 'Penjelasan Diskon', 'Total Akhir',
            'Metode Pembayaran', 'Jatuh Tempo', 'Status Pembayaran',
            'Status Pengajuan', 'Status Produk', 'Status Order', 'Batas Hold', 'Alasan Hold', 'Bukti Pengiriman',
        ];

        $rows = [
            array_pad([], max(1, count($headers) - 17), ''), // spacer row
            $headers,
        ];
        $rows[0][(int) floor(count($headers) / 2)] = 'SALES ORDER';

        $no = 1;
        $startRow = 3; // data mulai baris ke-3
        $currentRow = $startRow;

        foreach ($orders as $order) {
            $diskons = collect([
                (float) ($order->diskon_1 ?? 0),
                (float) ($order->diskon_2 ?? 0),
                (float) ($order->diskon_3 ?? 0),
                (float) ($order->diskon_4 ?? 0),
            ]);
            $diskonGabungan = $diskons->filter(fn ($v) => $v > 0)->map(fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . '%')->implode(' + ') ?: '0%';

            $penjelasanDiskon = collect([
                trim($order->penjelasan_diskon_1 ?? ''),
                trim($order->penjelasan_diskon_2 ?? ''),
                trim($order->penjelasan_diskon_3 ?? ''),
                trim($order->penjelasan_diskon_4 ?? ''),
            ])->filter()->implode(' + ');

            $deskripsiProduk = [];
            $hargaProduk = [];
            $totalPcs = 0;
            $totalAwal = 0;
            foreach ($order->productsWithDetails() as $item) {
                $desc      = "{$item['brand_name']} – {$item['category_name']} – {$item['product_name']} – {$item['color']}";
                $qty       = (int) ($item['quantity'] ?? 0);
                $harga     = (int) ($item['price'] ?? 0);
                $awal      = $qty * $harga;

                $totalPcs  += $qty;
                $totalAwal += $awal;

                $deskripsiProduk[] = "$desc";
                $hargaProduk[]     = "Rp " . number_format($harga, 0, ',', '.');
            }

            $totalAkhir = (int) ($order->total_harga_after_tax ?? 0);
            if (!$totalAkhir) {
                $after = (float) $totalAwal;
                if ($order->diskons_enabled) {
                    foreach ($diskons as $d) {
                        $d = max(0, min(100, (float) $d));
                        if ($d > 0) $after -= $after * ($d / 100);
                    }
                }
                $totalAkhir = (int) round($after);
            }

            // simpan paths gambar untuk AfterSheet
            $this->imageMap[$currentRow] = $this->parseImagePaths($order->delivery_images);

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
                'Rp ' . number_format($totalAwal, 0, ',', '.'),
                (string) ($order->jumlah_program ?? '-'),
                (string) ($order->reward_point ?? '-'),
                $diskonGabungan,
                $penjelasanDiskon ?: '-',
                'Rp ' . number_format($totalAkhir, 0, ',', '.'),
                ucfirst($order->payment_method ?? '-'),
                optional($order->payment_due_until)->format('Y-m-d') ?? '-',
                ucfirst($order->status_pembayaran ?? '-'),
                match ($order->status_pengajuan) {
                    'pending'  => 'Pending',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default    => ucfirst((string) $order->status_pengajuan),
                },
                match ($order->status_product) {
                    'pending'     => 'Pending',
                    'ready_stock' => 'Ready Stock',
                    'sold_out'    => 'Sold Out',
                    'rejected'    => 'Ditolak',
                    default       => ucfirst((string) $order->status_product),
                },
                match ($order->status_order) {
                    'pending'    => 'Pending',
                    'confirmed'  => 'Confirmed',
                    'processing' => 'Processing',
                    'on_hold'    => 'On Hold',
                    'delivered'  => 'Delivered',
                    'completed'  => 'Completed',
                    'cancelled'  => 'Cancelled',
                    'rejected'   => 'Ditolak',
                    default      => ucfirst((string) $order->status_order),
                },
                optional($order->on_hold_until)->format('Y-m-d') ?? '-',
                $order->on_hold_comment ?: '-',
                // kolom bukti pengiriman: kita isi placeholder '-' (gambar akan ditanam AfterSheet)
                empty($this->imageMap[$currentRow]) ? '-' : '',
            ];

            $currentRow++;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastColIndex = Coordinate::columnIndexFromString($lastCol);

                // Kolom gambar = kolom terakhir (Bukti Pengiriman)
                $imgColIndex = $lastColIndex;
                $imgCol = Coordinate::stringFromColumnIndex($imgColIndex);

                // Set lebar kolom gambar & tinggi baris default untuk nampung thumbnail
                $sheet->getColumnDimension($imgCol)->setWidth(40); // lebar kolom gambar
                foreach ($this->imageMap as $row => $paths) {
                    if (empty($paths)) {
                        // isi '-' jika kosong
                        $sheet->setCellValue($imgCol.$row, '-');
                        continue;
                    }

                    // row height agar muat 60px
                    $sheet->getRowDimension($row)->setRowHeight(65);

                    // Tanam maksimal 3 gambar, disusun horizontal pakai offsetX
                    $offsetX = 5;
                    foreach (array_slice($paths, 0, 3) as $path) {
                        $drawing = new Drawing();
                        $drawing->setPath($path);
                        $drawing->setWorksheet($sheet);
                        $drawing->setCoordinates($imgCol . $row);
                        $drawing->setOffsetX($offsetX);
                        $drawing->setOffsetY(3);
                        $drawing->setHeight(55); // tinggi thumbnail
                        $offsetX += 60; // jarak antar thumbnail
                    }
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
