<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function follow(Request $request) {
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
    }

    public function unfollow(Request $request) {
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
    }

    public function getUsers(Request $request) {
        $id = $request->id;

        $limit = ($request->limit != '')? $request->limit : 10;
        $page = ($request->page != '')? $request->page : 1;
        $search = $request->search;

        $offset = $limit * ($page - 1);

        $data = DB::select("select following from users where id='$id'");
        $array_following = json_decode($data[0]->following);

        $data = DB::select("select id, name, email, status, photos from users where
            name like '%$search%' and id<>'$id' order by id limit $limit offset $offset");


        $count = count($data);
        for($i = 0; $i < $count; $i++) {
            $data[$i]->photos = json_decode($data[$i]->photos);
            in_array($data[$i]->id, $array_following)?
                $data[$i]->followed = true:
                $data[$i]->followed = false;
        };

        $response['items'] = $data;
        $response['count'] = DB::select("select count(*) from users where
            name like '%$search%' and id<>'$id'")[0]->count;
        return response($response, 200);
    }
}
