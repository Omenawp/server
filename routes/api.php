<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::post('register', 'Auth\AuthController@register');
Route::post('login', 'Auth\AuthController@login');
Route::middleware('auth:api')->delete('login', 'Auth\AuthController@logout');

Route::middleware('auth:api')->get('auth/me', 'User\ProfileController@me'); 
Route::middleware('auth:api')->put('profile/status', 'User\ProfileController@changeStatus');
Route::get('profile/{id}', 'User\ProfileController@getProfile');

Route::middleware('auth:api')->post('follow/{id}', 'User\UsersController@follow');
Route::middleware('auth:api')->delete('follow/{id}', 'User\UsersController@unfollow');
Route::middleware('auth:api')->get('users', 'User\UsersController@getUsers');
