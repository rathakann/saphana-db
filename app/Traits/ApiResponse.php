<?php

namespace App\Traits;

trait ApiResponse{

    /*
        Author: Kann Ratha
        Date:   2024-08-07
        Desc:   Create the strandard API json response
    */
    public function apiResponse($data, $status = 'success', $count = null, $httpCode = 200, $msg = null, $errors = null){
        return response()->json([
            'count'         => $count,
            'data'          => $data,
            'status'        => $status,
            'message'       => $msg,
            'status_code'   => $httpCode,
            'error'        => $errors
        ], $httpCode);
    }
}
