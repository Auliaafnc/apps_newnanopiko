<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGaransiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $garansi = $this->route('garansi');

        $isStatusRequest = $this->has('status_pengajuan')
            || $this->has('status_product')
            || $this->has('status_garansi');

        if (! $isStatusRequest) {
            return true;
        }

        return (bool) $this->user()?->can('updateStatus', $garansi);
    }

    public function rules(): array
    {
        return [
            // --- field utama ---
            'company_id'             => 'sometimes|exists:companies,id',
            'customer_categories_id' => 'sometimes|exists:customer_categories,id',
            'employee_id'            => 'sometimes|exists:employees,id',
            'department_id'          => 'sometimes|exists:departments,id',
            'customer_id'            => 'sometimes|exists:customers,id',

            'address'                => 'sometimes|array',
            'phone'                  => 'sometimes|string|max:50',

            'products'               => 'sometimes|array',
            'products.*.brand_id'    => 'sometimes|integer|exists:brands,id',
            'products.*.kategori_id' => 'sometimes|integer|exists:categories,id',
            'products.*.produk_id'   => 'sometimes|integer|exists:products,id',
            'products.*.warna_id'    => 'sometimes|nullable',
            'products.*.quantity'    => 'sometimes|numeric|min:1',

            // tanggal dari Flutter
            'purchase_date'          => 'sometimes|date_format:Y-m-d',
            'claim_date'             => 'sometimes|date_format:Y-m-d|after_or_equal:purchase_date',

            'reason'                 => 'sometimes|nullable|string',
            'note'                   => 'sometimes|nullable|string',

            // JSON gambar (path lama)
            'image'                  => 'sometimes|nullable|array',
            'image.*'                => 'string',

            // JALUR BARU: images[] (dataURL) untuk menambah gambar utama
            'images'                 => 'sometimes|array',
            'images.*'               => ['string','regex:/^data:image\/(png|jpe?g|webp);base64,/i'],

            // --- status (selaras migration) ---
            'status_pengajuan'       => ['sometimes', Rule::in(['pending','approved','rejected'])],
            'status_product'         => ['sometimes', Rule::in(['pending','ready_stock','sold_out','rejected'])],
            'status_garansi'         => ['sometimes', Rule::in(['pending','confirmed','processing','on_hold','delivered','completed','cancelled','rejected'])],

            // --- komentar & audit ---
            'rejection_comment'      => ['sometimes','nullable','string','min:5'],
            'on_hold_comment'        => ['sometimes','nullable','string','min:5'],
            'on_hold_until'          => ['sometimes','nullable','date','after:now'],
            'cancelled_comment'      => ['sometimes','nullable','string','min:5'],

            'rejected_by'            => ['sometimes','nullable','exists:employees,id'],
            'on_hold_by'             => ['sometimes','nullable','exists:employees,id'],
            'cancelled_by'           => ['sometimes','nullable','exists:employees,id'],

            // sold out (jika kolom ada)
            'sold_out_comment'       => ['sometimes','nullable','string','min:5'],
            'sold_out_by'            => ['sometimes','nullable','exists:employees,id'],

            // --- delivery ---
            'delivery_images'        => ['sometimes','nullable','array'],
            // boleh path lama ATAU dataURL (akan diproses di handler)
            'delivery_images.*'      => ['string'],
            'delivered_at'           => ['sometimes','nullable','date'],
            'delivered_by'           => ['sometimes','nullable','exists:employees,id'],

            // file opsional (path string)
            'garansi_file'           => ['sometimes','nullable','string'],
            'garansi_excel'          => ['sometimes','nullable','string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $user    = $this->user();
            $garansi = $this->route('garansi');
            $sp      = $this->input('status_pengajuan');
            $sg      = $this->input('status_garansi');
            $sprod   = $this->input('status_product');

            if ($this->has('status_pengajuan') && $sp === 'rejected' && blank($this->input('rejection_comment'))) {
                $v->errors()->add('rejection_comment', 'Komentar wajib diisi saat status pengajuan = rejected.');
            }
            if ($this->has('status_garansi') && $sg === 'on_hold' && blank($this->input('on_hold_comment'))) {
                $v->errors()->add('on_hold_comment', 'Komentar wajib diisi saat status garansi = on_hold.');
            }
            if ($this->has('status_garansi') && $sg === 'cancelled' && blank($this->input('cancelled_comment'))) {
                $v->errors()->add('cancelled_comment', 'Komentar wajib diisi saat status garansi = cancelled.');
            }
            if ($this->has('status_product') && $sprod === 'sold_out' && blank($this->input('sold_out_comment'))) {
                $v->errors()->add('sold_out_comment', 'Komentar wajib diisi saat status produk = sold out.');
            }

            if (
                ($this->has('status_pengajuan') && $sp === 'rejected') ||
                ($this->has('status_garansi') && in_array($sg, ['on_hold','cancelled','completed'], true)) ||
                ($this->has('status_product') && in_array($sprod, ['sold_out','rejected'], true))
            ) {
                if (! $user?->can('updateStatus', $garansi)) {
                    if ($this->has('status_pengajuan')) $v->errors()->add('status_pengajuan', 'Anda tidak berhak mengubah status pengajuan.');
                    if ($this->has('status_garansi'))   $v->errors()->add('status_garansi', 'Anda tidak berhak mengubah status garansi.');
                    if ($this->has('status_product'))   $v->errors()->add('status_product', 'Anda tidak berhak mengubah status produk.');
                }
            }

            if ($this->has('status_garansi') && $sg === 'completed' && $garansi) {
                $hasConfirm = (bool) $garansi->delivered_at;
                $hasProof   = is_array($garansi->delivery_images) && count($garansi->delivery_images) > 0;
                if (! $hasConfirm || ! $hasProof) {
                    $v->errors()->add('status_garansi', 'Tidak bisa set completed tanpa konfirmasi & bukti delivery.');
                }
            }
        });
    }
}
