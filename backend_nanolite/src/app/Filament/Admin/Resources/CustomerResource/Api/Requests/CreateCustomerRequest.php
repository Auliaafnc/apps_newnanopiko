<?php

namespace App\Filament\Admin\Resources\CustomerResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // izinkan semua request (bisa diubah pakai policy)
    }

    public function rules(): array
{
    return [
        'company_id'             => 'required|exists:companies,id',
        'customer_categories_id' => 'required|exists:customer_categories,id',
        'department_id'          => 'required|exists:departments,id',
        'employee_id'            => 'nullable|exists:employees,id',
        'customer_program_id'    => 'nullable|exists:customer_programs,id',

        'name'   => 'required|string|max:255',
        'phone'  => 'required|string|max:20',
        'email'  => 'nullable|email',

        // ✅ alamat array (pakai code + name biar full)
        'address'                        => 'nullable|array',
        'address.*.detail_alamat'        => 'sometimes|string',
        'address.*.provinsi_code'        => 'sometimes|string',
        'address.*.provinsi_name'        => 'sometimes|string',
        'address.*.kota_kab_code'        => 'sometimes|string',
        'address.*.kota_kab_name'        => 'sometimes|string',
        'address.*.kecamatan_code'       => 'sometimes|string',
        'address.*.kecamatan_name'       => 'sometimes|string',
        'address.*.kelurahan_code'       => 'sometimes|string',
        'address.*.kelurahan_name'       => 'sometimes|string',
        'address.*.kode_pos'             => 'sometimes|string',

        'gmaps_link'  => 'nullable|string',
        'jumlah_program' => 'nullable|integer',
        'reward_point'   => 'nullable|integer',

        // ✅ multi foto
        'image'   => 'nullable',
        'image.*' => 'file|image|max:2048',  // lebih besar dari sebelumnya biar aman

        'status'  => 'nullable|string|in:active,inactive,pending',
    ];
}

}
