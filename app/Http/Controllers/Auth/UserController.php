<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $email = trim($request->email);
        $password = trim($request->password);

        if($email == '' || $password == ''){
            $response['resultCode'] = 1;
            $response['message'] = 'Email or password is incorrect';
            return response(json_encode($response), 200);
        }

        $user = DB::select("select * from users where email = '$email'");

        if(empty($user)){
            $response['resultCode'] = 1;
            $response['message'] = 'Email or password is incorrect';
            return response(json_encode($response), 200);
        }

        $user = (array) $user[0];

        if(!password_verify($password, $user['password'])) {
            $response['resultCode'] = 1;
            $response['message'] = 'Email or password is incorrect';
            return response(json_encode($response), 200);
        }

        $api_token = Str::random(60);
        $user = DB::update("update users set api_token='$api_token' where email='$email'");

        $token = encrypt($api_token);

        $response['resultCode'] = 0;
        $response['token'] = $token;
        return response($response, 200);
        //->withHeaders(['Access-Control-Allow-Origin' => 'http://localhost:3000', 'Access-Control-Allow-Credentials' => 'true']);

    }
}
