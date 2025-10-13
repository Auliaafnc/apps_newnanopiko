<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerExport implements FromArray, WithStyles
{
    protected array $filters;

    public function __construct($filters = [])
    {
        $this->filters = is_array($filters) ? $filters : $filters->toArray();
    }


    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    public function array(): array
    {
        $query = Customer::with(['employee', 'customerCategory', 'customerProgram']);

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
        if (!empty($this->filters['status_pengajuan'])) {
            $query->where('status_pengajuan', $this->filters['status_pengajuan']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        $customers = $query
            ->orderBy('employee_id')
            ->orderBy('customer_categories_id')
            ->orderBy('name')
            ->get();

        $rows = [
            ['', '', '', '', '', '', '', '', 'DATA CUSTOMER', '', '', '', '', ''],
            [
                'No.',
                'Department',
                'Karyawan',
                'Nama',
                'Kategori Customer',
                'Telepon',
                'Email',
                'Alamat',
                'Link Google Maps',
                'Program',
                'Program Point',
                'Reward Point',
                'Status Pengajuan',
                'Status',
                'Dibuat Pada',
                'Diupdate Pada',
            ],
        ];

        $no = 1;
        foreach ($customers as $cust) {
            $fullAddress = '-';
            if (is_array($cust->address)) {
                $fullAddress = collect($cust->address)->map(function ($addr) {
                    return implode(', ', [
                        $addr['detail_alamat'] ?? '-',
                        optional(\Laravolt\Indonesia\Models\Kelurahan::where('code', $addr['kelurahan'] ?? null)->first())->name ?? '-',
                        optional(\Laravolt\Indonesia\Models\Kecamatan::where('code', $addr['kecamatan'] ?? null)->first())->name ?? '-',
                        optional(\Laravolt\Indonesia\Models\Kabupaten::where('code', $addr['kota_kab'] ?? null)->first())->name ?? '-',
                        optional(\Laravolt\Indonesia\Models\Provinsi::where('code', $addr['provinsi'] ?? null)->first())->name ?? '-',
                        $addr['kode_pos'] ?? '-',
                    ]);
                })->implode("\n");
            }

            $rows[] = [
                $no++,
                $this->dashIfEmpty($cust->department->name ?? '-'),
                $this->dashIfEmpty($cust->employee->name ?? '-'),
                $this->dashIfEmpty($cust->name),
                $this->dashIfEmpty($cust->customerCategory->name ?? '-'),
                $this->dashIfEmpty($cust->phone),
                $this->dashIfEmpty($cust->email),
                $this->dashIfEmpty($fullAddress),
                $this->dashIfEmpty($cust->gmaps_link),
                $this->dashIfEmpty($cust->customerProgram->name ?? '-'),
                $cust->jumlah_program ?? '-',
                $cust->reward_point ?? '-',
                ucfirst($cust->status_pengajuan ?? '-'),
                ucfirst($cust->status ?? '-'),
                optional($cust->created_at)->format('Y-m-d H:i'),
                optional($cust->updated_at)->format('Y-m-d H:i'),
            ];
        }

        $rows[] = array_fill(0, 14, '');

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('I1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:P2')->applyFromArray([
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

        $lastRow = $sheet->getHighestRow();
        foreach (range(3, $lastRow) as $row) {
            foreach (range('A', 'P') as $col) {
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

        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
