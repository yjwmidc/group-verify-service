<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP8!';
});

Route::get('hello/:name', 'index/hello');

//验证路由
Route::group('verify', function () {
    Route::get('create', 'GeetestController/create');
    Route::get('page', 'GeetestController/page');
    Route::post('callback', 'GeetestController/callback');
    Route::post('check', 'GeetestController/check');
    Route::get('clean', 'GeetestController/clean');
});
