<?php

namespace App\GP247\Plugins\MFA\Controllers;

use App\GP247\Plugins\MFA\Models\TrustedDevice;
use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use App\GP247\Plugins\MFA\Helpers\TwoFactorHelper;
use App\GP247\Plugins\MFA\Services\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Routing\Controller;

class MFAController extends Controller
{
    /** Wrong tries allowed on the verify screen per guard + account in one window. */
    private const VERIFY_MAX_ATTEMPTS = 5;

    /** Length of that window (seconds). */
    private const VERIFY_DECAY_SECONDS = 900;

    /**
     * TOTP helper, resolved when needed.
     *
     * WHY lazy: building it constructs Google2FA; resolving at construction made
     * every route listing (which instantiates controllers) depend on the library.
     *
     * @return TwoFactorHelper
     */
    protected function helper(): TwoFactorHelper
    {
        return app(TwoFactorHelper::class);
    }

    /**
     * @return TrustedDeviceService
     */
    protected function trustedDevices(): TrustedDeviceService
    {
        return app(TrustedDeviceService::class);
    }

    /**
     * Show MFA setup page
     *
     * @param Request $request
     * @param string $guard
     * @return \Illuminate\View\View
     */
    public function showSetup(Request $request, $guard = 'customer')
    {
        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        // Check if user already has MFA enabled
        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->first();

        if ($mfaRecord && $mfaRecord->enabled) {
            return redirect()->route($guardConfig['redirect_after_verify']);
        }

        // Generate new secret if not exists
        if (!$mfaRecord) {
            $secret = $this->helper()->generateSecretKey();
            $mfaRecord = TwoFactorAuth::create([
                'user_type' => $guardConfig['model'],
                'user_id' => $user->id,
                'secret' => $secret,
                'enabled' => 0,
            ]);
        }

        // Get QR code
        $qrCodeSvg = $this->helper()->getQRCodeSvg(
            $user->email,
            $mfaRecord->getDecryptedSecretAttribute(),
            config('Plugins/MFA.app_name'),
            $guardConfig['qr_code_size'] ?? 200
        );

        return view('Plugins/MFA::Frontend.setup', [
            'guardConfig' => $guardConfig,
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $mfaRecord->getDecryptedSecretAttribute(),
            'guard' => $guard,
            'user' => $user,
        ]);
    }

    /**
     * Enable MFA for user
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'guard' => 'required|string',
        ]);

        $guard = $request->input('guard');
        $code = $request->input('code');

        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->first();

        if (!$mfaRecord) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_setup'));
        }

        // Verify code
        $isValid = $this->helper()->verifyCode(
            $mfaRecord->getDecryptedSecretAttribute(),
            $code,
            $guardConfig['window'] ?? 1
        );

        if (!$isValid) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.invalid_code'));
        }

        // Generate recovery codes
        $recoveryCodes = $this->helper()->generateRecoveryCodes(
            $guardConfig['recovery_codes_count'] ?? 8
        );

        // Enable MFA
        $mfaRecord->recovery_codes = $recoveryCodes;
        $mfaRecord->enabled = 1;
        $mfaRecord->enabled_at = now();
        $mfaRecord->save();

        return redirect()->route('mfa.recovery_codes', $guard)
            ->with('success', gp247_language_render('Plugins/MFA::lang.mfa_enabled_successfully'));
    }

    /**
     * Show recovery codes
     *
     * @param Request $request
     * @param string $guard
     * @return \Illuminate\View\View
     */
    public function showRecoveryCodes(Request $request, $guard = 'customer')
    {
        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->first();

        if (!$mfaRecord || !$mfaRecord->enabled) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled'));
        }

        $recoveryCodes = $mfaRecord->getDecryptedRecoveryCodesAttribute();

