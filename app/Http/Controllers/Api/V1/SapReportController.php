<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SapReportController extends Controller
{
    /**
     * 1. JOIN
     */
    public function testJoin(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'OCRD.CardCode',
                'OCRD.CardName',
                'OCRD.CardType',
                'OCRG.GroupName',
            ])
            ->join(
                BusinessPartner::hanaTable('OCRG'),
                'OCRD.GroupCode',
                '=',
                'OCRG.GroupCode'
            )
            ->orderBy('OCRD.CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'join',
            'data' => $data,
        ]);
    }

    /**
     * 2. LEFT JOIN
     */
    public function testLeftJoin(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'OCRD.CardCode',
                'OCRD.CardName',
                'OCRD.GroupCode',
                'OCRG.GroupName',
            ])
            ->leftJoin(
                BusinessPartner::hanaTable('OCRG'),
                'OCRD.GroupCode',
                '=',
                'OCRG.GroupCode'
            )
            ->orderBy('OCRD.CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'leftJoin',
            'data' => $data,
        ]);
    }

    /**
     * 3. JOIN SUBQUERY
     *
     * Get the latest invoice DocDate for each customer.
     */
    public function testJoinSub(): JsonResponse
    {
        $latestInvoices = BusinessPartner::query()
            ->from(BusinessPartner::hanaTable('OINV'))
            ->select([
                'CardCode',
                DB::raw('MAX("DocDate") AS "LastInvoiceDate"'),
            ])
            ->groupBy('CardCode');

        $data = BusinessPartner::query()
            ->select([
                'OCRD.CardCode',
                'OCRD.CardName',
                'LatestInvoice.LastInvoiceDate',
            ])
            ->leftJoinSub(
                $latestInvoices,
                'LatestInvoice',
                function ($join) {
                    $join->on(
                        'OCRD.CardCode',
                        '=',
                        'LatestInvoice.CardCode'
                    );
                }
            )
            ->orderBy('OCRD.CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'joinSub',
            'data' => $data,
        ]);
    }

    /**
     * 4. GROUP BY
     *
     * Count customers by CardType.
     */
    public function testGroupBy(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardType',
                DB::raw('COUNT(*) AS "Total"'),
            ])
            ->groupBy('CardType')
            ->orderBy('CardType')
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'groupBy',
            'data' => $data,
        ]);
    }

    /**
     * 5. HAVING
     *
     * Only customer types having more than 1 record.
     */
    public function testHaving(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardType',
                DB::raw('COUNT("DocEntry") AS "Total"'),
            ])
            ->groupBy('CardType')
            ->havingRaw('COUNT("DocEntry") > 1')
            ->orderBy('CardType')
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'having',
            'data' => $data,
        ]);
    }

    /**
     * 6. WHERE DATE
     *
     * Business partners created on/after a specific date.
     */
    public function testWhereDate(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CreateDate',
            ])
            ->where('CreateDate', '>=', '2025-01-01')
            ->orderBy('CreateDate')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'whereDate',
            'data' => $data,
        ]);
    }

    /**
     * 7. WHERE IN
     */
    public function testWhereIn(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->whereIn('CardType', [
                'C',
                'S',
            ])
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'whereIn',
            'data' => $data,
        ]);
    }

    /**
     * 8. WHERE EXISTS
     *
     * Customers who have at least one invoice.
     */
    public function testWhereExists(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
            ])
            ->whereExists(function (Builder $query) {
                $query->select(DB::raw('1'))
                    ->from(BusinessPartner::hanaTable('OINV'))
                    ->whereColumn(
                        BusinessPartner::hanaTable('OINV') . '.CardCode',
                        BusinessPartner::hanaTable('OCRD') . '.CardCode'
                    );
            })
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'whereExists',
            'data' => $data,
        ]);
    }

    /**
     * 9. WHERE NOT EXISTS
     *
     * Customers who do NOT have an invoice.
     */
    public function testWhereNotExists(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
            ])
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw('1'))
                    ->from(BusinessPartner::hanaTable('OINV'))
                    ->whereColumn(
                        BusinessPartner::hanaTable('OINV') . '.CardCode',
                        BusinessPartner::hanaTable('OCRD') . '.CardCode'
                    );
            })
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'whereNotExists',
            'data' => $data,
        ]);
    }

    /**
     * 10. UNION
     *
     * Get customer and vendor business partners.
     */
    public function testUnion(): JsonResponse
    {
        $customers = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
            ])
            ->where('CardType', 'C');

        $vendors = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
            ])
            ->where('CardType', 'S');

        $data = $customers
            ->union($vendors)
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'union',
            'data' => $data,
        ]);
    }

    /**
     * 11. DISTINCT
     */
    public function testDistinct(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select('CardType')
            ->distinct()
            ->orderBy('CardType')
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'distinct',
            'data' => $data,
        ]);
    }

    /**
     * 12. SELECT RAW
     */
    public function testSelectRaw(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->selectRaw(
                '"CardCode", "CardName", LENGTH("CardName") AS "NameLength"'
            )
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'selectRaw',
            'data' => $data,
        ]);
    }

    /**
     * 13. ORDER BY RAW
     */
    public function testOrderByRaw(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->orderByRaw('"CardCode" DESC')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'orderByRaw',
            'data' => $data,
        ]);
    }

    /**
     * 14. GROUP BY RAW
     */
    public function testGroupByRaw(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->selectRaw(
                '"CardType", COUNT(*) AS "Total"'
            )
            ->groupByRaw('"CardType"')
            ->orderByRaw('"CardType"')
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'groupByRaw',
            'data' => $data,
        ]);
    }

    /**
     * 15. HAVING RAW
     */
    public function testHavingRaw(): JsonResponse
    {
        $data = BusinessPartner::query()
            ->selectRaw(
                '"CardType", COUNT(*) AS "Total"'
            )
            ->groupByRaw('"CardType"')
            ->havingRaw('COUNT(*) > 1')
            ->orderByRaw('"CardType"')
            ->get();

        return response()->json([
            'success' => true,
            'test' => 'havingRaw',
            'data' => $data,
        ]);
    }
}
