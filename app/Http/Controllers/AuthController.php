<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function test(string $text)
    {
        return response($text);
    }

    public function register(Request $request)
    {
        User::registerNewUser($request->all());

        return response("Success");
    }

    public function login(Request $request)
    {
        return User::attemptLogIn($request->all());
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response('Logout success');
    }
}
