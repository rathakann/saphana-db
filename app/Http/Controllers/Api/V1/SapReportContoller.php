<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use App\Services\SapHana;
use Illuminate\Support\Facades\DB;
use PDO;

class SapReportContoller extends Controller
{
    public function __construct(
        protected SapHana $saphana
    ) {}
    public function index(Request $request)
    {
        $customerName = trim(
            (string) $request->input('customer_name', '')
        );

        $data = $this->saphana->paginate(
            '
            SELECT
                "CardCode",
                "CardName"
            FROM "TNLOIL_V051_01"."OCRD"
            WHERE UPPER("CardName") LIKE UPPER(:customer_name)
            ORDER BY "CardCode"
        ',
            [
                'customer_name' => "%{$customerName}%",
            ],
            page: max(
                1,
                (int) $request->input('page', 1)
            ),
            perPage: (int) $request->input('per_page', 10)
        );

        return $data;
    }
    public function show($cardCode)
    {
        $data = $this->saphana->selectOne(
            '
            SELECT
                "CardCode",
                "CardName"
            FROM "TNLOIL_V051_01"."OCRD"
            WHERE "CardCode" = :cardCode
        ',
            [
                'cardCode' => $cardCode,
            ]
        );
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function businessPartner(Request $request, $cardCode)
    {
        // $businessPartner = BusinessPartner::select(
        //     'CardCode',
        //     'CardName',
        //     'CardType'
        // )->whereCardCode($cardCode)->get();

        // $businessPartner = BusinessPartner::select(
        //     'CardCode',
        //     'CardName',
        //     'CardType'
        // )->whereRaw('"CardCode" = \'' . $cardCode . '\'')->get();

        // if ($businessPartner->isEmpty()) {
        //     return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        //}

        // $businessPartner = BusinessPartner::select(
        //     'CardCode',
        //     'CardName',
        //     'CardType'
        // )->where('CardCode', $cardCode)->get();
        // return response()->json(['success' => true, 'data' => $businessPartner]);


        // $businessPartner = BusinessPartner::query()
        //     ->select([
        //         'CardCode',
        //         'CardName',
        //         'CardType',
        //     ])
        //     ->whereHana('CardCode', $cardCode)
        //     ->first();

        // $businessPartner = BusinessPartner::select(
        //     'CardCode',
        //     'CardName',
        //     'CardType'
        // )->where('CardCode', 'V100010')->first();

        // $businessPartner = BusinessPartner::query()
        //     ->select([
        //         'CardCode',
        //         'CardName',
        //         'CardType',
        //     ])
        //     ->where('CardCode', $cardCode)
        //     ->get();

        $businessPartners = BusinessPartner::query()
            ->select(
                'DocEntry',
                'CardCode',
                'CardName',
                'CardType'
            )
            ->where('CardType', 'S')
            ->orderBy('CardCode')
            ->paginate(5);



        return response()->json([
            'success' => true,
            'data' => $businessPartners,
        ]);
    }
}
