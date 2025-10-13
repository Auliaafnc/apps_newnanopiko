<?php

namespace App\Filament\Admin\Resources\PerbaikandataResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Admin\Resources\PerbaikandataResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Admin\Resources\PerbaikandataResource\Api\Transformers\PerbaikandataTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = PerbaikandataResource::class;


    /**
     * Show Perbaikandata
     *
     * @param Request $request
     * @return PerbaikandataTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new PerbaikandataTransformer($query);
    }
}
