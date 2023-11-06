<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller{
    public function __construct(){
        $this->middleware('jwt', ['except' => ['login']]);
    }

    public function login(Request $request){

          $username = $request->input('username');
          $password = $request->input('password');
        
        $user = User::where('title', '!=', 'MAHASISWA')
            ->where('locked', 'N')
            ->where('username', $username)
            ->first();

    
        if ($user && $user->password === md5($password)) {
            $token = JWTAuth::fromUser($user);
            return $this->respondWithToken($token);
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function respondWithToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    
    public function logout(){
        Auth::logout();
    
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getProfile(){
        if (auth()->check()) {
            $user = auth()->user();
            return response()->json(['user' => $user]);
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }
    
}