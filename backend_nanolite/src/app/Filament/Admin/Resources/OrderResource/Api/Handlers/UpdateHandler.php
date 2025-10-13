<?php

namespace App\Filament\Admin\Resources\OrderResource\Api\Handlers;

use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Admin\Resources\OrderResource\Api\Requests\UpdateOrderRequest;
use Rupadana\ApiService\Http\Handlers;

class UpdateHandler extends Handlers
{
    public static ?string $uri = '/{record}';

    public static ?string $resource = OrderResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }

    /**
     * Update Order
     */
    public function handler(UpdateOrderRequest $request, $record)
    {
        $model = $record;

        // isi semua field
        $data = $request->all();
        $model->fill($data);

        $model->save();

        return static::sendSuccessResponse($model, 'Successfully Update Order');
    }
}
