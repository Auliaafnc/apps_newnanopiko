<?php

namespace App\Filament\Admin\Resources\CustomerResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'             => 'sometimes|exists:companies,id',
            'customer_categories_id' => 'sometimes|exists:customer_categories,id',
            'department_id'          => 'sometimes|exists:departments,id',
            'employee_id'            => 'sometimes|exists:employees,id',
            'customer_program_id'    => 'sometimes|exists:customer_programs,id',

            'name'   => 'sometimes|string|max:255',
            'phone'  => 'sometimes|string|max:20',
            'email'  => 'nullable|email',

            // ✅ alamat array (pakai code + name biar full sama dengan Create)
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
            'image.*' => 'file|image|max:2048',

            'status'  => 'nullable|string|in:active,inactive,pending',
        ];
    }
}
