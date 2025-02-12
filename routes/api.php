<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

    Route::get('/documentos',[DocumentoController::class,'index'])->name('documentos.index');

    Route::post('/documentosStore',[DocumentoController::class,'store'])->name('documentos.store');

    Route::put('/documentos/{id}',[DocumentoController::class,'update'])->name('documentos.update');

    Route::patch('/documentosEstado/{id}',[DocumentoController::class,'cambiarEstado'])->name('documentos.estado');

    Route::get('/documentosShow/{id}',[DocumentoController::class,'show'])->name('documentos.show');
