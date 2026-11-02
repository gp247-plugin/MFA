<?php

namespace App\GP247\Plugins\MFA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A browser the owner chose to trust: signing in there skips the authenticator
 * code (never the password) until expires_at. Only a SHA-256 of the token is
 * stored; the token itself lives in the owner's cookie.
 *
 * Owner = (user_type, user_id), the same pair used by TwoFactorAuth.
 *
 * @aidlc-unit mfa-plugin
 * @aidlc-story US-mfa-trusted-device, US-mfa-trusted-device-revoke
 * @aidlc-adr mfa-plugin_trusted-device-cookie
 */
class TrustedDevice extends Model
{
    /** Length of the random token handed to the browser. */
    public const TOKEN_LENGTH = 64;

    protected $table = 'mfa_trusted_devices';

    protected $fillable = [
        'user_type',
        'user_id',
        'token_hash',
        'user_agent',
        'ip_address',
        'expires_at',
        'last_used_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Trust a new browser for the owner; returns the token for the cookie (shown once).
     *
     * @param string      $userType
     * @param string      $userId
     * @param int         $days      Trust lifetime, > 0.
     * @param string|null $userAgent
     * @param string|null $ip
     * @return string
     */
    public static function issue(string $userType, string $userId, int $days, ?string $userAgent, ?string $ip): string
    {
        static::pruneExpiredFor($userType, $userId);
        $token = Str::random(self::TOKEN_LENGTH);
        static::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'token_hash' => static::hashToken($token),
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
            'ip_address' => $ip,
            'expires_at' => now()->addDays($days),
        ]);

        return $token;
    }

    /**
     * The unexpired device of this owner matching $token, or null.
     *
     * @param string $userType
     * @param string $userId
     * @param string $token
     * @return static|null
     */
    public static function findValidFor(string $userType, string $userId, string $token): ?self
    {
        if ($token === '') {
            return null;
        }

        return static::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('token_hash', static::hashToken($token))
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Unexpired devices of the owner, newest first (for the manage screen).
     *
     * @param string $userType
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function activeFor(string $userType, string $userId)
    {
        return static::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Revoke every device of the owner.
     *
     * @param string $userType
     * @param string $userId
     * @return int Rows removed.
     */
    public static function revokeAllFor(string $userType, string $userId): int
    {
        return static::where('user_type', $userType)->where('user_id', $userId)->delete();
    }

    /**
     * Revoke one device, only when it belongs to the owner.
     *
     * @param string $userType
     * @param string $userId
     * @param int    $id
     * @return bool
     */
    public static function revokeFor(string $userType, string $userId, int $id): bool
    {
        return static::where('id', $id)->where('user_type', $userType)->where('user_id', $userId)->delete() > 0;
    }

    /**
     * Drop the owner's expired devices (lazy cleanup — no scheduler needed).
     *
     * @param string $userType
     * @param string $userId
     * @return void
     */
    public static function pruneExpiredFor(string $userType, string $userId): void
    {
        static::where('user_type', $userType)->where('user_id', $userId)->where('expires_at', '<=', now())->delete();
    }

    /**
     * Record that the device was just used.
     *
     * @return void
     */
    public function touchUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }

    /**
     * Whether $token belongs to this device.
     *
     * @param string $token
     * @return bool
     */
    public function matches(string $token): bool
    {
        return $token !== '' && hash_equals($this->token_hash, static::hashToken($token));
    }

    /**
     * @param string $token
     * @return string
     */
    private static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
