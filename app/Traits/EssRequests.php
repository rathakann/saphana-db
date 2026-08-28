<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Exception;

trait EssRequests
{

    public function essRequest($method, $endpoint, $request = null)
    {
        try {
            $respn = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->withOptions([
                'verify' => false,
            ])->acceptJson();
            $url    = "http://" . env('ESS_HOST') . ":9090/ords/wsts/stocktake/" . $endpoint;
            $respn  = $respn->timeout(0); // Set Timeout to Unlimited

            switch ($method) {
                case 'get':
                    $respn = $respn->get($url, $request);
                    break;
                case 'post':
                    $respn = $respn->post($url, $request);
                    break;
                    //update batch columns
                case 'put':
                    $respn = $respn->put($url, $request);
                    break;
                    //update a specific column
                case 'patch':
                    $respn = $respn->patch($url, $request);
                    break;
                case 'delete':
                    $respn = $respn->delete($url, $request);
                    break;
                default:
                    # code..
                    break;
            }
            return $respn->json();
        } catch (Exception $e) {
            return array('status' => 'errors', 'message' => $e->getMessage(), 'status_code' => $e->getCode());
        }
    }
}
