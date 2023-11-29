<?php

use App\Http\Controllers\Api\Admin\DispensedBlood\DispensedBloodController;
use App\Http\Controllers\Api\Admin\Network\NetworkAdminController;
use App\Http\Controllers\Api\Donor\Network\NetworkController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\Admin\AuditTrailController;
use App\Http\Controllers\Api\Admin\BloodBags\BloodBagController;
use App\Http\Controllers\Api\Admin\Dashboard\DashboardController as DashboardDashboardController;
use App\Http\Controllers\Api\Admin\DonorList\DonorController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserListController;
use App\Http\Controllers\Api\Donor\DonationHistory\DonationHistoryController;
use App\Http\Controllers\Api\Donor\BloodJourney\BloodJourneyController;
use App\Http\Controllers\Api\Donor\Dashboard\DashboardController;
use App\Http\Controllers\Api\Admin\Inventory\InventoryController;
use App\Http\Controllers\Api\Admin\Mbd\MbdController;
use App\Http\Controllers\Api\Donor\Profile\ProfileController;
use App\Http\Controllers\Api\Admin\Settings\SettingsController;
use App\Http\Controllers\Api\Admin\DisposedBloodBags\DisposedBloodBagsController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\CustomEmailVerificationController;
use App\Http\Controllers\Api\PasswordResetLinkController;
use App\Http\Controllers\Api\NewPasswordController;

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
    // Route::match('/login', [AuthController::class, 'login']);    
    Route::match(['get', 'post'], '/login', [AuthController::class, 'login'])->name('login');
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
        Route::post('/register-step1', [RegistrationController::class, 'saveStep1']);
        Route::post('/register-step2', [RegistrationController::class, 'saveStep2']);
    }
);

