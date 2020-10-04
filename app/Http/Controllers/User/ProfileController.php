<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        $user = DB::select("select id, name, email, status, photos from users where id = '$id'");
        $user[0]->photos = json_decode($user[0]->photos);

        if(empty($user)){
            $response['resultCode'] = 1;
            $response['message'] = 'Unknown error';
            return response(json_encode($response), 400);
        }

        $response['data'] = (array) $user[0];
        $response['resultCode'] = 0;
        return response(json_encode($response), 200);
    }

    public function updatePhoto(Request $request) {
        $id = $request->id;
        $extension = $request->file('photo')->extension();
        $direct = $id . '/profile/';
        
        Storage::disk('public')->deleteDirectory($direct);

        $path = Storage::disk('public')->putFile($direct, $request->file('photo'));
        $photos['large'] = Storage::disk('public')->url($path);

        $name = Str::random(40) . '.' . $extension;
        Image::make($request->file('photo'))->fit(200, 200)->save('storage/'. $direct . $name);
        $photos['small'] = Storage::disk('public')->url($direct . $name);
    
        $files = json_encode($photos);
        $user = DB::update("update users set photos='$files' where id='$id'");

        $response['resultCode'] = 0;
        $response['data'] = $photos;
        return response($response, 200);
    }

    public function deletePhoto(Request $request) {
        $id = $request->id;

        $photos = ['large' => null, 'small' => null];
        $files = json_encode($photos);
        $delete = DB::update("update users set photos='$files' where id='$id'");

        Storage::disk('public')->deleteDirectory($id . '/profile');

        $response['resultCode'] = 0;
        $response['data'] = $photos;
        return response(json_encode($response), 200);
    }
}
