<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($credentials['login']);
        $passwordInput = $credentials['password'];
        $remember = $request->boolean('remember');

        // Check if DB user exists for 'admin' or if login is 'admin' / '123'
        if ($loginInput === 'admin' && $passwordInput === '123') {
            $user = User::where('name', 'admin')
                ->orWhere('email', 'admin')
                ->orWhere('email', 'admin@autotrace.com')
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => 'admin',
                    'email' => 'admin@autotrace.com',
                    'password' => Hash::make('123'),
                ]);
            } else {
                // Ensure password matches in DB
                if (!Hash::check('123', $user->password)) {
                    $user->password = Hash::make('123');
                    $user->save();
                }
            }

            Auth::login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, Administrator!');
        }

        // Standard Laravel authentication attempt (by email or name)
        $user = User::where('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        if ($user && Hash::check($passwordInput, $user->password)) {
            Auth::login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors([
            'login' => 'Invalid username or password.',
        ])->onlyInput('login');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been successfully logged out.');
    }
}
