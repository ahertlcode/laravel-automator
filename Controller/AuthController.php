<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller{
    use ApiResponser;

    public function register(Request $request) {
        $attr = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $attr['password'] = \bcrypt($attr['password']);

        $user = User::create($attr);

        return $this->success([
            'token' => $user->createToken('API Token')->plainTextToken
        ]);
    }

    public function login(Request $request){
        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8'
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credential mismatch', 401);
        }
        auth()->user()->is_online = 1;
        auth()->user()->save();
        return $this->success([
            'token' => auth()->user()->createToken('API Token')->plainTextToken
        ]);
    }

    public function request_password_reset(Request $request) {
        $attr = $request->validate([
            'email' => 'required|string|email'
        ]);

        $user = User::find([
            'email' => $request->email
        ])->get();

        if ($user) {
            //add send mail function here.
        }
    }

    public function logout(){
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Token Revoked'
        ];
    }
}
?>