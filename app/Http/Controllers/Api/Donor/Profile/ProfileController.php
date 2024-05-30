<?php

namespace App\Http\Controllers\Api\Donor\Profile;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use App\Models\DonorBadgeHistory;
use App\Models\User;
use App\Models\UserDetail;
use App\Rules\EmailUpdateProfile;
use App\Rules\MobileUpdateProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id'               => ['required', 'exists:users,user_id'],
                'email'                 => ['required', 'string', new EmailUpdateProfile],
                'mobile'                => ['required', 'string', new MobileUpdateProfile],
                'occupation'            => ['string'],
                'street'                => ['required', 'string'],
                'region'                => ['required'],
                'province'              => ['required'],
                'municipality'          => ['required'],
                'barangay'              => ['required'],
            ]);

            $userDetails = UserDetail::where('user_id', $validatedData['user_id'])->first();
            $userDetails->occupation = ucwords($validatedData['occupation']);
            $userDetails->occupation = $validatedData['occupation'];
            $userDetails->street = $validatedData['street'];
            $userDetails->region = $validatedData['region'];
            $userDetails->province = $validatedData['province'];
            $userDetails->municipality = $validatedData['municipality'];
            $userDetails->barangay = $validatedData['barangay'];
            $userDetails->update();

            $userAuthDetails = User::where('user_id', $validatedData['user_id'])->first();
            $userAuthDetails->mobile = $validatedData['mobile'];
            $userAuthDetails->email = $validatedData['email'];
            $userAuthDetails->update();

            return response()->json([
                'status'    => 'success',
                'message'   => 'Profile updated',
            ]);
        } catch (ValidationException $e) {

            return response()->json([
                'status'        => 'error',
                'errors'        => $e->validator->errors(),
            ], 422);
        } catch (QueryException $e) {

            return response()->json([
                'status'    => 'error',
                'message'   => 'Database error',
                'errors'    => $e->getMessage(),
            ], 500);
        }
    }

    public function getAchievements()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        $bronze = BloodBag::where('user_id', $userId)
            ->orderBy('date_donated', 'asc')
            ->skip(1)
            ->take(1)
            ->first();
        $bronzeBadge = $bronze ? $bronze->date_donated : null;
        $this->recordBadgeHistory($userId, 1, $bronzeBadge);

        $silver = BloodBag::where('user_id', $userId)
            ->orderBy('date_donated', 'asc')
            ->skip(3)
            ->take(1)
            ->first();
        $silverBadge = $silver ? $silver->date_donated : null;
        $this->recordBadgeHistory($userId, 2, $silverBadge);

        $gold = BloodBag::where('user_id', $userId)
            ->orderBy('date_donated', 'asc')
            ->skip(7)
            ->take(1)
            ->first();
        $goldBadge = $gold ? $gold->date_donated : null;
        $this->recordBadgeHistory($userId, 3, $goldBadge);

        $currentYear = date('Y');
        $currentDate = date('Y-m-d');

        // Check if the last achieved badges are from a different year
        if ($bronzeBadge && date('Y', strtotime($bronzeBadge)) < $currentYear) {
            $bronzeBadge = null;
            $this->recordBadgeHistory($userId, 1, $currentDate); // Create new record for bronze badge
        }

        if ($silverBadge && date('Y', strtotime($silverBadge)) < $currentYear) {
            $silverBadge = null;
            $this->recordBadgeHistory($userId, 2, $currentDate); // Create new record for silver badge
        }

        if ($goldBadge && date('Y', strtotime($goldBadge)) < $currentYear) {
            $goldBadge = null;
            $this->recordBadgeHistory($userId, 3, $currentDate); // Create new record for gold badge
        }

        return response()->json([
            'status' => 'success',
            'bronzeBadge' => $bronzeBadge,
            'silverBadge' => $silverBadge,
            'goldBadge' => $goldBadge,
        ]);
    }


    private function recordBadgeHistory($userId, $badgeType, $achievedDate)
    {
        // Check if a record already exists with the same user_id and badge_id
        $existingRecord = DonorBadgeHistory::where('user_id', $userId)
            ->where('badge_id', $badgeType)
            ->first();

        // Create a new record only if an existing record doesn't exist
        if (!$existingRecord) {
            DonorBadgeHistory::create([
                'user_id' => $userId,
                'badge_id' => $badgeType,
                'achieved_date' => $achievedDate
            ]);
        }
    }
}
