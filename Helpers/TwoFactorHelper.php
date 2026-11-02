<?php

namespace App\GP247\Plugins\MFA\Helpers;

use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;

class TwoFactorHelper
{
    /**
     * Cache key prefix of the last accepted TOTP time step, per MFA record.
     *
     * WHY shared: the website and REST API v2 read the same key, so a code used
     * on one channel cannot be replayed on the other. Value kept from 2.3.0.
     */
    public const REPLAY_PREFIX = 'mfa-api-totp:';

    /** How long the last accepted time step is remembered (seconds). */
    public const REPLAY_TTL = 600;

    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new secret key
     *
     * @return string
     */
    public function generateSecretKey()
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR code URL
     *
     * @param string $email
     * @param string $secret
     * @param string $appName
     * @return string
     */
    public function getQRCodeUrl($email, $secret, $appName = null)
    {
        $appName = $appName ?? config('Plugins/MFA.app_name', 'GP247');
        return $this->google2fa->getQRCodeUrl($appName, $email, $secret);
    }

    /**
     * Generate QR code as SVG
     *
     * @param string $email
     * @param string $secret
     * @param string $appName
     * @param int $size
     * @return string
     */
    public function getQRCodeSvg($email, $secret, $appName = null, $size = 200)
    {
        $qrCodeUrl = $this->getQRCodeUrl($email, $secret, $appName);
        
        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Verify a one-time password
     *
     * @param string $secret
     * @param string $code
     * @param int $window
     * @return bool
     */
    public function verifyCode($secret, $code, $window = 1)
    {
        return $this->google2fa->verifyKey($secret, $code, $window);
    }

    /**
     * Verify a code only for a time step newer than $lastTimestep (replay protection).
     *
     * @param string $secret
     * @param string $code
     * @param int    $lastTimestep Last accepted time step (0 = none).
     * @param int    $window
     * @return int|false The accepted time step, or false.
     */
    public function verifyCodeNewer($secret, $code, $lastTimestep, $window = 1)
    {
        $step = $this->google2fa->verifyKeyNewer($secret, $code, (int) $lastTimestep, $window);
        return is_int($step) ? $step : false;
    }

    /**
     * Accept an authenticator code for $record at most once per time step, on any channel.
     *
     * @param TwoFactorAuth $record Enabled MFA record.
     * @param string        $code   6-digit code.
     * @param int           $window Allowed drift in 30-second steps.
     * @return bool True when accepted (the step is remembered and last_used_at updated).
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-web-verify-brute-force, US-mfa-api-login-challenge
     * @aidlc-adr mfa-plugin_web-verify-throttle
     */
    public function acceptCodeOnce(TwoFactorAuth $record, string $code, int $window = 1): bool
    {
        $key = self::REPLAY_PREFIX . $record->id;
        $step = $this->verifyCodeNewer($record->getDecryptedSecretAttribute(), $code, (int) Cache::get($key, 0), $window);
        if ($step === false) {
            return false;
        }
        Cache::put($key, (int) $step, self::REPLAY_TTL);
        $record->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    /**
     * Get current timestamp
     *
     * @return int
     */
    public function getCurrentTimestamp()
    {
        return $this->google2fa->getTimestamp();
    }

    /**
     * Generate recovery codes
     *
     * @param int $count
     * @return array
     */
    public function generateRecoveryCodes($count = 8)
    {
        return mfa_generate_recovery_codes($count);
    }

    /**
     * Get Google2FA instance
     *
     * @return Google2FA
     */
    public function getGoogle2FA()
    {
        return $this->google2fa;
    }
}

