<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use App\Filament\Admin\Resources\GaransiResource;
use App\Filament\Admin\Resources\GaransiResource\Api\Requests\UpdateGaransiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rupadana\ApiService\Http\Handlers;

class UpdateHandler extends Handlers
{
    public static ?string $uri = '/{id}';
    public static ?string $resource = GaransiResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Update Garansi (selaras Filament + hardening)
     */
    public function handler(UpdateGaransiRequest $request)
    {
        $id    = $request->route('id');
        $model = static::getModel()::find($id);

        if (! $model) {
            return static::sendNotFoundResponse();
        }

        $data = $request->validated();
        $user = Auth::user();

        /**
         * 0) Batasi field yang boleh diubah untuk role sales,
         *    sama seperti di Filament (record disabled).
         */
        if ($user->hasAnyRole(['sales','head_sales','head_digital'])) {
            // sales tidak boleh mengubah field2 dasar saat EDIT
            unset(
                $data['department_id'],
                $data['employee_id'],
                $data['customer_categories_id'],
                $data['customer_id'],
                $data['phone'],
                $data['address'],
                $data['products'],
                $data['purchase_date'],
                $data['claim_date'],
                $data['reason'],
                $data['note'],
                $data['image'],
                $data['images'],
            );
            // sales boleh mengunggah delivery_images hanya jika policy/Request mengizinkan
            // (validasi status & policy sudah ditangani di FormRequest)
        }

        /**
         * 1) Address array → string multi-line (selaras Resource)
         */
        if (isset($data['address']) && is_array($data['address'])) {
            $data['address'] = collect($data['address'])->map(function ($i) {
                return implode(', ', array_filter([
                    $i['detail_alamat'] ?? null,
                    $i['kelurahan']     ?? null,
                    $i['kecamatan']     ?? null,
                    $i['kota_kab']      ?? null,
                    $i['provinsi']      ?? null,
                    $i['kode_pos']      ?? null,
                ], fn ($v) => !empty($v) && $v !== '-'));
            })->implode("\n");
        }

        /**
         * 2) images[] (dataURL) → simpan ke storage dan gabung dengan image lama
         *    Kolom DB 'image' bertipe JSON/array (cast di Model).
         */
        if (!empty($data['images']) && is_array($data['images'])) {
            $existing = (array) ($model->image ?? []);
            $stored   = [];

            foreach ($data['images'] as $dataUrl) {
                if (!is_string($dataUrl)) continue;

                if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
                    $ext = strtolower($m[1] ?? 'jpg');
                    if ($ext === 'jpeg') $ext = 'jpg';
                    $base64  = substr($dataUrl, strpos($dataUrl, ',') + 1);
                    $binary  = base64_decode($base64, true);
                    if ($binary === false) continue;

                    $filename = 'garansi-photos/'.Str::uuid()->toString().'.'.$ext;
                    Storage::disk('public')->put($filename, $binary);
                    $stored[] = $filename;
                }
            }

            if ($stored) {
                $data['image'] = array_values(array_unique(array_merge($existing, $stored)));
            }
            unset($data['images']);
        }

        /**
         * 3) delivery_images[] (dataURL) → simpan & gabungkan;
         *    set delivered_at/by otomatis bila ada unggahan baru.
         */
        $addedDelivery = [];
        if (!empty($data['delivery_images']) && is_array($data['delivery_images'])) {
            $existing = (array) ($model->delivery_images ?? []);

            foreach ($data['delivery_images'] as $item) {
                // dukung path string lama ATAU dataURL baru
                if (is_string($item) && str_starts_with($item, 'data:image/')) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $item, $m)) {
                        $ext = strtolower($m[1] ?? 'jpg');
                        if ($ext === 'jpeg') $ext = 'jpg';
                        $base64 = substr($item, strpos($item, ',') + 1);
                        $binary = base64_decode($base64, true);
                        if ($binary === false) continue;

                        $filename = 'garansi-delivery-photos/'.Str::uuid()->toString().'.'.$ext;
                        Storage::disk('public')->put($filename, $binary);
                        $addedDelivery[] = $filename;
                    }
                } elseif (is_string($item)) {
                    // kalau sudah berupa path (kompatibilitas lama)
                    $addedDelivery[] = $item;
                }
            }

            if ($addedDelivery) {
                $data['delivery_images'] = array_values(array_unique(array_merge($existing, $addedDelivery)));
                // auto set delivered_at/by jika belum terisi
                $data['delivered_at'] = $data['delivered_at'] ?? now();
                $data['delivered_by'] = $data['delivered_by'] ?? ($user?->employee_id);
            }
        }

        /**
         * 4) Simpan perubahan
         */
        $model->fill($data);
        $model->save();

        return static::sendSuccessResponse($model->fresh(), 'Successfully Update Resource');
    }
}
