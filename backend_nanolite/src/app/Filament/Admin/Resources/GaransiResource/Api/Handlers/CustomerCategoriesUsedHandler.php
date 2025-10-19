<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use App\Models\CustomerCategories;
use Rupadana\ApiService\Http\Handlers;

class CustomerCategoriesUsedHandler extends Handlers
{
    public static ?string $uri = '/customer-categories/used'; // GET /api/admin/garansi/customer-categories/used?employee_id=XX

    public function __invoke() { return $this->handler(); }

    public function handler()
    {
        $employeeId = request('employee_id');

        if (! $employeeId) {
            return static::sendErrorResponse('employee_id is required', 422);
        }

        $categories = CustomerCategories::whereHas('customers', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            })
            ->select('id','name')
            ->orderBy('name')
            ->get();

        return static::sendSuccessResponse($categories, 'Customer categories retrieved successfully.');
    }
}
