<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Closure;

class Authenticate extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('authorization');
        $check = explode(" ", $header);
        if(count($check) == 1) {
            $response['resultCode'] = 1;
            $response['message'] = 'Need to login';
            return response(json_encode($response), 200);
        }

        $token = $check[1];
        $api_token = decrypt($token);

        $user = DB::select("select id from users where api_token = '$api_token'");

        if(empty($user)){
            $response['resultCode'] = 1;
            $response['message'] = 'Need to login';
            return response(json_encode($response), 200);
        }
        $user = (array) $user[0];

        $request["id"] = $user["id"];

        return $next($request);
    }
}
