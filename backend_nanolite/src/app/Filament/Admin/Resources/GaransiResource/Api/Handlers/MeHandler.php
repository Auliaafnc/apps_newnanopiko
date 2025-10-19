<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Handlers;

use Illuminate\Support\Facades\Auth;
use Rupadana\ApiService\Http\Handlers;

class MeHandler extends Handlers
{
    public static ?string $uri = '/me'; // GET /api/admin/garansi/me

    public function __invoke() { return $this->handler(); } // biar bisa dipakai Route::get(..., MeHandler::class)

    public function handler()
    {
        $u = Auth::user();

        return static::sendSuccessResponse([
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'role'          => $u->getRoleNames()->first(),
            'employee_id'   => $u->employee_id,
            'department_id' => optional($u->employee)->department_id,
        ], 'Profile retrieved successfully.');
    }
}
