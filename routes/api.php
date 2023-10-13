<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function(){
    Route::prefix('/cc')->group(function (){
        Route::prefix('/mhs')->group(function (){
            Route::get('/mhs-daftar', [\App\Http\Controllers\Api\MhsController::class, 'mhsDaftar']);
            Route::get('/mhs-diterima', [\App\Http\Controllers\Api\MhsController::class, 'mhsDiterima']);
            Route::get('/mhs-provinsi', [\App\Http\Controllers\Api\MhsController::class, 'mhsPerProvinsi']);
            Route::get('/mhs-diterima-provinsi-fakultas', [\App\Http\Controllers\Api\MhsController::class, 'mhsdDiterimaProvinsiFakultas']);
            Route::get('/mhs-aktif-non-frs', [\App\Http\Controllers\Api\MhsController::class, 'mhsAktifNonFrs']);
            Route::get('/mhs-sudah-spp', [\App\Http\Controllers\Api\MhsController::class, 'mhsSudahBayarSpp']);
            Route::get('/mhs-belum-spp', [\App\Http\Controllers\Api\MhsController::class, 'mhsBelumBayarSpp']);
            Route::get('/mhs-beasiswa', [\App\Http\Controllers\Api\MhsController::class, 'MhsPenerimaBeasiswa']);
            Route::get('/asal-mhs', [\App\Http\Controllers\Api\MhsController::class, 'AsalMhs']);
        });

        Route::prefix('/pegawai')->group(function (){
            Route::get('/jmlh-pengajar-prodi', [\App\Http\Controllers\Api\PegawaiController::class, 'jmlTenagaPengajarPerProdi']);
            Route::get('/jmlh-kependidikan', [\App\Http\Controllers\Api\PegawaiController::class, 'jmlTenagaKependidikan']);
        });

        Route::prefix('/keuangan')->group(function (){
            Route::get('/tunggakan-mhs-prodi', [\App\Http\Controllers\Api\KeuanganController::class, 'tunggakanMhsPerProdi']);
        });

        Route::prefix('/mbkm-bkpl')->group(function (){
            Route::get('/pelaksanaan', [\App\Http\Controllers\Api\MbkmBkplController::class, 'pelaksanaan']);
        });

        Route::prefix('/chart')->group(function (){
            Route::get('/mhs-daftar', [\App\Http\Controllers\Api\ChartController::class, 'mhsDaftar']);
        });
    });
});