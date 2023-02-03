<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('home');
})->name('prescription');;

Route::get('/nurse_review', function () {
    return view('nurse_review');
})->name('nurse_review');

Route::get('/pharmacy_review', function () {
    return view('pharmacy_review');
})->name('pharmacy_review');

Route::get('/rendered_prescription', function () {
    return view('rendered_prescription');
})->name('rendered_prescription');

Route::get('/returned_prescription', function () {
    return view('returned_prescription');
})->name('returned_prescription');


Route::get('/patient/get', [\App\Http\Controllers\PatientController::class, 'getPatient']);

Route::get('/medicine/get', [\App\Http\Controllers\MedicineController::class, 'getMedicine']);
Route::get('/medicine/get/{keyword}', [\App\Http\Controllers\MedicineController::class, 'searchMedicine']);
Route::get('/user/get/doctors', [\App\Http\Controllers\UserController::class, 'getDoctors']);

#DOCTORS ROUTES
Route::get('/prescription/get', [\App\Http\Controllers\PrescriptionController::class, 'getPrescription']);
Route::get('/prescription/get/{id}', [\App\Http\Controllers\PrescriptionController::class, 'getPrescriptionDetails']);
Route::get('/prescription/get/logs/{id}', [\App\Http\Controllers\PrescriptionController::class, 'getPrescriptionLogs']);

#NURSES ROUTES
Route::get('/nurse_review/get', [\App\Http\Controllers\NurseReviewController::class, 'getPrescriptions']);
#PHARMACIST ROUTES
Route::get('/pharmacy_review/get', [\App\Http\Controllers\PharmacyReviewController::class, 'getPrescriptions']);
#RENDERED PRESCRIPTIONS ROUTES
Route::get('/rendered_prescription/get', [\App\Http\Controllers\RenderedPrescriptionController::class, 'getPrescriptions']);
#RETURNED PRESCRIPTIONS ROUTES
Route::get('/returned_prescription/get', [\App\Http\Controllers\ReturnedPrescriptionController::class, 'getPrescriptions']);
Route::get('/patient/get/details/{id}', [\App\Http\Controllers\PatientController::class, 'getPatientDetails']);
//Route::get('/prescription/insert_prescription', [\App\Http\Controllers\PrescriptionController::class, 'insertPrescription']);

