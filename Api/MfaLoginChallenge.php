<?php

namespace App\GP247\Plugins\MFA\Api;

use App\GP247\Plugins\MFA\Helpers\TwoFactorHelper;
use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use GP247\Core\Api\Contracts\LoginChallenge;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Second sign-in step of the GP247 REST API (v2) for accounts protected by this plugin:
 * after the password, the app sends the 6-digit authenticator code or a recovery code to
 * `POST login/challenge`. Registered with core's `gp247-config.api.login_challenge`
 * (customer and admin) by Provider.php when core offers the seam (gp247/core 3.6+).
 *
 * A guard with "forced" MFA blocks API sign-in of accounts that have not set MFA up yet
 * (method `setup_required`): setting it up needs the website. A code accepted through the
 * API cannot be accepted again within its time window.
 */
class MfaLoginChallenge implements LoginChallenge
{
    /** Cache key prefix of the last accepted time step, per MFA record. */
    private const REPLAY_PREFIX = 'mfa-api-totp:';

    /** How long the last accepted time step is remembered (seconds). */
    private const REPLAY_TTL = 600;

    /**
     * {@inheritDoc}
     */
    public function required(Authenticatable $owner, Request $request): bool
    {
        $guard = $this->guardOf($owner);
        if (!$guard) {
            return false;
        }
        return $this->record($owner, $guard) !== null || !empty($guard['forced']);
    }

    /**
     * {@inheritDoc}
     */
    public function describe(Authenticatable $owner): array
    {
        $guard = $this->guardOf($owner);
        if (!$guard || !$this->record($owner, $guard)) {
            return ['method' => 'setup_required', 'meta' => [
                'message' => 'Two-factor authentication is required: turn it on in your account on the website first',
            ]];
        }
        return ['method' => 'totp', 'meta' => [
            'digits'         => 6,
            'recovery_codes' => (bool) config('Plugins/MFA.enable_recovery_codes', true),
        ]];
    }

    /**
     * {@inheritDoc}
     */
    public function verify(Authenticatable $owner, Request $request): bool
    {
        $guard = $this->guardOf($owner);
        $record = $guard ? $this->record($owner, $guard) : null;
        if (!$record) {
            return false;
        }

        $code = trim((string) $request->input('code', ''));
        if (strlen($code) === 6 && ctype_digit($code)) {
            return $this->verifyTotp($record, $code, (int) ($guard['window'] ?? 1));
        }

        $recovery = trim((string) $request->input('recovery_code', ''));
        if ($recovery !== '' && config('Plugins/MFA.enable_recovery_codes', true) && $record->hasRecoveryCode($recovery)) {
            $record->useRecoveryCode($recovery);
            $record->forceFill(['last_used_at' => now()])->save();
            return true;
        }
        return false;
    }

    /**
     * Check an authenticator code; a code is accepted only for a time step newer than the
     * last one accepted through the API.
     *
     * @param TwoFactorAuth $record
     * @param string        $code
     * @param int           $window
     * @return bool
     */
    private function verifyTotp(TwoFactorAuth $record, string $code, int $window): bool
    {
        $key = self::REPLAY_PREFIX . $record->id;
        $step = $this->helper()->verifyCodeNewer(
            $record->getDecryptedSecretAttribute(), $code, (int) Cache::get($key, 0), $window
        );
        if ($step === false) {
            return false;
        }
        Cache::put($key, (int) $step, self::REPLAY_TTL);
        $record->forceFill(['last_used_at' => now()])->save();
        return true;
    }

    /**
     * Effective config of the enabled guard whose user model is $owner's class, or null.
     *
     * @param Authenticatable $owner
     * @return array<string, mixed>|null
     */
    private function guardOf(Authenticatable $owner): ?array
    {
        foreach (mfa_effective_guards() as $conf) {
            if (!empty($conf['enabled']) && !empty($conf['model']) && is_a($owner, $conf['model'])) {
                return $conf;
            }
        }
        return null;
    }

    /**
     * Active MFA record of $owner for $guard, or null.
     *
     * @param Authenticatable      $owner
     * @param array<string, mixed> $guard
     * @return TwoFactorAuth|null
     */
    private function record(Authenticatable $owner, array $guard): ?TwoFactorAuth
    {
        return TwoFactorAuth::where('user_type', $guard['model'])
            ->where('user_id', (string) $owner->getAuthIdentifier())
            ->where('enabled', 1)
            ->first();
    }

    /**
     * @return TwoFactorHelper
     */
    protected function helper(): TwoFactorHelper
    {
        return app(TwoFactorHelper::class);
    }
}
