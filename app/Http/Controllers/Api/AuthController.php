<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login()
    {
        $input = request('email_or_phone'); 
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $credentials = [
            $field => $input,
            'password' => request('password'),
        ];

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        if ($user && $user->email_verified_at === null) {
            auth()->logout();
            return response()->json([
                'status' => 'error',
                'message' => 'Your email is not yet verified',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */


     public function respondWithToken(string $access_token, User $user = null)
     {
         $expires_in = Auth::factory()->getTTL() * 1440;
 
         return new JsonResponse([
             'user' => $user ?: Auth::user(),
             'authorization' => [
                 'access_token' => $access_token,
                 'token_type' => 'bearer',
                 'expires_in' => $expires_in,
             ],
         ]);
     }

}