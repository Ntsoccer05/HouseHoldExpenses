<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;

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



Route::get(config('filament.path'), function () {
    try {
        Log::info('Accessed /admin', [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'headers' => request()->headers->all(),
        ]);

        return redirect(config('filament.path')); // Filament のトップページへ
    } catch (\Throwable $e) {
        Log::error('Error occurred while accessing /admin', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
        ]);

        abort(500, 'Internal Server Error');
    }
});

Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');