<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\BloodBags\BloodBagController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserListController;
use App\Http\Controllers\Api\Donor\DonationHistory\DonationHistoryController;
use App\Http\Controllers\Api\Donor\BloodJourney\BloodJourneyController;


/*
|--------------------------------------------------------------------------
| Address
|--------------------------------------------------------------------------
*/

Route::controller(AddressController::class)->group(function () {

    Route::get('/address/get-regions', 'getRegions')->name('get-regions');
    Route::post('/address/get-provinces/{regCode?}', 'getProvinces')->name('get-provinces');
    Route::post('/address/get-municipalities/{provCode?}', 'getMunicipalities')->name('get-municipalities');
    Route::post('/address/get-barangays/{citymunCode?}', 'getBarangays')->name('get-barangays');

});


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::group([

    'middleware' => 'api',
    'prefix' => 'auth:sanctum'

], function ($router) { 
    Route::post('/login', [AuthController::class, 'login']);    
    Route::post('/logout', [AuthController::class, 'logout']);    

});


/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/
Route::group(
    [
        'prefix' => config('app.apiversion'),
        'as' => config('app.apiversion'),
    ],
    function () {
        Route::post('/register-step1',[RegistrationController::class, 'saveStep1']);
        Route::post('/register-step2',[RegistrationController::class, 'saveStep2']);

});



/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:sanctum','admin']], function () {

    Route::post('/add-bloodbag', [BloodBagController::class, 'store']);
    Route::put('/update-bloodbag', [BloodBagController::class, 'update']);
    Route::get('/get-user-details', [UserListController::class, 'getUserDetails']);

});




/*
|--------------------------------------------------------------------------
| Donor API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/get-history', [DonationHistoryController::class, 'donationHistory']);
    Route::get('/get-blood-journey', [BloodJourneyController::class, 'bloodJourney']);


});