<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Administrator\AuthController;
use App\Http\Controllers\Api\V1\SapReportContoller;
use App\Models\BusinessPartner;
use Illuminate\Support\Facades\DB;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout']);


Route::get('/customers', [SapReportContoller::class, 'index']);
Route::get('/customers/{cardCode}', [SapReportContoller::class, 'show']);
Route::get('/business-partner/{cardCode}', [SapReportContoller::class, 'businessPartner']);


Route::get('/hana', function () {
    // $result = DB::connection('saphana')->select('
    //     SELECT
    //         "CardCode",
    //         "CardName"
    //     FROM "TNLOIL_V051_01"."OCRD"
    //     LIMIT 10
    // ');
    // $result = BusinessPartner::where(
    //     'CardCode',
    //     'V100091'
    // )->first();

    // return response()->json([
    //     'success' => true,
    //     'data' => $result,
    // ]);

    //$result = DB::connection('saphana')->select( ' SELECT "CardCode", "CardName" FROM "TNLOIL_V051_01"."OCRD" WHERE "CardCode" = \'V100091\' ' ); 

    //$result = DB::connection('saphana')->select('SELECT CURRENT_TIMESTAMP FROM DUMMY');

    // $result = DB::connection('saphana')
    //     ->select(
    //         '
    //     SELECT
    //         "CardCode",
    //         "CardName"
    //     FROM "TNLOIL_V051_01"."OCRD"
    //     WHERE "CardCode" = ?
    //     ',
    //         [
    //             "'V100091'",
    //         ]
    //     );

    $result = DB::connection('saphana')->select(
        '
        SELECT
            "CardCode",
            "CardName"
        FROM "TNLOIL_V051_01"."OCRD"
        WHERE "CardCode" = \'V100091\'
    '
    );



    return response()->json([
        'success' => true,
        'data' => $result,
    ]);


    // return response()->json(['success' => true, 'data' => $result,]);
});


Route::get('/test', function () {
    try {

        $pdo = new PDO(
            'odbc:DRIVER={HDBODBC};SERVERNODE=172.20.23.202:30015;DATABASE=TNLOIL_V051_01',
            'SYSTEM',
            'HDB4dm@23'
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        echo "HANA CONNECTION OK" . PHP_EOL;

        $stmt = $pdo->query(
            'SELECT CURRENT_TIMESTAMP FROM DUMMY'
        );

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        print_r($result);

        // return response()->json([
        //     'drivers' => PDO::getAvailableDrivers(),
        //     'result' => DB::connection('saphana')
        //         ->select('SELECT CURRENT_TIMESTAMP FROM DUMMY'),
        // ]);


        // $pdo = new PDO(
        //     'odbc:DRIVER={HDBODBC};SERVERNODE=172.20.23.202:30015;DATABASE=TNLOIL_V051_01',
        //     'SYSTEM',
        //     'HDB4dm@23'
        // );
        // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // $sql = '
        //     SELECT
        //         "CardCode",
        //         "CardName"
        //     FROM "TNLOIL_V051_01"."OCRD"
        //     LIMIT 10
        // ';
        // $stmt = $pdo->query($sql);
        // $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // return response()->json(['data' => $rows]);

    } catch (PDOException $e) {
        echo "HANA CONNECTION FAILED" . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;
    }
});
