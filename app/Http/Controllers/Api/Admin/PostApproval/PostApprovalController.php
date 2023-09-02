<?php

namespace App\Http\Controllers\Api\Admin\PostApproval;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\DonorPost;

class PostApprovalController extends Controller
{
    
    public function approvePost(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;
            try {
                
                $validatedData = $request->validate([
                    'donor_posts_id'       => 'required',
                ]);               

                $ch = curl_init('http://ipwho.is/' );
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
            
                $ipwhois = json_decode(curl_exec($ch), true);
            
                curl_close($ch);

                $donor_post = DonorPost::where('donor_posts_id', $validatedData['donor_posts_id'])->latest('created_at')->first();

                if ($donor_post) {

                    if($donor_post->isApproved === 1) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => 'This post is already approved',
                        ], 400);
                    }

                    $donor_post->isApproved = 1;
                    $donor_post->save();
                   
                        AuditTrail::create([
                            'user_id'    => $userId,
                            'action'     => 'Approved post | post id: ' . $donor_post->donor_posts_id,
                            'status'     => 'success',
                            'ip_address' => $ipwhois['ip'],
                            'region'     => $ipwhois['region'],
                            'city'       => $ipwhois['city'],
                            'postal'     => $ipwhois['postal'],
                            'latitude'   => $ipwhois['latitude'],
                            'longitude'  => $ipwhois['longitude'],
                        ]);
                
                        return response()->json([
                            'status'       => 'error',
                            'message'      => 'Post Approved',
                        ], 200);

                }else{
                    return response()->json([
                        'status'       => 'success',
                        'message'      => 'Post not found',
                    ], 404);
                }
                


            } catch (ValidationException $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->validator->errors(),
                ], 400);
            }
    }

}
