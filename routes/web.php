<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\IncomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:web')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::apiResource('incomes', IncomeController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('debts', DebtController::class);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/reports/annual', [DashboardController::class, 'annualReport']);
    });
});

Route::get('/', function () {
    return response()->json([
        'name' => 'Finance Atlas API',
        'status' => 'ok',
    ]);
});
