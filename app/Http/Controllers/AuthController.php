<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class AuthController extends Controller{
  public function login(Request $request)
  {
      // Validasi permintaan masuk
      $request->validate([
          'email' => 'required|email',
          'password' => 'required',
      ]);

      $kredensial = $request->only('email', 'password');

      if (Auth::attempt($kredensial)) {
          $user = Auth::user();

          // Hasilkan token untuk pengguna (Anda dapat menggunakan Laravel Passport atau paket lain)
          $token = $user->createToken('authToken')->plainTextToken;

          return response()->json([
              'user' => $user,
              'token' => $token,
          ]);
      } else {
          return response()->json(['message' => 'Kredensial tidak valid'], 401);
      }
  }

  // Fungsi Logout
  public function logout(Request $request)
  {
      // Mencabut token pengguna
      $request->user()->tokens()->delete();

      return response()->json(['message' => 'Berhasil keluar']);
  }

  // Fungsi Dapatkan Profil Pengguna
  public function getProfile()
  {
      $user = Auth::user();

      return response()->json(['user' => $user]);
  }
}