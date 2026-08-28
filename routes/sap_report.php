<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SapReportController;

Route::prefix('report')->group(function () {

    Route::get('/join', [
        SapReportController::class,
        'testJoin',
    ]);

    Route::get('/left-join', [
        SapReportController::class,
        'testLeftJoin',
    ]);

    Route::get('/join-sub', [
        SapReportController::class,
        'testJoinSub',
    ]);

    Route::get('/group-by', [
        SapReportController::class,
        'testGroupBy',
    ]);

    Route::get('/having', [
        SapReportController::class,
        'testHaving',
    ]);

    Route::get('/where-date', [
        SapReportController::class,
        'testWhereDate',
    ]);

    Route::get('/where-in', [
        SapReportController::class,
        'testWhereIn',
    ]);

    Route::get('/where-exists', [
        SapReportController::class,
        'testWhereExists',
    ]);

    Route::get('/where-not-exists', [
        SapReportController::class,
        'testWhereNotExists',
    ]);

    Route::get('/union', [
        SapReportController::class,
        'testUnion',
    ]);

    Route::get('/distinct', [
        SapReportController::class,
        'testDistinct',
    ]);

    Route::get('/select-raw', [
        SapReportController::class,
        'testSelectRaw',
    ]);

    Route::get('/order-by-raw', [
        SapReportController::class,
        'testOrderByRaw',
    ]);

    Route::get('/group-by-raw', [
        SapReportController::class,
        'testGroupByRaw',
    ]);

    Route::get('/having-raw', [
        SapReportController::class,
        'testHavingRaw',
    ]);
});
