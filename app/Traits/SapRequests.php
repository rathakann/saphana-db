<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Exception;

trait SapRequests
{

    public function sapRequest($method, $endpoint, $request, $islogged = false, $perPage = 1000)
    {
        try {
            if (!$islogged) {
                $login = $this->sapLogin();
                if (isset($login) && isset($login['SessionId'])) {
                    session(['sapSession' => $login['SessionId']]);
                } else {
                    $msg = "SAP " . $login['error']['message']['value'] . '. Please check the server connection configuration.';
                    return array('status' => 'errors', 'message' => $msg, 'status_code' => 401);
                }
            }

            // dd($login);
            if (session()->has('sapSession')) {
                $respn = Http::withHeaders([
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Cookie'        => 'B1SESSION=' . session('sapSession'),
                    'Prefer'        =>  'odata.maxpagesize=' . $perPage //defind page size per request
                ])->withOptions([
                    'verify' => false,
                ])->acceptJson();
                $url    = "https://" . env('SAP_HOST') . ":50000/b1s/v1/" . $endpoint;
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
            } else {
                return array('status' => 'errors', 'message' => 'Oop! Session timeout.', 'status_code' => 401);
            }
        } catch (Exception $e) {
            return array('status' => 'errors', 'message' => $e->getMessage(), 'status_code' => $e->getCode());
        }
    }
    public function sapLogin()
    {
        try {
            $frmUser = array(
                'CompanyDB' =>  env('SAP_DATABASE'),
                'UserName'  =>  env('SAP_USERNAME'),
                'Password'  =>  env('SAP_PASSWORD')
            );
            return Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->withOptions([
                'verify' => false,
            ])->acceptJson()
                ->post("https://" . env('SAP_HOST') . ":50000/b1s/v1/Login", $frmUser)
                ->json();
        } catch (Exception $e) {
            return array('status' => 'errors', 'message' => $e->getMessage(), 'status_code' => $e->getCode());
        }
    }
}
