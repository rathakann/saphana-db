<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
// use App\Models\Setting\SystemLogs;

trait BaseFn
{

    /*
        Traits is specail PHP feature that allow to define interface or sharp of object.
        Author: Kann Ratha
        Date:   2027-08-07
        Desc:   Base Function for reusable
    */
    public function generateDocumentNumber(string $table, string $tranTypeShort, string $tranType, int $location, string $flag = null, $lengths = 8)
    {
        // $zeroLenght = $this->generateZeroLengths($lengths);
        $respn = DB::table($table)
            ->selectRaw("CONCAT('" . $tranTypeShort . "', RIGHT(CONCAT('000','" . $location . "'), 3), RIGHT(CONCAT('000000000000', RIGHT(`code`,8)+1), " . $lengths . ")) AS docNumber")
            ->whereLocationId($location)
            ->whereType($tranType)
            ->when($flag, function ($query) use ($flag) {
                $query->whereFlag($flag);
            })->orderBy('id', 'desc')->first();
        if (!$respn) {
            return $tranTypeShort . substr('000' . $location, -3) . substr('0000000000001', -$lengths);
        } else {
            return $respn->docNumber;
        }
        // $respn = DB::table($table)
        //     ->whereLocationId($location)
        //     ->whereType($tranType)
        //     ->when($flag, function ($query) use ($flag) {
        //         $query->whereFlag($flag);
        //     })
        //     ->selectRaw("CONCAT('" . $tranTypeShort . "', RIGHT(CONCAT('000','" . $location . "'), 3), RIGHT(CONCAT('" . $zeroLenght . "', COUNT(*)+1), " . $lengths . ")) AS docNumber")->first();
        // return $respn->docNumber;
    }
    public function generateCodeNumber(string $table, string $prefix, $lengths = 6)
    {
        $zeroLenght = $this->generateZeroLengths($lengths);
        $respn = DB::table($table)->selectRaw("CONCAT('" . $prefix . "',RIGHT(CONCAT('" . $zeroLenght . "', COUNT(*)+1), " . $lengths . ")) AS codeNumber")->first();
        return $respn->codeNumber;
    }
    public function generateZeroLengths($lengths)
    {
        $zero = '0';
        for ($i = 0; $i < $lengths; $i++) {
            $zero .= $zero;
        }
        return $zero;
    }
    // public function createLogs($type, $msg, $status = true)
    // {
    //     SystemLogs::create([
    //         'type'          =>  $type,
    //         'status'        =>  (bool)$status ? 'success' : 'failed',
    //         'message'       =>  $msg,
    //         'created_at'    =>  date('Y-m-d H:i:s')
    //     ]);
    // }
    public function getCurrentStockQty($locationId = 0, $warehouseId = 0, $itemId = 0)
    {
        $stock = DB::table('stockings')->join('stocking_details', 'stockings.id', '=', 'stocking_details.stocking_id')
            ->select(DB::raw("IFNULL(SUM(`stock_after`->'$[2]'), 0) AS 'stock_qty'"), 'stocking_details.bin_id')
            ->where('stockings.location_id', $locationId)
            ->where('stockings.location_wh', $warehouseId)
            ->where('stocking_details.item_id', $itemId)
            ->where('stocking_details.bin_flag', 1)
            ->groupBy('stocking_details.bin_id')
            ->get();
        return $stock->sum('stock_qty');
    }
}
