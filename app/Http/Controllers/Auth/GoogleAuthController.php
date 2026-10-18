<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    /**
     * Send the user to Google's sign-in page.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Google sends the user back here after they choose an account.
     *
     * Only users an admin has already added (and who are active) may sign in.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $exception) {
            // Happens when the user presses "Cancel" on Google, or the sign-in link expired.
            report($exception);

            return redirect()->route('login')
                ->withErrors(['google' => __('Google sign-in did not complete. Please try again.')]);
        }

        $googleData = $googleUser->getRaw();

        if (empty($googleData['email_verified'])) {
            return redirect()->route('login')
                ->withErrors(['google' => __('Your Google email address is not verified.')]);
        }

        $email = Str::lower($googleUser->getEmail());

        $user = User::where('email', $email)->first();

        // Unknown and deactivated users get the same message, so nobody can
        // find out which email addresses exist in the app.
        if ($user === null || ! $user->is_active) {
            return redirect()->route('login')
                ->withErrors(['google' => __('This Google account is not on the approved list. Ask an ERCare24 admin to add your email.')]);
        }

        // Once an email is linked to a Google account, no other Google account may use it.
        if ($user->google_id !== null && $user->google_id !== $googleUser->getId()) {
            return redirect()->route('login')
                ->withErrors(['google' => __('This Google account is not on the approved list. Ask an ERCare24 admin to add your email.')]);
        }

        // First sign-in: link the Google account and use the name from Google.
        if ($user->google_id === null) {
            $user->google_id = $googleUser->getId();

            if ($googleUser->getName()) {
                $user->name = $googleUser->getName();
            }
        }

        $user->avatar = $googleUser->getAvatar();

        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
