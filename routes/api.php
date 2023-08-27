<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\BloodBags\BloodBagController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserListController;


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

Route::controller(AddressController::class)->group(function () {

    Route::get('/address/get-regions', 'getRegions')->name('get-regions');
    Route::post('/address/get-provinces/{regCode?}', 'getProvinces')->name('get-provinces');
    Route::post('/address/get-municipalities/{provCode?}', 'getMunicipalities')->name('get-municipalities');
    Route::post('/address/get-barangays/{citymunCode?}', 'getBarangays')->name('get-barangays');

});

//authentication
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth:sanctum'

], function ($router) { 
    Route::post('/login', [AuthController::class, 'login']);    
    Route::post('/logout', [AuthController::class, 'logout']);    

});


//registration
Route::group(
    [
        'prefix' => config('app.apiversion'),
        'as' => config('app.apiversion'),
    ],
    function () {
        Route::post('/register-step1',[RegistrationController::class, 'saveStep1']);
        Route::post('/register-step2',[RegistrationController::class, 'saveStep2']);
});

//admin api routes
Route::group(['middleware' => ['auth:sanctum','admin']], function () {

    Route::post('/add-bloodbag', [BloodBagController::class, 'store']);
    Route::put('/update-bloodbag', [BloodBagController::class, 'update']);
    Route::get('/get-user-details', [UserListController::class, 'getUserDetails']);

});