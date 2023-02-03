<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/insert_patient', [\App\Http\Controllers\PatientController::class, 'store']);
Route::post('/insert_prescription', [\App\Http\Controllers\PrescriptionController::class, 'insertPrescription']);

Route::post('/approve_prescription', [\App\Http\Controllers\PrescriptionController::class, 'approvePrescription']);
Route::post('/reject_prescription', [\App\Http\Controllers\PrescriptionController::class, 'rejectPrescription']);
Route::post('/render_prescription', [\App\Http\Controllers\PharmacyReviewController::class, 'renderPrescription']);
Route::post('/update_prescription', [\App\Http\Controllers\ReturnedPrescriptionController::class, 'updatePrescription']);

