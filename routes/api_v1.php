<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Administrator\AuthController;
use App\Models\BusinessPartner;
use Illuminate\Support\Facades\DB;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout']);
