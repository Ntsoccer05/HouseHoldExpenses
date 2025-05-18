<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Filament パスを config から取得して、ルーティングから除外する
$filamentPath = ltrim(config('filament.path'), '/'); // 先頭スラッシュを除去

Route::get('/{any}', function () {
    return view('index');
})->where('any', "^(?!{$filamentPath}).*");