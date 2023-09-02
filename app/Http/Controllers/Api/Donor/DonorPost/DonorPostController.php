<?php

namespace App\Http\Controllers\Api\Donor\DonorPost;

use App\Http\Controllers\Controller;
use App\Models\DonorPost;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DonorPostController extends Controller
{
    public function createPost(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        try {
            $validatedData = $request->validate([
                'body' => 'required',
                'contact' => 'required',
            ]);

            $donor_post = DonorPost::create([
                'user_id' => $userId,
                'body' => $validatedData['body'],
                'contact' => $validatedData['contact'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Donor post created successfully',
                'data' => $donor_post,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ], 400);
        }
    }


    public function getDonorPost()
    {
        $donorPosts = DonorPost::where('isApproved', 1)
            ->where('status', 0)
            ->with(['user.userDetails.galloner', 'user.bloodBag' => function ($query) {
                $query->orderBy('date_donated', 'desc')->first();
            }])
            ->get();

        if ($donorPosts->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donor post found.'
            ], 200);
        }

        $responseData = $donorPosts->map(function ($donorPost) {
            $userDetails = $donorPost->user->userDetails;
            $donate = $donorPost->user->bloodBag;
            $lastDonation = $donate ? $donate->date_donated : null;
            $donateQty = $userDetails->galloner->donate_qty; 

            return [
                'donor_posts_id'    => $donorPost->donor_posts_id,
                'first_name'        => $userDetails->first_name,
                'last_name'         => $userDetails->last_name,
                'blood_type'        => $userDetails->blood_type,
                'body'              => $donorPost->body,
                'donate_qty'        => $donateQty, 
                'last_donation'     => $lastDonation
            ];
        });


        return response()->json([
            'status' => 'success',
            'data' => $responseData,
        ], 200);
    }

}
