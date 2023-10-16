<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\AuditTrailController;
use App\Http\Controllers\Api\Admin\BloodBags\BloodBagController;
use App\Http\Controllers\Api\Admin\Dashboard\DashboardController as DashboardDashboardController;
use App\Http\Controllers\Api\Admin\DonorList\DonorController;
use App\Http\Controllers\Api\Admin\PostApproval\PostApprovalController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserListController;
use App\Http\Controllers\Api\Donor\DonationHistory\DonationHistoryController;
use App\Http\Controllers\Api\Donor\BloodJourney\BloodJourneyController;
use App\Http\Controllers\Api\Donor\Dashboard\DashboardController;
use App\Http\Controllers\Api\Donor\DonorPost\DonorPostController;
use App\Http\Controllers\Api\Admin\Inventory\InventoryController;
use App\Http\Controllers\Api\Donor\Profile\ProfileController;
use App\Http\Controllers\Api\Admin\Settings\SettingsController;

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
    Route::post('/add-bledby', [BloodBagController::class, 'addBledBy']);
    Route::post('/add-venue', [BloodBagController::class, 'addVenue']);
    Route::get('/get-bledby-and-venue', [BloodBagController::class, 'getBledByAndVenue']);

    Route::get('/get-collected-bloodbags', [BloodBagController::class, 'collectedBloodBag']);
    Route::post('/filter-collected-bloodbags', [BloodBagController::class, 'filterBloodTypeCollectedBloodBag']);
    Route::post('/search-collected-bloodbag', [BloodBagController::class, 'searchCollectedBloodBag']);
    Route::get('/export-pdf-collected-bloodbags', [BloodBagController::class, 'exportBloodBagAsPdf']);
    Route::put('/edit-bloodbag', [BloodBagController::class, 'editBloodBag']);
    Route::delete('/remove-bloodbag', [BloodBagController::class, 'removeBlood']);
    Route::post('/move-to-defferal', [UserListController::class, 'moveToDeferral']);
    Route::get('/get-temporary-defferal', [UserListController::class, 'getTemporaryDeferral']);
    Route::get('/get-permanent-defferal', [UserListController::class, 'getPermanentDeferral']);
    Route::put('/edit-user-details', [UserListController::class, 'editUserDetails']);
    Route::post('/approve-post', [PostApprovalController::class, 'approvePost']);
    Route::post('/cancel-approve-post', [PostApprovalController::class, 'cancelApprovedPost']);
    Route::get('/get-audit-trail', [AuditTrailController::class, 'getAuditTrail']);
    Route::post('/add-to-inventory', [InventoryController::class, 'storedInInventory']);
    Route::post('/bulk-move-to-inventory', [InventoryController::class, 'multipleMoveToInventory']);
    Route::get('/get-stocks', [InventoryController::class, 'getStocks']);
    Route::post('/filter-stocks', [InventoryController::class, 'filterBloodTypeStocks']);
    Route::post('/search-stocks', [InventoryController::class, 'searchStocks']);
    Route::get('/get-deferral-bloodbags', [InventoryController::class, 'getTempDeferralBloodBag']);
    Route::post('/filter-deferral-bloodbags', [InventoryController::class, 'filterBloodTypeTempDeferral']);
    Route::get('/get-permanent-bloodbags', [InventoryController::class, 'getPermaDeferralBloodBag']);
    Route::post('/filter-permanent-bloodbags', [InventoryController::class, 'filterBloodTypePermaDeferral']);
    Route::post('/move-back-to-collected', [InventoryController::class, 'moveToCollected']);
    Route::get('/get-expired-blood', [InventoryController::class, 'expiredBlood']);
    Route::post('/filter-expired', [InventoryController::class, 'filterBloodTypeExp']);
    Route::post('/dispose-blood', [InventoryController::class, 'disposeBlood']);
    Route::get('/export-pdf-user-details', [UserListController::class, 'exportUserDetailsAsPdf']);
    Route::get('/get-donor-list', [DonorController::class, 'donorList']);
    Route::post('/search-donor', [DonorController::class, 'searchDonor']);
    Route::get('/export-pdf-donor-list', [DonorController::class, 'exportDonorListAsPdf']);
    Route::post('/create-security-pin', [SettingsController::class, 'createSecurityPin']);
    Route::post('/check-security-pin', [SettingsController::class, 'checkSecurityPin']);

    Route::get('/dashboard-get-stocks',[DashboardDashboardController::class, 'getDashboardStock']);
    Route::get('/dashboard-get-quota',[DashboardDashboardController::class, 'getQuota']);
    Route::get('/dashboard-count-bloodbag-per-month',[DashboardDashboardController::class, 'countBloodBagPerMonth']);
    Route::get('/dashboard-count-donor-per-barangay',[DashboardDashboardController::class, 'countDonorPerBarangay']);
    Route::get('/dashboard-mbd-quick-view',[DashboardDashboardController::class, 'mbdQuickView']);
    Route::get('/dashboard-get-number-of-donors',[DashboardDashboardController::class, 'countAllDonors']);

});




/*
|--------------------------------------------------------------------------
| Donor API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/get-available-blood', [DashboardController::class, 'donorAvailableBlood']);
    Route::get('/get-badge', [DashboardController::class, 'getBadge']);
    Route::get('/get-history', [DonationHistoryController::class, 'donationHistory']);
    Route::get('/get-day-since-last-donation', [DonationHistoryController::class, 'computeDaySinceLastDonation']);
    Route::get('/get-blood-journey', [BloodJourneyController::class, 'bloodJourney']);
    Route::post('/create-post', [DonorPostController::class, 'createPost']);
    Route::get('/get-donor-post', [DonorPostController::class, 'getDonorPost']);
    Route::put('/edit-post', [DonorPostController::class, 'editPost']);
    Route::delete('/delete-post', [DonorPostController::class, 'deletePost']);
    Route::get('/get-user-details', [UserListController::class, 'getUserDetails']);
    Route::post('/search-user', [UserListController::class, 'searchUsers']);
    Route::put('/edit-profile', [ProfileController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);    
    Route::get('/check-role',[AuthController::class, 'checkIfAdmin']);
    Route::get('/me',[AuthController::class, 'me']);
});