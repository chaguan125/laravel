<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\couponsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/coupons', function () {
    return view('coupons',['name' => "名稱",'voucher_type'=>'类型','region'=>'来源']);
});

Route::any('/coupons/post','couponsController@post');

Route::any('/coupons/file','couponsController@file');

Route::any('/coupons/copy','couponsController@copy');

Route::any('/test/redis','testController@redis');

Route::any('/test/check','testController@check');

Route::any('/test/trsubmit','testController@trsubmit');

Route::any('/test/collect','testController@collect');

Route::any('/test/store','testController@store');

Route::any('/test/dbDate','testController@dbDate');

Route::any('player/list', 'PlayerController@list');

Route::any('player/add', 'PlayerController@add');

Route::any('player/prize-list', 'PlayerController@prizeList');

Route::any('player/article-add', 'PlayerController@articleAdd');

Route::any('player/article-list', 'PlayerController@articleList');

Route::any('player/article-fabulous', 'PlayerController@articleFabulous');

Route::any('player/article-show-detail', 'PlayerController@showArticleDetail');

Route::any('player/article-info', 'PlayerController@showArticleInfo');



