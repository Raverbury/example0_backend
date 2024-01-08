<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{

    public function test(string $text) {
        return response($text);
    }

    public function register(Request $request)
    {
        $credentials = $request->validate(
        [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        try
        {
            DB::beginTransaction();
            User::create(
            [
                'name' => $credentials['email'],
                'email' => $credentials['email'],
                'password' => Hash::make($credentials['password']),
            ]);
            DB::commit();
        }
        catch (Exception $exception)
        {
            DB::rollback();
            return response($exception);
        }

        return response("Success");
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user == null || !Hash::check($credentials['password'], $user['password']))
        {
            return response('Invalid credentials');
        }

        $user->tokens()->delete();
        $token = $user->createToken('test');
        return response($token->plainTextToken);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response('Logout success');
    }
}
