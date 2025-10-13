<?php

namespace App\Filament\Admin\Resources\ProductReturnResource\Api\Handlers;

use App\Filament\Admin\Resources\ProductReturnResource;
use App\Filament\Admin\Resources\ProductReturnResource\Api\Requests\CreateProductReturnRequest;
use Rupadana\ApiService\Http\Handlers;
use Illuminate\Http\UploadedFile;

class CreateHandler extends Handlers
{
    public static ?string $uri = '/';

    public static ?string $resource = ProductReturnResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Create ProductReturn
     */
    public function handler(CreateProductReturnRequest $request)
    {
        $model = new (static::getModel());

        // isi semua field kecuali image
        $data = $request->except('image');
        $model->fill($data);

        // handle upload satu gambar
        $file = $request->file('image') 
            ?? $request->file('image.0') 
            ?? $request->file('image[]');

        if ($file instanceof UploadedFile) {
            $path = $file->store('product-returns', 'public');
            $model->image = $path; // ✅ simpan string path
        }

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Create ProductReturn');
    }
}
