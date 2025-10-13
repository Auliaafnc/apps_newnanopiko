<?php
namespace App\Filament\Admin\Resources\PerbaikandataResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Admin\Resources\PerbaikandataResource;
use App\Filament\Admin\Resources\PerbaikandataResource\Api\Requests\CreatePerbaikandataRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = PerbaikandataResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Perbaikandata
     *
     * @param CreatePerbaikandataRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreatePerbaikandataRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}