<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


/*Route::post('help', function (Request $request) {
    $method = $request->method();
    if($method == 'OPTIONS') {
        return response($method, 200)
            ->withHeaders(['Access-Control-Allow-Origin' => 'http://localhost:3000', 'Access-Control-Allow-Credentials' => 'true', 'Access-Control-Request-Headers' => 'content-type'])->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');;
    }

    return response($method, 200)->withHeaders(['Access-Control-Allow-Origin' => 'http://localhost:3000', 'Access-Control-Allow-Credentials' => 'true']);
});*/

Route::middleware('auth:api')->get('auth/me', function (Request $request) {
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
});

Route::middleware('auth:api')->put('profile/status', function (Request $request) {
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
});

Route::get('profile/{id}', function (Request $request) {
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
});

Route::middleware('auth:api')->post('follow/{id}', function (Request $request) {
    $id = $request->id; 
    $uri = explode('/', $request->path());
    $following_id = intval($uri[array_key_last($uri)]);

    $data = DB::select("select following from users where id='$id'");
    $array_following = json_decode($data[0]->following);

    if(in_array($following_id, $array_following)) {
        $response['resultCode'] = 1;
        $response['message'] = 'Already following';
        return response(json_encode($response), 200);
    }
    array_push($array_following, $following_id);
    $following = json_encode($array_following);
    
    DB::update("update users set following='$following' where id='$id'");

    $data = DB::select("select followers from users where id='$following_id'");
    $array_followers = json_decode($data[0]->followers);
    array_push($array_followers, $id);
    $followers = json_encode($array_followers);

    DB::update("update users set followers='$followers' where id='$following_id'");

    $response['resultCode'] = 0;
    return response($response, 200);
});

Route::middleware('auth:api')->delete('follow/{id}', function (Request $request) {
    $id = $request->id; 
    $uri = explode('/', $request->path());
    $following_id = intval($uri[array_key_last($uri)]);

    $data = DB::select("select following from users where id='$id'");
    $array_following = json_decode($data[0]->following);

    if(!in_array($following_id, $array_following)) {
        $response['resultCode'] = 1;
        $response['message'] = 'Error';
        return response(json_encode($response), 200);
    }

    $key = array_search($following_id, $array_following);
    unset($array_following[$key]);
    $array_following = array_values($array_following);
    $following = json_encode($array_following);

    DB::update("update users set following='$following' where id='$id'");

    $data = DB::select("select followers from users where id='$following_id'");
    $array_followers = json_decode($data[0]->followers);
    
    $key = array_search($id, $array_followers);
    unset($array_followers[$key]);
    $array_followers = array_values($array_followers);
    $followers = json_encode($array_followers);

    DB::update("update users set followers='$followers' where id='$following_id'");

    $response['resultCode'] = 0;
    return response($response, 200);
});

Route::post('register', function(Request $request) {
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
    $newUser = DB::insert("insert into users (name, email, password, following, followers) 
        values ('$name','$email', '$hashedPassword', '$following', '$following')");
    
    $response['resultCode'] = 0;
    $response['message'] = 'User has been created';
    return response(json_encode($response), 200);
});

Route::post('login', function(Request $request) {
    /*$method = $request->method();
    if($method == 'OPTIONS') {
        return response('', 200)
            ->withHeaders([
            'Access-Control-Allow-Origin' => 'http://localhost:3000', 
            'Access-Control-Allow-Credentials' => 'true', 
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS']);
    }*/

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
});

Route::middleware('auth:api')->delete('login', function(Request $request) {
    $id = $request->id; 

    $logout = DB::update("update users set api_token = '' where id = '$id'");
    if($logout != 1){
        $response['resultCode'] = 1;
        $response['message'] = 'Error';
        return response(json_encode($response), 400);
    }

    $response['resultCode'] = 0;
    return response(json_encode($response), 200);
});

Route::middleware('auth:api')->get('users', function(Request $request) {
    $id = $request->id; 

    $limit = ($request->limit != '')? $request->limit : 10;
    $page = ($request->page != '')? $request->page : 1;
    $search = $request->search;
    
    $offset = $limit * ($page - 1);

    $data = DB::select("select following from users where id='$id'");
    $array_following = json_decode($data[0]->following);

    $data = DB::select("select id, name, email, status from users where 
        name like '%$search%' and id<>'$id' order by id limit $limit offset $offset"); 

    $count = count($data);
    for($i = 0; $i < $count; $i++) {
        in_array($data[$i]->id, $array_following)? 
            $data[$i]->followed = true: 
            $data[$i]->followed = false;
    };

    $response['items'] = $data;
    $response['count'] = DB::select("select count(*) from users where 
        name like '%$search%' and id<>'$id'")[0]->count;
    return response($response, 200);
});