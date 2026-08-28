<?php

use Illuminate\Support\Facades\Route;



Route::prefix('v1')->group(function () {
    require __DIR__ . '/api_v1.php';
    require __DIR__ . '/sap_test.php';
    require __DIR__ . '/sap_report.php';
});
