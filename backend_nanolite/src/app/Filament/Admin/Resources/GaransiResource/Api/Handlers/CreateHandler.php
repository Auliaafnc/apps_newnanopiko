<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use App\Filament\Admin\Resources\GaransiResource;
use App\Filament\Admin\Resources\GaransiResource\Api\Requests\CreateGaransiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rupadana\ApiService\Http\Handlers;

class CreateHandler extends Handlers
{
    public static ?string $uri = '/';
    public static ?string $resource = GaransiResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Create Garansi (logic mengikuti Filament)
     */
    public function handler(CreateGaransiRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();

        /**
         * 1) Force dept & employee untuk role sales
         */
        if ($user->hasAnyRole(['sales','head_sales','head_digital'])) {
            $data['department_id'] = optional($user->employee)->department_id;
            $data['employee_id']   = $user->employee_id;
        }

        /**
         * 2) Default status = pending
         */
        $data['status_pengajuan'] = $data['status_pengajuan'] ?? 'pending';
        $data['status_product']   = $data['status_product']   ?? 'pending';
        $data['status_garansi']   = $data['status_garansi']   ?? 'pending';

        /**
         * 3) Format address jika dikirim sebagai array seperti Flutter
         *    (menjadi string multi-line sama seperti di Filament)
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
         * 4) Simpan multi image dari dataURL base64 → kolom 'image' (array/json)
         *    Flutter mengirim di key 'images': ["data:image/jpeg;base64,...", ...]
         */
        if (!empty($data['images']) && is_array($data['images'])) {
            $stored = [];
            foreach ($data['images'] as $dataUrl) {
                if (!is_string($dataUrl)) continue;

                if (preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
                    $ext  = strtolower($m[1] ?? 'jpg');
                    if ($ext === 'jpeg') $ext = 'jpg';
                    $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
                    $binary = base64_decode($base64, true);
                    if ($binary === false) continue;

                    $filename = 'garansi-photos/'.Str::uuid()->toString().'.'.$ext;
                    Storage::disk('public')->put($filename, $binary);
                    $stored[] = $filename;
                }
            }
            // kolom di DB sebaiknya json/array (cast di model)
            $data['image'] = $stored;
            unset($data['images']);
        }

        /**
         * 5) Pastikan products (repeater) disimpan apa adanya (json)
         *    Misal sudah tervalidasi sebagai array di FormRequest.
         */
        // tidak ada transform khusus

        $modelClass = static::getModel();
        $model = new $modelClass();
        $model->fill($data);
        $model->save();

        return static::sendSuccessResponse($model->fresh(), 'Successfully Create Resource');
    }
}
