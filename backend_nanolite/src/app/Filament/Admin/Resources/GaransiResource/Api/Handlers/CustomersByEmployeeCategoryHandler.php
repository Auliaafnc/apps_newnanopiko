<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use App\Models\Customer;
use Rupadana\ApiService\Http\Handlers;

class CustomersByEmployeeCategoryHandler extends Handlers
{
    public static ?string $uri = '/customers/by-filter'; // GET /api/admin/garansi/customers/by-filter?department_id=..&employee_id=..&category_id=..

    public function __invoke() { return $this->handler(); }

    public function handler()
    {
        $deptId = request('department_id');
        $empId  = request('employee_id');
        $catId  = request('category_id');

        if (! $deptId || ! $empId || ! $catId) {
            return static::sendErrorResponse('department_id, employee_id, and category_id are required', 422);
        }

        $customers = Customer::query()
            ->where('status', 'active')
            ->where('department_id', $deptId)
            ->where('employee_id',  $empId)
            ->where('customer_categories_id', $catId)
            ->select('id','name','phone','full_address as address','customer_categories_id as category_id')
            ->orderBy('name')
            ->get();

        return static::sendSuccessResponse($customers, 'Customers retrieved successfully.');
    }
}
