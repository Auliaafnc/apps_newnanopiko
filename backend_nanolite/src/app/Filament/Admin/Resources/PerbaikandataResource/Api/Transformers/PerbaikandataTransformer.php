<?php
namespace App\Filament\Admin\Resources\PerbaikandataResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Perbaikandata;

/**
 * @property Perbaikandata $resource
 */
class PerbaikandataTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
