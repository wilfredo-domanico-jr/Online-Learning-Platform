<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SocialController extends Controller
{
    /**
     * Redirect user to the social provider.
     */
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle callback from the social provider.
     */
    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return to_route("register")->withErrors("Failed to authenticate. Please try again.");
        }

        // Check if email already exists
        $user = User::where('email', $socialUser->getEmail())
            ->where('social_provider', $provider)
            ->first();

        if ($user) {

            return to_route("register")->withErrors("This email for this provider is already registered. Please login instead.");
        }

        // Create new user with random password
        $user = User::create([
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'password' => bcrypt(Str::random(16)),
            'social_provider' => $provider, // optional
            'social_id' => $socialUser->getId(), // optional
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
