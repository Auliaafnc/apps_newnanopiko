<?php

namespace App\Filament\Admin\Resources\OrderResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // izinkan semua request (bisa diubah via policy)
    }

    public function rules(): array
    {
        return [
            /* ================= RELASI ================= */
            'company_id'             => ['sometimes', 'integer', 'exists:companies,id'],
            'department_id'          => ['sometimes', 'integer', 'exists:departments,id'],
            'employee_id'            => ['sometimes', 'integer', 'exists:employees,id'],
            'customer_id'            => ['sometimes', 'integer', 'exists:customers,id'],
            'customer_categories_id' => ['sometimes', 'integer', 'exists:customer_categories,id'],
            'customer_program_id'    => ['sometimes', 'integer', 'exists:customer_programs,id'],

            /* ================= PRODUK ================= */
            'products'               => ['sometimes', 'array', 'min:1'],
            'products.*.produk_id'   => ['required_with:products', 'integer', 'exists:products,id'],
            'products.*.warna_id'    => ['nullable', 'string'],
            'products.*.quantity'    => ['required_with:products', 'integer', 'min:1'],
            'products.*.price'       => ['required_with:products', 'numeric', 'min:0'],

            /* ================= DISKON ================= */
            'diskon_1'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penjelasan_diskon_1'    => ['nullable', 'string'],
            'diskon_2'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penjelasan_diskon_2'    => ['nullable', 'string'],
            'diskons_enabled'        => ['boolean'],

            /* ========== PROGRAM & REWARD POINT ========= */
            'program_enabled'        => ['boolean'],
            'jumlah_program'         => ['nullable', 'integer'],
            'reward_enabled'         => ['boolean'],
            'reward_point'           => ['nullable', 'integer'],

            /* ================= TOTAL HARGA ============= */
            'total_harga'            => ['sometimes', 'numeric', 'min:0'],
            'total_harga_after_tax'  => ['nullable', 'numeric', 'min:0'],

            /* ================= ALAMAT ================== */
            'address'                       => ['nullable', 'array'],
            'address.*.detail_alamat'       => ['sometimes', 'string'],
            'address.*.kelurahan'           => ['sometimes', 'string'],
            'address.*.kecamatan'           => ['sometimes', 'string'],
            'address.*.kota_kab'            => ['sometimes', 'string'],
            'address.*.provinsi'            => ['sometimes', 'string'],
            'address.*.kode_pos'            => ['sometimes', 'string'],

            /* ================= STATUS & PEMBAYARAN ===== */
            'payment_method'        => ['nullable', 'string', 'in:cash,transfer,tempo'],
            'status_pembayaran'     => ['nullable', 'string', 'in:sudah bayar,belum bayar'],
            'status'                => ['sometimes', 'string', 'in:pending,processing,completed,cancelled'],
        ];
    }
}
