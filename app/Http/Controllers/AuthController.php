<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller{
    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');
        
        // Menggunakan query kustom untuk mendapatkan data pengguna
        $user = DB::table('adm_users')
            ->where('title', '!=', 'MAHASISWA')
            ->where('locked', 'N')
            ->where('username', $credentials['username'])
            ->first();
    
        if ($user && $user->password === md5($credentials['password'])) {
            if ($token = Auth::login($user)) {
                return $this->respondWithToken($token);
            }
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    public function logout()
    {
        Auth::logout();
    
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getProfile()
{
    $user = auth()->user();
    return response()->json(['user' => $user]);
}

}