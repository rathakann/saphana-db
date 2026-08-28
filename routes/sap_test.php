<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SapEloquentTestController;

Route::prefix('sap')->group(function () {

    Route::get('/get', [
        SapEloquentTestController::class,
        'get',
    ]);

    Route::get('/first', [
        SapEloquentTestController::class,
        'first',
    ]);

    Route::get('/find', [
        SapEloquentTestController::class,
        'find',
    ]);

    Route::get('/where', [
        SapEloquentTestController::class,
        'where',
    ]);

    Route::get('/where-multiple', [
        SapEloquentTestController::class,
        'whereMultiple',
    ]);

    Route::get('/or-where', [
        SapEloquentTestController::class,
        'orWhere',
    ]);

    Route::get('/where-in', [
        SapEloquentTestController::class,
        'whereIn',
    ]);

    Route::get('/where-not-in', [
        SapEloquentTestController::class,
        'whereNotIn',
    ]);

    Route::get('/where-null', [
        SapEloquentTestController::class,
        'whereNull',
    ]);

    Route::get('/where-not-null', [
        SapEloquentTestController::class,
        'whereNotNull',
    ]);

    Route::get('/where-like', [
        SapEloquentTestController::class,
        'whereLike',
    ]);

    Route::get('/where-not-like', [
        SapEloquentTestController::class,
        'whereNotLike',
    ]);

    Route::get('/where-between', [
        SapEloquentTestController::class,
        'whereBetween',
    ]);

    Route::get('/where-not-between', [
        SapEloquentTestController::class,
        'whereNotBetween',
    ]);

    Route::get('/order-by-asc', [
        SapEloquentTestController::class,
        'orderByAsc',
    ]);

    Route::get('/order-by-desc', [
        SapEloquentTestController::class,
        'orderByDesc',
    ]);

    Route::get('/limit', [
        SapEloquentTestController::class,
        'limit',
    ]);

    Route::get('/offset', [
        SapEloquentTestController::class,
        'offset',
    ]);

    Route::get('/limit-offset', [
        SapEloquentTestController::class,
        'limitOffset',
    ]);

    Route::get('/paginate', [
        SapEloquentTestController::class,
        'paginate',
    ]);

    Route::get('/simple-paginate', [
        SapEloquentTestController::class,
        'simplePaginate',
    ]);

    Route::get('/count', [
        SapEloquentTestController::class,
        'count',
    ]);

    Route::get('/exists', [
        SapEloquentTestController::class,
        'exists',
    ]);

    Route::get('/doesnt-exist', [
        SapEloquentTestController::class,
        'doesntExist',
    ]);

    Route::get('/value', [
        SapEloquentTestController::class,
        'value',
    ]);

    Route::get('/pluck', [
        SapEloquentTestController::class,
        'pluck',
    ]);

    Route::get('/distinct', [
        SapEloquentTestController::class,
        'distinct',
    ]);

    Route::get('/group-by', [
        SapEloquentTestController::class,
        'groupBy',
    ]);

    Route::get('/max', [
        SapEloquentTestController::class,
        'max',
    ]);

    Route::get('/min', [
        SapEloquentTestController::class,
        'min',
    ]);

    Route::get('/avg', [
        SapEloquentTestController::class,
        'avg',
    ]);

    Route::get('/sum', [
        SapEloquentTestController::class,
        'sum',
    ]);

    Route::get('/first-or-fail', [
        SapEloquentTestController::class,
        'firstOrFail',
    ]);

    Route::get('/find-many', [
        SapEloquentTestController::class,
        'findMany',
    ]);

    Route::get('/when', [
        SapEloquentTestController::class,
        'when',
    ]);

    Route::get('/where-column', [
        SapEloquentTestController::class,
        'whereColumn',
    ]);

    Route::get('/raw-select', [
        SapEloquentTestController::class,
        'rawSelect',
    ]);

    Route::get('/raw-where', [
        SapEloquentTestController::class,
        'rawWhere',
    ]);

    Route::get('/join', [
        SapEloquentTestController::class,
        'join',
    ]);

    Route::get('/reorder', [
        SapEloquentTestController::class,
        'reorder',
    ]);

    Route::get('/take', [
        SapEloquentTestController::class,
        'take',
    ]);

    Route::get('/skip', [
        SapEloquentTestController::class,
        'skip',
    ]);

    Route::get('/chunk', [
        SapEloquentTestController::class,
        'chunk',
    ]);

    Route::get('/chunk-by-id', [
        SapEloquentTestController::class,
        'chunkById',
    ]);

    Route::get('/cursor', [
        SapEloquentTestController::class,
        'cursor',
    ]);

    Route::get('/lazy', [
        SapEloquentTestController::class,
        'lazy',
    ]);

    Route::get('/lazy-by-id', [
        SapEloquentTestController::class,
        'lazyById',
    ]);

    Route::get('/pluck-collection', [
        SapEloquentTestController::class,
        'pluckCollection',
    ]);

    Route::get('/to-base', [
        SapEloquentTestController::class,
        'toBase',
    ]);

    Route::get('/binding', [
        SapEloquentTestController::class,
        'binding',
    ]);
});
