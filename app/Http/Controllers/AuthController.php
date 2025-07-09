<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
    public function __construct() {
        $this->middleware('auth:sanctum', [ 'except' => [ 'login', 'register' ] ]);
    }

    public function login( LoginRequest $request ) {
        $credentials = $request->only('username', 'password');

        if ( !Auth::attempt($credentials) ) {
            return response()->json([ 'message' => 'نام کاربری یا رمز عبور اشتباه است' ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }

    public function me() {
        return response()->json(Auth::user());
    }

    public function logout( Request $request ) {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'با موفقیت خارج شدید',
        ]);
    }

    public function register( RegisterRequest $request ) {
        $user = User::create([
            'name'     => $request->name,
            'username' => strtolower($request->username),
            'hash'     => substr(md5($request->username), 10, 20),
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'ثبت‌نام با موفقیت انجام شد',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    public function check( Request $request ) {
        $user = $request->user();

        if ( $user ) {
            return response()->json([
                'status'     => true,
                'user'       => $user,
                'token_info' => $request->bearerToken(),
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'کاربر لاگین نکرده است.',
        ], 401);
    }

}
