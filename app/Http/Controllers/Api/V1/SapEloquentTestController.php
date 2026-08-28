<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SapEloquentTestController extends Controller
{
    /**
     * 1. Get all records
     */
    public function get()
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->orderBy('CardCode')
            ->get();

        return $this->success($data);
    }

    /**
     * 2. Get first record
     */
    public function first()
    {
        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->orderBy('CardCode')
            ->first();

        return $this->success($data);
    }

    /**
     * 3. Find by primary key
     */
    public function find()
    {
        $data = BusinessPartner::query()
            ->find(1278);

        return $this->success($data);
    }

    /**
     * 4. Where =
     */
    public function where()
    {
        $data = BusinessPartner::query()
            ->where('CardCode', 'V100010')
            ->get();

        return $this->success($data);
    }

    /**
     * 5. Where multiple conditions
     */
    public function whereMultiple()
    {
        $data = BusinessPartner::query()
            ->where('CardType', 'S')
            ->where('CardCode', 'V100010')
            ->get();

        return $this->success($data);
    }

    /**
     * 6. OR WHERE
     */
    public function orWhere()
    {
        $data = BusinessPartner::query()
            ->where('CardCode', 'V100010')
            ->orWhere('CardCode', 'V100011')
            ->get();

        return $this->success($data);
    }

    /**
     * 7. WHERE IN
     */
    public function whereIn()
    {
        $data = BusinessPartner::query()
            ->whereIn('CardCode', [
                'V100010',
                'V100011',
                'V100012',
            ])
            ->get();

        return $this->success($data);
    }

    /**
     * 8. WHERE NOT IN
     */
    public function whereNotIn()
    {
        $data = BusinessPartner::query()
            ->whereNotIn('CardCode', [
                'V100010',
                'V100011',
            ])
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 9. WHERE NULL
     */
    public function whereNull()
    {
        $data = BusinessPartner::query()
            ->whereNull('CardName')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 10. WHERE NOT NULL
     */
    public function whereNotNull()
    {
        $data = BusinessPartner::query()
            ->whereNotNull('CardName')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 11. WHERE LIKE
     */
    public function whereLike()
    {
        $data = BusinessPartner::query()
            ->where('CardName', 'LIKE', '%General%')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 12. WHERE NOT LIKE
     */
    public function whereNotLike()
    {
        $data = BusinessPartner::query()
            ->where('CardName', 'NOT LIKE', '%General%')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 13. WHERE BETWEEN
     */
    public function whereBetween()
    {
        $data = BusinessPartner::query()
            ->whereBetween('CardCode', [
                'V100010',
                'V100100',
            ])
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 14. WHERE NOT BETWEEN
     */
    public function whereNotBetween()
    {
        $data = BusinessPartner::query()
            ->whereNotBetween('CardCode', [
                'V100010',
                'V100100',
            ])
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 15. ORDER BY ASC
     */
    public function orderByAsc()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode', 'asc')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 16. ORDER BY DESC
     */
    public function orderByDesc()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode', 'desc')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 17. LIMIT
     */
    public function limit()
    {
        $data = BusinessPartner::query()
            ->limit(10)
            ->get();

        return $this->success($data);
    }

    /**
     * 18. OFFSET
     */
    public function offset()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->offset(10)
            ->limit(10)
            ->get();

        return $this->success($data);
    }

    /**
     * 19. LIMIT + OFFSET
     */
    public function limitOffset()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->limit(10)
            ->offset(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 20. PAGINATE
     */
    public function paginate(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $data = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->orderBy('CardCode')
            ->paginate($perPage);

        return $this->success($data);
    }

    /**
     * 21. SIMPLE PAGINATE
     */
    public function simplePaginate(Request $request)
    {
        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->simplePaginate($perPage);

        return $this->success($data);
    }

    /**
     * 22. COUNT
     */
    public function count()
    {
        $count = BusinessPartner::query()->count();

        return $this->success([
            'count' => $count,
        ]);
    }

    /**
     * 23. EXISTS
     */
    public function exists()
    {
        $exists = BusinessPartner::query()
            ->where('CardCode', 'V100010')
            ->exists();

        return $this->success([
            'exists' => $exists,
        ]);
    }

    /**
     * 24. DOESN'T EXIST
     */
    public function doesntExist()
    {
        $exists = BusinessPartner::query()
            ->where('CardCode', 'XXXXXXXX')
            ->doesntExist();

        return $this->success([
            'doesnt_exist' => $exists,
        ]);
    }

    /**
     * 25. VALUE
     */
    public function value()
    {
        $value = BusinessPartner::query()
            ->where('CardCode', 'V100010')
            ->value('CardName');

        return $this->success([
            'CardName' => $value,
        ]);
    }

    /**
     * 26. PLUCK
     */
    public function pluck()
    {
        $data = BusinessPartner::query()
            ->limit(20)
            ->pluck('CardName', 'CardCode');

        return $this->success($data);
    }

    /**
     * 27. DISTINCT
     */
    public function distinct()
    {
        $data = BusinessPartner::query()
            ->select('CardType')
            ->distinct()
            ->get();

        return $this->success($data);
    }

    /**
     * 28. GROUP BY
     */
    public function groupBy()
    {
        $data = BusinessPartner::query()
            ->select([
                'CardType',
                DB::raw('COUNT(*) AS "Total"'),
            ])
            ->groupBy('CardType')
            ->get();

        return $this->success($data);
    }

    /**
     * 29. MAX
     */
    public function max()
    {
        $value = BusinessPartner::query()
            ->max('CardCode');

        return $this->success([
            'max' => $value,
        ]);
    }

    /**
     * 30. MIN
     */
    public function min()
    {
        $value = BusinessPartner::query()
            ->min('CardCode');

        return $this->success([
            'min' => $value,
        ]);
    }

    /**
     * 31. AVG
     */
    public function avg()
    {
        $value = BusinessPartner::query()
            ->avg('DocEntry');

        return $this->success([
            'avg' => $value,
        ]);
    }

    /**
     * 32. SUM
     */
    public function sum()
    {
        $value = BusinessPartner::query()
            ->sum('DocEntry');

        return $this->success([
            'sum' => $value,
        ]);
    }

    /**
     * 33. FIRST OR FAIL
     */
    public function firstOrFail()
    {
        $data = BusinessPartner::query()
            ->where('CardCode', 'V100010')
            ->firstOrFail();

        return $this->success($data);
    }

    /**
     * 34. FIND MANY
     */
    public function findMany()
    {
        $data = BusinessPartner::query()
            ->whereIn('CardCode', [
                'V100010',
                'V100011',
                'V100012',
            ])
            ->get();

        return $this->success($data);
    }

    /**
     * 35. WHEN
     */
    public function when(Request $request)
    {
        $search = $request->input('search');

        $data = BusinessPartner::query()
            ->when(
                filled($search),
                function ($query) use ($search) {
                    $query->where(
                        'CardName',
                        'LIKE',
                        "%{$search}%"
                    );
                }
            )
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 36. WHERE COLUMN
     */
    public function whereColumn()
    {
        $data = BusinessPartner::query()
            ->whereColumn('CardCode', 'CardName')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 37. RAW SELECT
     */
    public function rawSelect()
    {
        $data = BusinessPartner::query()
            ->selectRaw(
                '"CardCode", "CardName", LENGTH("CardName") AS "NameLength"'
            )
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 38. RAW WHERE
     *
     * Only use trusted SQL fragments.
     */
    public function rawWhere()
    {
        $data = BusinessPartner::query()
            ->whereRaw(
                'LENGTH("CardName") > ?',
                [5]
            )
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 39. JOIN test
     *
     * Requires a valid SAP HANA table/column.
     */
    public function join()
    {
        $data = BusinessPartner::query()
            ->join(
                BusinessPartner::hanaTable('OCRG'),
                'OCRD.GroupCode',
                '=',
                'OCRG.GroupCode'
            )
            ->select([
                'OCRD.CardCode',
                'OCRD.CardName',
                'OCRG.GroupName',
            ])
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 40. REORDER
     */
    public function reorder()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->reorder('CardName')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 41. TAKE
     */
    public function take()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->take(10)
            ->get();

        return $this->success($data);
    }

    /**
     * 42. SKIP
     */
    public function skip()
    {
        $data = BusinessPartner::query()
            ->orderBy('CardCode')
            ->skip(10)
            ->take(10)
            ->get();

        return $this->success($data);
    }

    /**
     * 43. CHUNK
     *
     * Test only; returns first chunk.
     */
    public function chunk()
    {
        $rows = [];

        BusinessPartner::query()
            ->orderBy('CardCode')
            ->chunk(10, function ($chunk) use (&$rows) {
                $rows = $chunk->toArray();

                return false;
            });

        return $this->success($rows);
    }

    /**
     * 44. CHUNK BY ID
     *
     * Only use after confirming a suitable numeric key.
     */
    public function chunkById()
    {
        $rows = [];

        BusinessPartner::query()
            ->orderBy('DocEntry')
            ->chunkById(
                10,
                function ($chunk) use (&$rows) {
                    $rows = $chunk->toArray();

                    return false;
                },
                'DocEntry'
            );

        return $this->success($rows);
    }

    /**
     * 45. CURSOR
     */
    public function cursor()
    {
        $rows = [];

        foreach (
            BusinessPartner::query()
                ->orderBy('CardCode')
                ->limit(20)
                ->cursor()
            as $row
        ) {
            $rows[] = $row;
        }

        return $this->success($rows);
    }

    /**
     * 46. LAZY
     */
    public function lazy()
    {
        $rows = BusinessPartner::query()
            ->select([
                'CardCode',
                'CardName',
                'CardType',
            ])
            ->orderBy('CardCode')
            ->lazy()
            ->take(20)
            ->all();
        return $this->success($rows);
    }

    /**
     * 47. LAZY BY ID
     */
    public function lazyById()
    {
        $rows = [];

        foreach (
            BusinessPartner::query()
                ->select([
                    'DocEntry',
                    'CardCode',
                    'CardName',
                    'CardType',
                ])
                ->lazyById(10, 'DocEntry')
            as $row
        ) {
            $rows[] = $row;

            if (count($rows) >= 20) {
                break;
            }
        }

        return $this->success($rows);
    }

    /**
     * 48. PLUCK COLLECTION
     */
    public function pluckCollection()
    {
        $data = BusinessPartner::query()
            ->limit(20)
            ->get()
            ->pluck('CardName', 'CardCode');

        return $this->success($data);
    }

    /**
     * 49. TO BASE
     */
    public function toBase()
    {
        $data = BusinessPartner::query()
            ->toBase()
            ->select([
                'CardCode',
                'CardName',
            ])
            ->orderBy('CardCode')
            ->limit(20)
            ->get();

        return $this->success($data);
    }

    /**
     * 50. SQL + BINDING TEST
     */
    public function binding()
    {
        $cardCode = "V100010";

        $data = BusinessPartner::query()
            ->where('CardCode', $cardCode)
            ->get();

        return $this->success([
            'card_code' => $cardCode,
            'data' => $data,
        ]);
    }

    /**
     * Common success response.
     */
    protected function success($data)
    {
        return response()->json([
            'count' => is_countable($data) ? count($data) : 1,
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Common error response.
     */
    protected function error(Throwable $e)
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
