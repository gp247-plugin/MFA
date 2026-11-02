<?php

namespace App\GP247\Plugins\MFA\Middleware;

use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use App\GP247\Plugins\MFA\Services\TrustedDeviceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ask a password-authenticated user of a guard for the second factor once per
 * session, unless this browser is trusted for that account.
 *
 * @aidlc-unit mfa-plugin
 * @aidlc-story US-mfa-verified-session-per-guard, US-mfa-trusted-device
 * @aidlc-adr mfa-plugin_verified-session-per-guard, mfa-plugin_trusted-device-cookie
 */
class TwoFactorAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard Guard key; null = config default_guard.
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // WHY no URL guessing: every place the plugin attaches this middleware passes
        // the guard; the old path sniffing never matched (path() has no leading "/").
        $guard = $guard ?? config('Plugins/MFA.default_guard', 'customer');

        $guardConfig = mfa_get_guard_config($guard);

        // Check if MFA is enabled for this guard
        if (!$guardConfig || !$guardConfig['enabled']) {
            return $next($request);
        }

        // The second factor only applies after the password: no user, nothing to do
        $user = Auth::guard($guard)->user();
        if (!$user) {
            return $next($request);
        }

        if (mfa_is_verified($guard, $user)) {
            return $next($request);
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->getAuthIdentifier())
            ->where('enabled', 1)
            ->first();

        // If user doesn't have MFA enabled, check if it's forced
        if (!$mfaRecord) {
            if ($guardConfig['forced'] ?? false) {
                // Redirect to MFA setup if forced
                return redirect()->route('mfa.setup.show', $guard)
                    ->with('warning', gp247_language_render('Plugins/MFA::lang.mfa_required'));
            }
            return $next($request);
        }

        if (app(TrustedDeviceService::class)->isTrusted($request, $guard, $guardConfig, $user)) {
            mfa_set_verified($guard, $user);
            return $next($request);
        }

        // Store guard in session for verification
        session(['mfa_guard' => $guard]);

        return redirect()->route('mfa.verify.show', $guard);
    }
}
