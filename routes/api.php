<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;







Route::group(['prefix' => 'v1'], function(){
    Route::prefix('/cc')->group(function (){
        Route::prefix('/mhs')->group(function (){
            Route::get('/data', [\App\Http\Controllers\Api\MhsController::class, 'mhsDaftar']);
        });
        Route::prefix('/pegawai')->group(function (){
            Route::get('/data', [\App\Http\Controllers\Api\MhsController::class, 'pegawai']);
        });
        Route::prefix('/keuangan')->group(function (){
            Route::get('/data', [\App\Http\Controllers\Api\MhsController::class, 'keuangan']);
        });
        Route::prefix('/mbkm-bkpl')->group(function (){
            Route::get('/data', [\App\Http\Controllers\Api\MhsController::class, 'bkpl']);
        });
    });
});