/*
|--------------------------------------------------------------------------
| email verification
|--------------------------------------------------------------------------
*/
Route::get('/email/verify/{id}/{hash}', [CustomEmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1', 'guest'])->name('verification.verify');
Route::post('/email/verification-notification', [CustomEmailVerificationController::class, 'resend'])
    ->middleware(['throttle:6,1'])->name('verification.send');
Route::post('/check-verify', [CustomEmailVerificationController::class, 'checkIfVerify']);
Route::post('/check-verify-reg', [CustomEmailVerificationController::class, 'chechVerifyReg']);
Route::post('/check-user-details', [CustomEmailVerificationController::class, 'checkUserDetail']);


/*
|--------------------------------------------------------------------------
| forgot password
|--------------------------------------------------------------------------
*/
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/verify-otp', [PasswordResetLinkController::class, 'verifyOtp']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);
Route::post('/resend-otp', [PasswordResetLinkController::class, 'resendOtpEmail']);
Route::post('/next-resend-otp', [PasswordResetLinkController::class, 'getNextResendOtp']);


/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/
Route::group(['middleware' => ['auth:sanctum', 'admin']], function () {

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
    Route::get('/get-unsafe-remarks', [BloodBagController::class, 'getRemarks']);
    Route::post('/mark-unsafe', [BloodBagController::class, 'markUnsafe']);

    Route::get('/get-defferal-categories', [UserListController::class, 'getDeferralCategories']);
    Route::post('/move-to-defferal', [UserListController::class, 'moveToDeferral']);
    Route::get('/get-temporary-defferal', [UserListController::class, 'getTemporaryDeferral']);
    Route::get('/get-permanent-defferal', [UserListController::class, 'getPermanentDeferral']);

    Route::put('/edit-user-details', [UserListController::class, 'editUserDetails']);
    Route::post('/add-user', [UserListController::class, 'addUsers']);

    Route::get('/get-audit-trail', [AuditTrailController::class, 'getAuditTrail']);
    Route::post('/add-to-inventory', [InventoryController::class, 'storedInInventory']);
    Route::post('/bulk-move-to-inventory', [InventoryController::class, 'multipleMoveToInventory']);
    Route::get('/get-hospitals', [InventoryController::class, 'getHospitals']);

    Route::get('/get-blood-request', [NetworkAdminController::class, 'getAllBloodRequest']);
    Route::get('/get-request-id', [NetworkAdminController::class, 'getRequestIdNumber']);
    Route::post('/create-network-post', [NetworkAdminController::class, 'createPost']);
    Route::get('/get-interested-donor', [NetworkAdminController::class, 'getInterestedDonor']);

    Route::get('/get-stocks', [InventoryController::class, 'getStocks']);
    Route::post('/filter-stocks', [InventoryController::class, 'filterBloodTypeStocks']);
    Route::post('/search-stocks', [InventoryController::class, 'searchStocks']);
    Route::get('/export-stocks', [InventoryController::class, 'exportStocksAsPdf']);
    Route::get('/get-deferral-bloodbags', [InventoryController::class, 'getTempDeferralBloodBag']);
    Route::post('/filter-deferral-bloodbags', [InventoryController::class, 'filterBloodTypeTempDeferral']);
    Route::post('/search-rbb', [InventoryController::class, 'searchRbb']);
    Route::get('/export-rbb', [InventoryController::class, 'exportRbb']);


    Route::get('/get-permanent-bloodbags', [InventoryController::class, 'getPermaDeferralBloodBag']);
    Route::post('/filter-permanent-bloodbags', [InventoryController::class, 'filterBloodTypePermaDeferral']);
    Route::post('/search-sbb', [InventoryController::class, 'searchSbb']);
    Route::get('/export-sbb', [InventoryController::class, 'exportSbb']);

    Route::post('/move-back-to-collected', [InventoryController::class, 'moveToCollected']);
    Route::get('/get-expired-blood', [InventoryController::class, 'expiredBlood']);
    Route::post('/search-expired-blood', [InventoryController::class, 'searchExpiredBlood']);
    Route::get('/export-expired-blood', [InventoryController::class, 'exportExpiredAsPdf']);

    Route::post('/filter-expired', [InventoryController::class, 'filterBloodTypeExp']);
    Route::post('/dispose-blood', [InventoryController::class, 'disposeBlood']);
    Route::get('/export-pdf-user-details', [UserListController::class, 'exportUserDetailsAsPdf']);
    Route::get('/get-donor-list', [DonorController::class, 'donorList']);
    // Route::post('/filter-donor-list', [DonorController::class, 'filterDonorList']);
    Route::post('/search-donor', [DonorController::class, 'searchDonor']);
    Route::get('/export-pdf-donor-list', [DonorController::class, 'exportDonorListAsPdf']);

    Route::get('/dashboard-get-stocks', [DashboardDashboardController::class, 'getDashboardStock']);
    Route::get('/dashboard-get-quota', [DashboardDashboardController::class, 'getQuota']);
    Route::post('/dashboard-count-bloodbag-per-month', [DashboardDashboardController::class, 'countBloodBagPerMonth']);
    Route::post('/dashboard-count-donor-per-barangay', [DashboardDashboardController::class, 'countDonorPerBarangay']);
    Route::get('/dashboard-mbd-quick-view', [DashboardDashboardController::class, 'mbdQuickView']);
    // Route::get('/dashboard-get-number-of-donors',[DashboardDashboardController::class, 'countAllDonors']);

    Route::post('/mbd', [MbdController::class, 'getMbdSummary']);

    Route::post('/dispensed-blood', [InventoryController::class, 'dispensedBlood']);
    Route::get('/registered-users', [InventoryController::class, 'getRegisteredUsers']);


    Route::post('/dispensed-list', [DispensedBloodController::class, 'dispensedBloodList']);
    Route::post('/dispList', [DispensedBloodController::class, 'dispList']);
    Route::get('/get-all-serial-no', [DispensedBloodController::class, 'getAllSerialNumber']);
    Route::get('/get-dispensed-list', [DispensedBloodController::class, 'filterDispensedList']);
    Route::post('/search-patient', [DispensedBloodController::class, 'searchPatient']);
    Route::get('/export-patient-list', [DispensedBloodController::class, 'exportPatientList']);

    Route::post('/mark-as-accomodated', [NetworkAdminController::class, 'markAsAccomodated']);
    Route::post('/mark-as-referred', [NetworkAdminController::class, 'markAsReferred']);

    Route::post('/filter-disposed-bloodbag', [DisposedBloodBagsController::class, 'filterDisposedBloodBags']);
    Route::get('/get-disposed-bloodbag', [DisposedBloodBagsController::class, 'getDisposedBloodBag']);
    Route::post('/search-disposed-bloodbag', [DisposedBloodBagsController::class, 'searchDisposedBloodBag']);
    Route::get('/export-disposed-bloodbag', [DisposedBloodBagsController::class, 'exportDisposedAsPdf']);

    Route::get('/export-disposed-bloodbag', [DisposedBloodBagsController::class, 'exportDisposedAsPdf']);

    //Settings venue
    Route::post('/add-venue', [SettingsController::class, 'addVenue']);
    Route::get('/get-venue', [SettingsController::class, 'getVenues']);
    Route::post('/edit-venue', [SettingsController::class, 'editVenue']);
    Route::delete('/delete-venue', [SettingsController::class, 'deleteVenue']);

    //Settings hospitals
    Route::post('/add-hospital', [SettingsController::class, 'addHospital']);
    Route::get('/get-hospital', [SettingsController::class, 'getHospitals']);
    Route::post('/edit-hospital', [SettingsController::class, 'editHospital']);
    Route::delete('/delete-hospital', [SettingsController::class, 'deleteHospital']);

    //Settings Bled By
    Route::post('/add-bled-by', [SettingsController::class, 'addBledBy']);
    Route::get('/get-bled-by', [SettingsController::class, 'getBledBy']);
    Route::post('/edit-bled-by', [SettingsController::class, 'editBledBy']);
    Route::delete('/delete-bled-by', [SettingsController::class, 'deleteBledBy']);

    //Settings Permanent Deferral Category
    Route::post('/add-permanent-deferral-category', [SettingsController::class, 'addPermanentDeferralCategory']);
    Route::get('/get-permanent-deferral-category', [SettingsController::class, 'getPermanentDeferralCategory']);
    Route::post('/edit-permanent-deferral-category', [SettingsController::class, 'editPermanentDeferralCategory']);
    Route::delete('/delete-permanent-deferral-category', [SettingsController::class, 'deletePermanentDeferralCategory']);

    //Settings Temporary Deferral Category
    Route::post('/add-temporary-deferral-category', [SettingsController::class, 'addTemporaryDeferralCategory']);
    Route::get('/get-temporary-deferral-category', [SettingsController::class, 'getTemporaryDeferralCategory']);
    Route::post('/edit-temporary-deferral-category', [SettingsController::class, 'editTemporaryDeferralCategory']);
    Route::delete('/delete-temporary-deferral-category', [SettingsController::class, 'deleteTemporaryDeferralCategory']);

    //Settings Temporary  Remarks
    Route::post('/add-reactive-remarks', [SettingsController::class, 'addReactiveRemarks']);
    Route::get('/get-reactive-remarks', [SettingsController::class, 'getReactiveRemarks']);
    Route::post('/edit-reactive-remarks', [SettingsController::class, 'editReactiveRemarks']);
    Route::delete('/delete-reactive-remarks', [SettingsController::class, 'deleteReactiveRemarks']);

    //Settings Spoiled Remarks
    Route::post('/add-spoiled-remarks', [SettingsController::class, 'addSpoiledRemarks']);
    Route::get('/get-spoiled-remarks', [SettingsController::class, 'getSpoiledRemarks']);
    Route::post('/edit-spoiled-remarks', [SettingsController::class, 'editSpoiledRemarks']);
    Route::delete('/delete-spoiled-remarks', [SettingsController::class, 'deleteSpoiledRemarks']);

    //Settings Security Pin
    Route::post('/create-security-pin', [SettingsController::class, 'createSecurityPin']);
    Route::post('/check-security-pin', [SettingsController::class, 'checkSecurityPin']);
    Route::post('/change-security-pin', [SettingsController::class, 'changeSecurityPin']);
});




/*
|--------------------------------------------------------------------------
| Donor API Routes
|--------------------------------------------------------------------------
*/

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/get-available-blood', [DashboardController::class, 'donorAvailableBlood']);
    Route::get('/get-donation-summary', [DashboardController::class, 'donationSummary']);
    Route::get('/get-history', [DonationHistoryController::class, 'donationHistory']);
    Route::get('/get-day-since-last-donation', [DonationHistoryController::class, 'computeDaySinceLastDonation']);
    Route::get('/get-blood-journey', [BloodJourneyController::class, 'bloodJourney']);
    // Route::post('/create-post', [NetworkAdminController::class, 'createPost']);
    // Route::get('/get-donor-post', [DonorPostController::class, 'getDonorPost']);
    // Route::put('/edit-post', [DonorPostController::class, 'editPost']);
    // Route::delete('/delete-post', [DonorPostController::class, 'deletePost']);
    Route::post('/request-blood', [NetworkController::class, 'createBloodRequest']);
    Route::post('/cancel-request-blood', [NetworkController::class, 'cancelRequest']);

    Route::get('/get-requested-blood', [NetworkController::class, 'getBloodRequest']);
    Route::get('/get-blood-components', [NetworkController::class, 'getBloodComponent']);
    Route::get('/get-latest-blood-request', [NetworkController::class, 'getLastRequest']);
    Route::get('/get-admin-post', [NetworkController::class, 'adminPost']);
    Route::get('/get-recent-post', [NetworkController::class, 'getRecentPost']);

    Route::post('/button-interested', [NetworkController::class, 'buttonInterested']);
    Route::get('/get-my-interest', [NetworkController::class, 'getMyInterestDonation']);
    Route::get('/get-my-schedule-donation', [NetworkController::class, 'getMyScheduleDonation']);

    Route::get('/get-user-details', [UserListController::class, 'getUserDetails']);
    Route::post('/search-user', [UserListController::class, 'searchUsers']);
    Route::put('/edit-profile', [ProfileController::class, 'updateProfile']);
    Route::get('/get-achievements', [ProfileController::class, 'getAchievements']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check-role', [AuthController::class, 'checkIfAdmin']);
    Route::get('/me', [AuthController::class, 'me']);
});
