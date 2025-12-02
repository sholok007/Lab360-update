<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($request->only('username','password'))) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors(['error'=>'Invalid username or password.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login'); // now works
    }

    public function showSignup()
    {
        return view('signup.signup');
    }

    public function signup(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'username'=>'required|string|max:255|unique:users',
            'email'=>'required|string|email|unique:users',
            'password'=>'required|string|min:6',
            'contact_no'=>'nullable|string|max:20',
        ]);

        User::create([
            'name'=>$request->name,
            'username'=>$request->username,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'contact_no'=>$request->contact_no,
            'role'=>'Proprietor', 
        ]);

        return redirect()->route('login')->with('success','Signup successful! Please login.');
    }
}
