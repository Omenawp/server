<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::post('register', 'Auth\AuthController@register');
Route::post('login', 'Auth\AuthController@login');
Route::middleware('auth:api')->delete('login', 'Auth\AuthController@logout');

Route::middleware('auth:api')->get('auth/me', 'User\ProfileController@me'); 
Route::middleware('auth:api')->put('profile/status', 'User\ProfileController@changeStatus');
Route::middleware('auth:api')->post('profile/photo', 'User\ProfileController@updatePhoto'); //PUT doesn't work
Route::middleware('auth:api')->delete('profile/photo', 'User\ProfileController@deletePhoto');
Route::get('profile/{id}', 'User\ProfileController@getProfile');

Route::middleware('auth:api')->post('follow/{id}', 'User\UsersController@follow');
Route::middleware('auth:api')->delete('follow/{id}', 'User\UsersController@unfollow');
Route::middleware('auth:api')->get('users', 'User\UsersController@getUsers');

Route::middleware('auth:api')->post('post/preload', 'Posts\PostsController@preload');
Route::middleware('auth:api')->post('post/clear', 'Posts\PostsController@clear');
Route::middleware('auth:api')->post('post/add', 'Posts\PostsController@addPost');
Route::middleware('auth:api')->delete('post/{id_post}', 'Posts\PostsController@deletePost');

Route::middleware('auth:api')->post('like/{id}', 'Posts\PostsController@likePost');
Route::middleware('auth:api')->delete('like/{id}', 'Posts\PostsController@dislikePost');

Route::middleware('auth:api')->get('posts', 'Posts\PostsController@allPosts');

