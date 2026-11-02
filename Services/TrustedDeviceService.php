<?php

namespace App\GP247\Plugins\MFA\Services;

use App\GP247\Plugins\MFA\Models\TrustedDevice;
use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie as CookieFactory;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * "Trust this device" for one guard: decides whether the signed-in user may skip
 * the authenticator code, issues the trust cookie and revokes trust.
 *
 * The cookie only replaces the second factor — callers check it after the guard
 * already has a password-authenticated user. Cookie path/domain/secure/same_site
 * follow config('session.*') so trust never reaches more hosts than the session
 * cookie does (host-only when SESSION_DOMAIN is null).
 *
 * @aidlc-unit mfa-plugin
 * @aidlc-story US-mfa-trusted-device, US-mfa-trusted-device-revoke
 * @aidlc-adr mfa-plugin_trusted-device-cookie
 */
class TrustedDeviceService
{
    /** Memo of available() for this instance. */
    private ?bool $tableExists = null;

    /**
     * Whether the trusted-device table exists.
     *
     * WHY: plugin files can be replaced before update() creates the table (manual
     * upload, update not applied yet). Until then trust is simply off: the code is
     * asked as before and password changes keep working.
     *
     * @return bool
     */
    public function available(): bool
    {
        return $this->tableExists ??= Schema::hasTable('mfa_trusted_devices');
    }

    /**
     * Cookie name for a guard.
     *
     * @param string $guard
     * @return string
     */
    public function cookieName(string $guard): string
    {
        return 'mfa_trust_' . $guard;
    }

    /**
     * Trust lifetime of a guard in days (0 = option off).
     *
     * @param array $guardConfig
     * @return int
     */
    public function days(array $guardConfig): int
    {
        return max(0, min(365, (int) ($guardConfig['trust_device_days'] ?? 0)));
    }

    /**
     * Whether $user may skip the code on this browser; touches the device when so.
     *
     * @param Request $request
     * @param string  $guard
     * @param array   $guardConfig
     * @param mixed   $user
     * @return bool
     */
    public function isTrusted(Request $request, string $guard, array $guardConfig, $user): bool
    {
        if ($this->days($guardConfig) === 0 || !$this->available()) {
            return false;
        }
        $token = $request->cookie($this->cookieName($guard));
        if (!is_string($token) || $token === '') {
            return false;
        }
        $device = TrustedDevice::findValidFor($guardConfig['model'], $this->userId($user), $token);
        if (!$device) {
            return false;
        }
        $device->touchUsed();

        return true;
    }

    /**
     * Trust the current browser for $user and build the cookie to send.
     *
     * @param Request $request
     * @param string  $guard
     * @param array   $guardConfig
     * @param mixed   $user
     * @return Cookie|null Null when the guard has the option off.
     */
    public function trust(Request $request, string $guard, array $guardConfig, $user): ?Cookie
    {
        $days = $this->days($guardConfig);
        if ($days === 0 || !$this->available()) {
            return null;
        }
        $token = TrustedDevice::issue(
            $guardConfig['model'],
            $this->userId($user),
            $days,
            $request->userAgent(),
            $request->ip()
        );

        return CookieFactory::make(
            $this->cookieName($guard),
            $token,
            $days * 24 * 60,
            config('session.path', '/'),
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );
    }

    /**
     * Cookie that deletes the trust cookie of a guard from the browser.
     *
     * @param string $guard
     * @return Cookie
     */
    public function forget(string $guard): Cookie
    {
        return CookieFactory::forget($this->cookieName($guard), config('session.path', '/'), config('session.domain'));
    }

    /**
     * Revoke every trusted device of an owner.
     *
     * @param string $userType
     * @param string $userId
     * @return int
     */
    public function revokeAll(string $userType, string $userId): int
    {
        return $this->available() ? TrustedDevice::revokeAllFor($userType, $userId) : 0;
    }

    /**
     * Unexpired devices of the signed-in user (empty when the table is missing).
     *
     * @param array $guardConfig
     * @param mixed $user
     * @return \Illuminate\Support\Collection
     */
    public function activeFor(array $guardConfig, $user)
    {
        return $this->available() ? TrustedDevice::activeFor($guardConfig['model'], $this->userId($user)) : collect();
    }

    /**
     * Revoke every trusted device of the owner of an MFA record.
     *
     * @param TwoFactorAuth $record
     * @return int
     */
    public function revokeAllForRecord(TwoFactorAuth $record): int
    {
        return $this->revokeAll((string) $record->user_type, (string) $record->user_id);
    }

    /**
     * Owner id as stored in the MFA tables.
     *
     * @param mixed $user
     * @return string
     */
    public function userId($user): string
    {
        return (string) $user->getAuthIdentifier();
    }
}
