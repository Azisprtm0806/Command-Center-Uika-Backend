<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'getProfile']);
});

Route::group(['prefix' => 'v1'], function(){
    Route::prefix('/cc')->group(function (){
        Route::get('/latlong-desa', [\App\Http\Controllers\Api\AkademikController::class, 'latlongDesa']);
        Route::get('/latlong-asal-sekolah', [\App\Http\Controllers\Api\AkademikController::class, 'latlongAsalSekolah']);

        Route::prefix('/mhs')->group(function (){
            Route::get('/total-mhs-daftar', [\App\Http\Controllers\Api\AkademikController::class, 'totalMhsDaftar']);
            Route::get('/total-mhs-diterima', [\App\Http\Controllers\Api\AkademikController::class, 'totalMhsDiterima']);
            Route::get('/total-mhs-peminat', [\App\Http\Controllers\Api\AkademikController::class, 'totalMhsPeminat']);
            Route::get('/mhs-daftar-detail', [\App\Http\Controllers\Api\AkademikController::class, 'mhsDaftarDetail']);
            Route::get('/mhs-peminat-detail', [\App\Http\Controllers\Api\AkademikController::class, 'mhsPeminatDetail']);
            Route::get('/rata-ipk-prodi', [\App\Http\Controllers\Api\AkademikController::class, 'ipkPerProdi']);
            Route::get('/rata-ipk-fakultas', [\App\Http\Controllers\Api\AkademikController::class, 'ipkPerFakultas']);
            Route::get('/lama-lulusan', [\App\Http\Controllers\Api\AkademikController::class, 'lamaLulusan']);
            Route::get('/mhs-provinsi', [\App\Http\Controllers\Api\AkademikController::class, 'mhsPerProvinsi']);
            Route::get('/mhs-diterima-provinsi-fakultas', [\App\Http\Controllers\Api\AkademikController::class, 'mhsdDiterimaProvinsiFakultas']);
            Route::get('/mhs-aktif-non-frs', [\App\Http\Controllers\Api\AkademikController::class, 'mhsAktifNonFrs']);
            Route::get('/mhs-sudah-spp', [\App\Http\Controllers\Api\AkademikController::class, 'mhsSudahBayarSpp']);
            Route::get('/mhs-belum-spp', [\App\Http\Controllers\Api\AkademikController::class, 'mhsBelumBayarSpp']);
            Route::get('/mhs-beasiswa', [\App\Http\Controllers\Api\AkademikController::class, 'MhsPenerimaBeasiswa']);
            Route::get('/mhs-lulus-beasiswa', [\App\Http\Controllers\Api\AkademikController::class, 'mhsLulusBeasiswa']);
            Route::get('/asal-mhs', [\App\Http\Controllers\Api\AkademikController::class, 'AsalMhs']);
            Route::get('/data-newMhs', [\App\Http\Controllers\Api\AkademikController::class, 'dataNewMhs']);
            Route::get('/data-oldMhs', [\App\Http\Controllers\Api\AkademikController::class, 'dataOldMhs']);
            Route::get('/data-asal-sekolah-newMhs', [\App\Http\Controllers\Api\AkademikController::class, 'dataAsalSekolahNewMhs']);
            Route::get('/data-asal-sekolah-oldMhs', [\App\Http\Controllers\Api\AkademikController::class, 'dataAsalSekolahOldMhs']);
            Route::get('/peta-sebaran-desa', [\App\Http\Controllers\Api\AkademikController::class, 'petaSebaranDesaMhs']);
            Route::get('/peta-sebaran-sekolah', [\App\Http\Controllers\Api\AkademikController::class, 'petaSebaranAsalSekolah']);
        });

        Route::prefix('/pegawai')->group(function (){
            Route::get('/total-pengajar-dosen', [\App\Http\Controllers\Api\PegawaiController::class, 'totalTenagaPengajarPerProdi']);
            Route::get('/total-tendik', [\App\Http\Controllers\Api\PegawaiController::class, 'totalTendik']);
            Route::get('/jmlh-pengajar-dosen', [\App\Http\Controllers\Api\PegawaiController::class, 'jmlTenagaPengajarDosen']);
            Route::get('/jafungDosen', [\App\Http\Controllers\Api\PegawaiController::class, 'jafungDosen']);
            Route::get('/struktural', [\App\Http\Controllers\Api\PegawaiController::class, 'struktural']);
            Route::get('/jml-tendik', [\App\Http\Controllers\Api\PegawaiController::class, 'jmlTendik']);
        });

        Route::prefix('/keuangan')->group(function (){
            Route::get('/tunggakan-mhs', [\App\Http\Controllers\Api\KeuanganController::class, 'tunggakanMhs']);
            Route::get('/tunggakan-mhs-spp', [\App\Http\Controllers\Api\KeuanganController::class, 'tunggakanMhsSpp']);
            Route::get('/tunggakan-mhs-sks-ujian', [\App\Http\Controllers\Api\KeuanganController::class, 'tunggakanMhsSksUjian']);
        });

        Route::prefix('/mbkm-bkpl')->group(function (){
            Route::get('/pelaksanaan', [\App\Http\Controllers\Api\MbkmBkplController::class, 'pelaksanaan']);
            Route::get('/total-pelaksanaan', [\App\Http\Controllers\Api\MbkmBkplController::class, 'totalJmlPelaksanaan']);
        });

        Route::prefix('/chart')->group(function (){
            Route::get('/mhs-chart', [\App\Http\Controllers\Api\ChartController::class, 'mhsChart']);
            Route::get('/tunggakanPerProdi', [\App\Http\Controllers\Api\ChartController::class, 'jmlTunggakanPerProdi']);
            Route::get('/pegawai-chart', [\App\Http\Controllers\Api\ChartController::class, 'kepegawain']);
            Route::get('/struktural-chart', [\App\Http\Controllers\Api\ChartController::class, 'strukturalChart']);
            Route::get('/mbkmbkpl', [\App\Http\Controllers\Api\ChartController::class, 'jmlMbkmBkpl']);
            Route::get('/beasiswa-chart', [\App\Http\Controllers\Api\ChartController::class, 'beasiswaChart']);
            Route::get('/mhs-spp-chart', [\App\Http\Controllers\Api\ChartController::class, 'mhsSppChart']);
            Route::get('/ipk-chart', [\App\Http\Controllers\Api\ChartController::class, 'chartIpk']);
            Route::get('/lama-lulusan-chart', [\App\Http\Controllers\Api\ChartController::class, 'chartLamaLulusan']);
            Route::get('/jafung-chart', [\App\Http\Controllers\Api\ChartController::class, 'chartJafung']);
        });
    });
});