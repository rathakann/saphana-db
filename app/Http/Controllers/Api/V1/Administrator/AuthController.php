<?php

namespace App\Http\Controllers\Api\V1\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Traits\ApiResponse;
use Exception;

class AuthController extends Controller
{
    use ApiResponse;
    //
    public function login(Request $request)
    {
        try{
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
            if (!Auth::attempt($request->only('username', 'password'))) {
                return $this->apiResponse(null, 'failed', null, Response::HTTP_EXPECTATION_FAILED, 'Invalid credentials');
            }
            $request->session()->regenerate();
            return $this->apiResponse(Auth::user(), 'success', null, Response::HTTP_OK, 'You have been logged in successfully.');

        } catch (Exception $e) {
            return $this->apiResponse(null, 'failed', null, Response::HTTP_EXPECTATION_FAILED, $e->getMessage());
        }
    }

    public function register(Request $request)
    {
        try{
            // Validate request
            $request->validate([
                'username' => 'required|string|min:3|max:50|unique:users,username',
                'password' => 'required|string|min:6|confirmed', // expects password_confirmation
            ]);
            // Create new user
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            // Log the user in
            Auth::login($user);
            return $this->apiResponse(Auth::user(), 'success', null, Response::HTTP_CREATED, 'You have been registered successfully.');
        }catch(Exception $e){
            return $this->apiResponse(null, 'failed', null, Response::HTTP_EXPECTATION_FAILED, $e->getMessage());
        }
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $this->apiResponse(null, 'success', null, Response::HTTP_OK, 'You have been logged out successfully.');
    }
}
