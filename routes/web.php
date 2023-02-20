<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

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
    if (Auth::check()) return redirect()->route('prescription');
    return view('login');
})->name('login');


Route::get('/logout', function () {
    Auth::logout();
    Session::flush();
    return redirect()->route('login');
})->name('logout');

Route::post('/authenticate', [\App\Http\Controllers\UserController::class, 'authenticate'])->name('authenticate');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home', function () {
        return view('home');
    })->name('prescription');;

    Route::get('/nurse_review', function () {
        return view('nurse_review');
    })->name('nurse_review');

    Route::get('/icc_review', function () {
        return view('icc_review');
    })->name('icc_review');

    Route::get('/pharmacy_review', function () {
        return view('pharmacy_review');
    })->name('pharmacy_review');

    Route::get('/returned_meds', function () {
        return view('returned_meds');
    })->name('returned_meds');

    Route::get('/issuances', function () {
        return view('issuances');
    })->name('issuances');

    Route::get('/ams_summary', function () {
        return view('ams_summary');
    })->name('ams_summary');

    Route::get('/rendered_prescription', function () {
        return view('rendered_prescription');
    })->name('rendered_prescription');

    Route::get('/returned_prescription', function () {
        return view('returned_prescription');
    })->name('returned_prescription');

    Route::get('/nurse_admin', function () {
        return view('nurse_admin');
    })->name('nurse_admin');

    Route::get('/medicine', function () {
        return view('medicine');
    })->name('medicine');

    #APIS
    Route::post('/insert_patient', [\App\Http\Controllers\PatientController::class, 'store'])->name('insert_patient');
    Route::post('/insert_medicine', [\App\Http\Controllers\MedicineController::class, 'store'])->name('insert_medicine');
    Route::post('/insert_prescription', [\App\Http\Controllers\PrescriptionController::class, 'insertPrescription'])->name('insert_prescription');
    Route::post('/approve_prescription', [\App\Http\Controllers\PrescriptionController::class, 'approvePrescription'])->name('approve_prescription');
    Route::post('/reject_prescription', [\App\Http\Controllers\PrescriptionController::class, 'rejectPrescription'])->name('reject_prescription');
    Route::post('/render_prescription', [\App\Http\Controllers\PharmacyReviewController::class, 'renderPrescription'])->name('render_prescription');
    Route::post('/update_prescription', [\App\Http\Controllers\ReturnedPrescriptionController::class, 'updatePrescription'])->name('update_prescription');
    Route::post('/update_nurse_station', [\App\Http\Controllers\NurseStationController::class, 'insertNurseStation'])->name('update_nurse_station');
    Route::post('/return_meds', [\App\Http\Controllers\PrescriptionController::class, 'returnMeds'])->name('return_meds');
    Route::put('/accept_meds', [\App\Http\Controllers\ReturnedMedsController::class, 'receiveMedicine'])->name('accept_meds');

    Route::get('/homis_patient/get/{keyword}', [\App\Http\Controllers\PatientController::class, 'searchHomisPatient']);

    Route::get('/patient/get', [\App\Http\Controllers\PatientController::class, 'getPatient']);
    Route::get('/medicine/get/master', [\App\Http\Controllers\MedicineController::class, 'getMedicine']);
    Route::get('/medicine/get', [\App\Http\Controllers\MedicineController::class, 'getMedicineList']);
    Route::get('/medicine/get/issuance', [\App\Http\Controllers\PrescribedMedicineController::class, 'getIssuancesSummary']);
    Route::get('/user/get/doctors', [\App\Http\Controllers\UserController::class, 'getDoctors']);
    Route::get('/user/get/nurses', [\App\Http\Controllers\UserController::class, 'getNurses']);
    Route::get('/ams/summary', [\App\Http\Controllers\PrescriptionController::class, 'getAmsSummary']);
    #DOCTORS ROUTES
    Route::get('/prescription/get', [\App\Http\Controllers\PrescriptionController::class, 'getPrescription']);
    Route::get('/patient/get/concurrent', [\App\Http\Controllers\PrescriptionController::class, 'getConcurrentTherapy'])->name('getConcurrentTherapy');
    Route::get('/prescription/get/{id}', [\App\Http\Controllers\PrescriptionController::class, 'getPrescriptionDetails']);
    Route::get('/prescription/get/logs/{id}', [\App\Http\Controllers\PrescriptionController::class, 'getPrescriptionLogs']);
    #NURSES ROUTES
    Route::get('/nurse_review/get', [\App\Http\Controllers\NurseReviewController::class, 'getPrescriptions']);
    #ICC ROUTES
    Route::get('/icc_review/get', [\App\Http\Controllers\IccReviewController::class, 'getPrescriptions']);
    #PHARMACIST ROUTES
    Route::get('/pharmacy_review/get', [\App\Http\Controllers\PharmacyReviewController::class, 'getPrescriptions']);
    #RENDERED PRESCRIPTIONS ROUTES
    Route::get('/rendered_prescription/get', [\App\Http\Controllers\RenderedPrescriptionController::class, 'getPrescriptions']);
    #RETURNED PRESCRIPTIONS ROUTES
    Route::get('/returned_prescription/get', [\App\Http\Controllers\ReturnedPrescriptionController::class, 'getPrescriptions']);
    Route::get('/patient/get/details/{id}', [\App\Http\Controllers\PatientController::class, 'getPatientDetails']);
    #RETURNED MEDS ROUTES
    Route::get('/returned_meds/get', [\App\Http\Controllers\ReturnedMedsController::class, 'getReturnedMeds']);

    //Route::get('/prescription/insert_prescription', [\App\Http\Controllers\PrescriptionController::class, 'insertPrescription']);
});



