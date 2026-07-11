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
            return redirect()->away(
                config('app.frontend_url').'/login?error=oauth_failed'
            );
        }

        // Match by email alone: a user who originally registered with a
        // password should be linked to this provider, not rejected/duplicated.
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            if (is_null($user->social_provider)) {
                $user->forceFill([
                    'social_provider' => $provider,
                    'social_id' => $socialUser->getId(),
                ])->save();
            }
        } else {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'password' => bcrypt(Str::random(16)),
                'email_verified_at' => now(), // provider already verified this email
                'social_provider' => $provider,
                'social_id' => $socialUser->getId(),
            ]);

            $user->assignRole('student');
        }

        Auth::login($user);

        return redirect()->away(config('app.frontend_url').'/auth/callback');
    }
}
