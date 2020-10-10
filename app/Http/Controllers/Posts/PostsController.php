<?php

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PostsController extends Controller
{
    protected function getLastKey($url) {
        $url = explode('/', $url);
        return $url[array_key_last($url)];
    }
    public function preload(Request $request) {
        $extension = $request->file('pic')->extension();

        if(!($extension == 'jpeg' || $extension == 'png')) {
            $response['resultCode'] = 1;
            $response['meaasge'] = 'File type should be jpeg or png';
            return response($response, 200);
        }

        $path = Storage::disk('public')->putFile('temp', $request->file('pic'));
        $response['url'] = Storage::disk('public')->url($path);

        $response['resultCode'] = 0;
        return response($response, 200);   
    }

    public function clear(Request $request) {
        $name = $this->getLastKey($request->url);
        Storage::disk('public')->delete('temp/' . $name);

        $response['resultCode'] = 0;
        return response($response, 200);
    }

    public function addPost(Request $request) {
        $id = $request->id;
        $text = $request->text;
        $url = $request->url;
        $today = date("Y-m-d H:i:s"); 

        preg_match_all('/\#\w+/', $text, $out);
        $tegs = json_encode($out[0]);

        if($url === null & $text === null) {
            $response['resultCode'] = 1;
            $response['message'] = 'All fields are empty';
            return response($response, 200);
        }

        if($url !== null) {
            $name = $this->getLastKey($url);
            Storage::disk('public')->move('temp/'. $name, $id . '/posts/' . $name);
            $url = Storage::disk('public')->url($id . '/posts/' . $name);
        }

        $json = json_encode([]);

        DB::insert("insert into posts (id_user, added_on, text, photo, likes, tegs)  
            values ('$id', '$today', '$text', '$url', '$json', '$tegs')");
        
        $response['resultCode'] = 0;
        return response($response, 200);

    }

    public function deletePost(Request $request) { // id post
        $id = $request->id;
        $id_post = $request->id_post;

        $res = DB::select("select id, photo from posts where id='$id_post'");
        
        if($res == null) {
            $response['resultCode'] = 1;
            $response['message'] = 'This post doesn\'t exists' ;
            return response($response, 200);
        }

        $photo = $res[0]->photo;
        
        if($photo != null) {
            $name = $this->getLastKey($photo);
            Storage::disk('public')->delete($id . '/posts/' . $name);
        }
        
        $delete = DB::delete("delete from posts where id='$id_post'");

        $response['resultCode'] = 0;
        return response($response, 200);
    }

    public function likePost(Request $request) {
        $id = $request->id;
        $id_post = $this->getLastKey($request->path());

        $likes = json_decode(DB::select("select likes from posts where id='$id_post'")[0]->likes);
        array_push($likes, $id);

        $likes = json_encode($likes);
        $req = DB::update("update posts set likes='$likes' where id='$id_post'");

        if($req != 1) {
            $response['resultCode'] = 1;
            return response($response, 200);
        }

        $response['resultCode'] = 0;
        return response($response, 200);
    }

    public function dislikePost(Request $request) {
        $id = $request->id;
        $id_post = $this->getLastKey($request->path());

        $likes = json_decode(DB::select("select likes from posts where id='$id_post'")[0]->likes);
        $key = array_search($id, $likes);
        unset($likes[$key]);

        $likes = json_encode($likes);
        $req = DB::update("update posts set likes='$likes' where id='$id_post'");

        if($req != 1) {
            $response['resultCode'] = 1;
            return response($response, 200);
        }

        $response['resultCode'] = 0;
        return response($response, 200);

    }

    public function allPosts(Request $request) {
        $id_current = $request->id;
        $id = $request->id_post;

        $limit = ($request->limit != '')? $request->limit : 10;
        $page = ($request->page != '')? $request->page : 1;
        $offset = $limit * ($page - 1);

        $request = ($id === 5) ? "" : "where id_user='$id'";
        $data = DB::select("select * from posts " . $request . " order by added_on DESC limit $limit offset $offset");

        foreach($data as $post) {
            $likes = json_decode($post->likes);
            $post->likes = count($likes);
            $post->like = in_array($id_current, $likes);
            $post->tegs = json_decode($post->tegs);
        }

        $response['resultCode'] = 0;
        $response['data'] = $data;
        $response['count'] = DB::select("select count(*) from posts " . $request )[0]->count;

        return response($response, 200);
    }
}
