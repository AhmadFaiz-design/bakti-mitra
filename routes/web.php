<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin', function () {
    return view('admin.dashboard');
});
Route::get('/login', function () {
    return view('auth.login');
});
Route::get('/koordinator', function () {
    return view('koordinator.dashboard');
});
Route::get('/mitra', function () {
    return view('mitra.dashboard');
});

