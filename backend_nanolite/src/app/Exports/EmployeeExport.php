<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromArray, WithStyles
{
    protected array $filters = [];
    protected ?Collection $data = null;

    public function __construct($input = [])
    {
        // Kalau input adalah Collection (dari model)
        if ($input instanceof Collection) {
            $this->data = $input;
        }
        // Kalau input adalah array filter (dari resource)
        elseif (is_array($input)) {
            $this->filters = $input;
        }
    }

    protected function dashIfEmpty($value): string
    {
        return (is_null($value) || trim((string) $value) === '') ? '-' : (string) $value;
    }

    public function array(): array
    {
        // Ambil data dari Collection langsung, atau pakai filter
        if ($this->data) {
            $employees = $this->data;
        } else {
            $query = Employee::with('department');

            if (!empty($this->filters['department_id'])) {
                $query->where('department_id', $this->filters['department_id']);
            }

            if (!empty($this->filters['status'])) {
                $query->where('employees.status', $this->filters['status']); // ✅ Fix ambiguity
            }


            $employees = $query
                ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
                ->orderBy('departments.name')
                ->orderBy('employees.status') 
                ->select('employees.*')
                ->get();


        }

        $rows = [
            ['', '', '', '', '', 'DATA KARYAWAN', '', '', '', '', ''],
            [
                'No.', 'Nama', 'Email', 'Telepon', 'Departemen', 'Alamat', 'Status',
                'Dibuat Pada', 'Diupdate Pada'
            ],
        ];

        $no = 1;
        foreach ($employees as $employee) {
            $rows[] = [
                $no++,
                $this->dashIfEmpty($employee->name),
                $this->dashIfEmpty($employee->email),
                $this->dashIfEmpty($employee->phone),
                $this->dashIfEmpty(optional($employee->department)->name),
                $this->dashIfEmpty(strip_tags($employee->full_address)),
                ucfirst($this->dashIfEmpty($employee->status)),
                optional($employee->created_at)->format('Y-m-d H:i'),
                optional($employee->updated_at)->format('Y-m-d H:i'),
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $highestRow = $sheet->getHighestRow();
        foreach (range(3, $highestRow) as $row) {
            foreach (range('A', 'I') as $col) {
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

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
