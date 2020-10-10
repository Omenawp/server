<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
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
    }

    public function logout(Request $request) {
        $id = $request->id;

        $logout = DB::update("update users set api_token = '' where id = '$id'");
        if($logout != 1) {
            $response['resultCode'] = 1;
            $response['message'] = 'Error';
            return response(json_encode($response), 400);
        }

        $response['resultCode'] = 0;
        return response(json_encode($response), 200);
    }

    public function register(Request $request) {
        $name = trim($request->name);
        $email = trim($request->email);
        $password = trim($request->password);

        if($name == ''){
            $response['resultCode'] = 1;
            $response['message'] = 'Name is missing';
            return response(json_encode($response), 200);
        }

        if($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            $response['resultCode'] = 1;
            $response['message'] = 'Email is incorrect';
            return response(json_encode($response), 200);
        }

        if(strlen($password) < 6) {
            $response['resultCode'] = 1;
            $response['message'] = 'Password lenght must be more than 6 symbols';
            return response(json_encode($response), 200);
        }

        $isUser = DB::select("select * from users where email = '$email'");
        if(!empty($isUser)){
            $response['resultCode'] = 1;
            $response['message'] = 'This user already exists';
            return response(json_encode($response), 200);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $following = json_encode(array());
        $photos = json_encode(['large' => null, 'small' => null]);
        
        $newUser = DB::insert("insert into users (name, email, password, following, followers, photos)
            values ('$name','$email', '$hashedPassword', '$following', '$following', '$photos')");

        $response['resultCode'] = 0;
        $response['message'] = 'User has been created';
        return response(json_encode($response), 200);
    }
}