        return view('Plugins/MFA::Frontend.recovery_codes', [
            'guardConfig' => $guardConfig,
            'recoveryCodes' => $recoveryCodes,
            'guard' => $guard,
            'user' => $user,
        ]);
    }

    /**
     * Show MFA verification page
     *
     * @param Request $request
     * @param string|null $guard
     * @return \Illuminate\View\View
     */
    public function showVerify(Request $request, $guard = null)
    {
        $guard = $guard ?? session('mfa_guard', 'customer');

        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        return view('Plugins/MFA::Frontend.verify', [
            'guardConfig' => $guardConfig,
            'guard' => $guard,
            'user' => $user,
            'trustDays' => $this->trustedDevices()->days($guardConfig),
        ]);
    }

    /**
     * Verify the second factor of a password-authenticated user.
     *
     * Every try is counted before the code is checked (5 per 15 minutes per guard +
     * account); an authenticator code is accepted once per time step on any channel;
     * "trust this device" is honoured only for an authenticator code; a recovery
     * code revokes every trusted device (the usual reason is a lost phone).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-web-verify-brute-force, US-mfa-trusted-device, US-mfa-trusted-device-revoke, US-mfa-verified-session-per-guard
     * @aidlc-adr mfa-plugin_web-verify-throttle, mfa-plugin_trusted-device-cookie
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:16',
            'guard' => 'required|string',
            'trust_device' => 'nullable|boolean',
        ]);

        $guard = $request->input('guard');
        $code = trim((string) $request->input('code'));

        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->where('enabled', 1)
            ->first();

        if (!$mfaRecord) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled'));
        }

        $limiterKey = 'mfa-web-verify:' . $guard . ':' . $user->getAuthIdentifier();
        // WHY count first and compare the returned count: the cache increment is atomic, so
        // concurrent requests each get their own number and cannot slip past the limit together.
        if (RateLimiter::hit($limiterKey, self::VERIFY_DECAY_SECONDS) > self::VERIFY_MAX_ATTEMPTS) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($limiterKey) / 60));
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.verify_locked', ['minutes' => $minutes]));
        }

        if (strlen($code) === 6 && ctype_digit($code)
            && $this->helper()->acceptCodeOnce($mfaRecord, $code, (int) ($guardConfig['window'] ?? 1))) {
            $response = $this->completeVerification($request, $limiterKey, $guard, $guardConfig, $user)
                ->with('success', gp247_language_render('Plugins/MFA::lang.verification_successful'));

            if ($request->boolean('trust_device')) {
                $cookie = $this->trustedDevices()->trust($request, $guard, $guardConfig, $user);
                if ($cookie) {
                    $response->withCookie($cookie);
                }
            }

            return $response;
        }

        if (strlen($code) === 8 && $mfaRecord->hasRecoveryCode($code)) {
            $mfaRecord->useRecoveryCode($code);
            $this->trustedDevices()->revokeAllForRecord($mfaRecord);

            return $this->completeVerification($request, $limiterKey, $guard, $guardConfig, $user)
                ->with('warning', gp247_language_render('Plugins/MFA::lang.recovery_code_used'))
                ->withCookie($this->trustedDevices()->forget($guard));
        }

        return back()->with('error', gp247_language_render('Plugins/MFA::lang.invalid_code'));
    }

    /**
     * Mark the session verified for this guard + account and go on.
     *
     * @param Request $request
     * @param string  $limiterKey
     * @param string  $guard
     * @param array   $guardConfig
     * @param mixed   $user
     * @return RedirectResponse
     */
    private function completeVerification(Request $request, string $limiterKey, string $guard, array $guardConfig, $user): RedirectResponse
    {
        RateLimiter::clear($limiterKey);
        // WHY regenerate: the session just gained a privilege (second factor passed).
        $request->session()->regenerate();
        mfa_set_verified($guard, $user);
        session(['mfa_guard' => $guard]);

        return redirect()->route($guardConfig['redirect_after_verify']);
    }

    /**
     * Show MFA management page
     *
     * @param Request $request
     * @param string $guard
     * @return \Illuminate\View\View
     */
    public function showManage(Request $request, $guard = 'customer')
    {
        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->first();

        $service = $this->trustedDevices();
        $trustedDevices = $mfaRecord && $mfaRecord->enabled ? $service->activeFor($guardConfig, $user) : collect();
        $currentToken = (string) $request->cookie($service->cookieName($guard));

        return view('Plugins/MFA::Frontend.manage', [
            'guardConfig' => $guardConfig,
            'mfaRecord' => $mfaRecord,
            'guard' => $guard,
            'user' => $user,
            'trustedDevices' => $trustedDevices,
            'currentToken' => $currentToken,
        ]);
    }

    /**
     * Disable MFA for user
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'guard' => 'required|string',
        ]);

        $guard = $request->input('guard');
        $password = $request->input('password');

        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.incorrect_password'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->first();

        if ($mfaRecord) {
            $mfaRecord->delete();
        }
        $this->trustedDevices()->revokeAll($guardConfig['model'], $this->trustedDevices()->userId($user));

        return redirect()->back()
            ->with('success', gp247_language_render('Plugins/MFA::lang.mfa_disabled_successfully'))
            ->withCookie($this->trustedDevices()->forget($guard));
    }

    /**
     * Stop trusting one of the signed-in user's devices.
     *
     * @param Request $request
     * @return RedirectResponse
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-trusted-device-revoke
     */
    public function revokeTrustedDevice(Request $request)
    {
        $request->validate([
            'guard' => 'required|string',
            'id' => 'required|integer',
        ]);
        $context = $this->signedInContext((string) $request->input('guard'));
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$guard, $guardConfig, $user] = $context;
        $service = $this->trustedDevices();

        // WHY scoped by owner: an id from the form must never reach another account's device.
        $device = !$service->available() ? null : TrustedDevice::where('id', (int) $request->input('id'))
            ->where('user_type', $guardConfig['model'])
            ->where('user_id', $service->userId($user))
            ->first();
        if (!$device) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.trusted_device_not_found'));
        }
        $isCurrent = $device->matches((string) $request->cookie($service->cookieName($guard)));
        TrustedDevice::revokeFor($guardConfig['model'], $service->userId($user), (int) $device->id);

        $response = back()->with('success', gp247_language_render('Plugins/MFA::lang.trusted_device_revoked'));

        return $isCurrent ? $response->withCookie($service->forget($guard)) : $response;
    }

    /**
     * Stop trusting every device of the signed-in user.
     *
     * @param Request $request
     * @return RedirectResponse
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-trusted-device-revoke
     */
    public function revokeAllTrustedDevices(Request $request)
    {
        $request->validate(['guard' => 'required|string']);
        $context = $this->signedInContext((string) $request->input('guard'));
        if ($context instanceof RedirectResponse) {
            return $context;
        }
        [$guard, $guardConfig, $user] = $context;
        $service = $this->trustedDevices();
        $service->revokeAll($guardConfig['model'], $service->userId($user));

        return back()
            ->with('success', gp247_language_render('Plugins/MFA::lang.trusted_devices_revoked'))
            ->withCookie($service->forget($guard));
    }

    /**
     * [guard, guard config, user] for an enabled guard with a signed-in user, or the
     * redirect to show instead.
     *
     * @param string $guard
     * @return array|RedirectResponse
     */
    private function signedInContext(string $guard)
    {
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }
        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        return [$guard, $guardConfig, $user];
    }

    /**
     * Regenerate recovery codes
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'guard' => 'required|string',
        ]);

        $guard = $request->input('guard');
        $password = $request->input('password');

        // Validate guard exists in config
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig || !$guardConfig['enabled']) {
            return redirect()->back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled_for_guard'));
        }

        $user = Auth::guard($guard)->user();
        if (!$user) {
            return redirect()->route($guardConfig['redirect_need_login'])->with('error', gp247_language_render('Plugins/MFA::lang.user_not_authenticated'));
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.incorrect_password'));
        }

        $mfaRecord = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->where('enabled', 1)
            ->first();

        if (!$mfaRecord) {
            return back()->with('error', gp247_language_render('Plugins/MFA::lang.mfa_not_enabled'));
        }

        $mfaRecord->regenerateRecoveryCodes($guardConfig['recovery_codes_count'] ?? 8);

        return redirect()->route('mfa.recovery_codes', $guard)
            ->with('success', gp247_language_render('Plugins/MFA::lang.recovery_codes_regenerated'));
    }
}
