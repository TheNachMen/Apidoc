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

    Route::get('/documentos',[DocumentoController::class,'index']);

    Route::post('/documentosStore',[DocumentoController::class,'store']);

    Route::put('/documentos/{id}',[DocumentoController::class,'update']);

    Route::patch('/documentosEstado/{id}',[DocumentoController::class,'cambiarEstado']);

    Route::get('/documentosShow/{id}',[DocumentoController::class,'show']);

    Route::get('/trazabilidaddocindex', [DocumentoController::class,'trazabilidadindex']);
