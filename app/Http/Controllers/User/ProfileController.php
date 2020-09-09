<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function me(Request $request) {
        $id = $request->id;
        $user = DB::select("select id, email, name from users where id = '$id'");
    
        if(empty($user)){
            $response['resultCode'] = 1;
            $response['message'] = 'Unknown error';
            return response(json_encode($response), 500);
        }
    
        $response['data'] = (array) $user[0];
        $response['resultCode'] = 0;
        return response($response, 200);
    }

    public function changeStatus(Request $request) {
        $id = $request->id;
        $status = trim($request->status);

        $user = DB::update("update users set status='$status' where id='$id'");

        if(empty($status)){
            $response['resultCode'] = 1;
            $response['message'] = 'Unknown error';
            return response(json_encode($response), 400);
        }

        $response['resultCode'] = 0;
        return response(json_encode($response), 200);
    }

    public function getProfile(Request $request) {
        $uri = explode('/', $request->path());
        $id = $uri[array_key_last($uri)];

        $user = DB::select("select id, name, email, status from users where id = '$id'");

        if(empty($user)){
            $response['resultCode'] = 1;
            $response['message'] = 'Unknown error';
            return response(json_encode($response), 400);
        }

        $response['data'] = (array) $user[0];
        $response['resultCode'] = 0;
        return response(json_encode($response), 200);
    }
}
