<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use App\Filament\Admin\Resources\GaransiResource;
use App\Support\ApiPaging;
use Illuminate\Support\Facades\Auth;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Filament\Admin\Resources\GaransiResource\Api\Transformers\GaransiTransformer;

class PaginationHandler extends Handlers
{
    use ApiPaging;

    public static ?string $uri = '/';
    public static ?string $resource = GaransiResource::class;

    public function handler()
    {
        $user = Auth::user();

        // base query
        $base = static::getModel()::query()
            ->with(['company','customerCategory','employee','customer','department']);

        // === Role scoping: sama seperti getEloquentQuery di Resource ===
        if ($user->hasAnyRole(['sales','head_sales','head_digital'])) {
            $deptId = optional($user->employee)->department_id;
            $empId  = $user->employee_id;

            $base->where('department_id', $deptId)
                 ->where('employee_id', $empId);
        }

        // === QueryBuilder + filters/sorts yang relevan ===
        $qb = QueryBuilder::for($base)
            ->allowedFilters([
                // pencarian sederhana
                AllowedFilter::partial('no_garansi'),
                AllowedFilter::partial('phone'),
                AllowedFilter::partial('reason'),

                // exact filters
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('employee_id'),
                AllowedFilter::exact('customer_id'),
                AllowedFilter::exact('customer_categories_id'),

                // status (sesuai schema)
                AllowedFilter::exact('status_pengajuan'),
                AllowedFilter::exact('status_product'),
                AllowedFilter::exact('status_garansi'),

                // tanggal (rentang)
                AllowedFilter::callback('purchase_date_from', function ($query, $value) {
                    if ($value) $query->whereDate('purchase_date', '>=', $value);
                }),
                AllowedFilter::callback('purchase_date_to', function ($query, $value) {
                    if ($value) $query->whereDate('purchase_date', '<=', $value);
                }),
                AllowedFilter::callback('claim_date_from', function ($query, $value) {
                    if ($value) $query->whereDate('claim_date', '>=', $value);
                }),
                AllowedFilter::callback('claim_date_to', function ($query, $value) {
                    if ($value) $query->whereDate('claim_date', '<=', $value);
                }),
            ])
            ->allowedSorts([
                'id','no_garansi','purchase_date','claim_date',
                'created_at','updated_at',
            ])
            ->defaultSort('-created_at');

        $paginator = $qb
            ->paginate($this->perPage(request()))
            ->appends(request()->query())
            ->through(fn ($garansi) => new GaransiTransformer($garansi));

        return static::sendSuccessResponse($paginator, 'Garansi list retrieved successfully');
    }
}
