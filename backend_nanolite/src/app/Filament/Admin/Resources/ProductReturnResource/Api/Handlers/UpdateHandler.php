<?php

namespace App\Filament\Admin\Resources\ProductReturnResource\Api\Handlers;

use App\Filament\Admin\Resources\ProductReturnResource;
use App\Filament\Admin\Resources\ProductReturnResource\Api\Requests\UpdateProductReturnRequest;
use Rupadana\ApiService\Http\Handlers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UpdateHandler extends Handlers
{
    public static ?string $uri = '/{record}';

    public static ?string $resource = ProductReturnResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Update ProductReturn
     */
    public function handler(UpdateProductReturnRequest $request, $record)
    {
        $model = $record;

        // isi semua field kecuali image
        $data = $request->except('image');
        $model->fill($data);

        // handle replace image tunggal
        $file = $request->file('image') 
            ?? $request->file('image.0') 
            ?? $request->file('image[]');

        if ($file instanceof UploadedFile) {
            // hapus foto lama kalau ada
            if ($model->image && Storage::disk('public')->exists($model->image)) {
                Storage::disk('public')->delete($model->image);
            }

            // upload baru
            $path = $file->store('product-returns', 'public');
            $model->image = $path;
        }

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Update ProductReturn');
    }
}
