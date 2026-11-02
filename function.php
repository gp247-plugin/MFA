<?php

if (!function_exists('mfa_is_user_enrolled')) {
    /**
     * Check if user has enabled MFA
     *
     * @param mixed $user
     * @param string $guard
     * @return bool
     */
    function mfa_is_user_enrolled($user, $guard)
    {
        if (!$user) {
            return false;
        }

        // Use the effective config (file defaults ⊕ DB overrides), same as every
        // other consumer, instead of reading config() directly.
        $guardConfig = mfa_get_guard_config($guard);
        if (!$guardConfig) {
            return false;
        }

        $mfaRecord = \App\GP247\Plugins\MFA\Models\TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $user->id)
            ->where('enabled', 1)
            ->first();

        return $mfaRecord !== null;
    }
}

if (!function_exists('mfa_verified_marks')) {
    /**
     * The session's MFA marks: [guard => user id] for every guard verified in it.
     *
     * WHY an array: up to 2.3.x the mark was a single `true` shared by every guard,
     * so passing MFA as an admin unlocked a customer signed in on the same browser.
     * A non-array value (that legacy `true`) counts as "nothing verified".
     *
     * @return array<string, string>
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-verified-session-per-guard
     */
    function mfa_verified_marks()
    {
        $marks = session(config('Plugins/MFA.session_key', 'mfa_verified'));

        return is_array($marks) ? $marks : [];
    }
}

if (!function_exists('mfa_is_verified')) {
    /**
     * Whether $user passed MFA for $guard in the current session.
     *
     * @param string|null $guard Guard key; null = default_guard.
     * @param mixed       $user  Signed-in user of that guard; null = Auth::guard($guard)->user().
     * @return bool
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-verified-session-per-guard
     */
    function mfa_is_verified($guard = null, $user = null)
    {
        $guard = $guard ?? config('Plugins/MFA.default_guard', 'customer');
        $user = $user ?? \Illuminate\Support\Facades\Auth::guard($guard)->user();
        if (!$user) {
            return false;
        }
        $marks = mfa_verified_marks();

        return isset($marks[$guard]) && (string) $marks[$guard] === (string) $user->getAuthIdentifier();
    }
}

if (!function_exists('mfa_set_verified')) {
    /**
     * Mark that $user passed MFA for $guard in the current session.
     *
     * @param string $guard
     * @param mixed  $user
     * @return void
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-verified-session-per-guard
     */
    function mfa_set_verified($guard, $user)
    {
        $marks = mfa_verified_marks();
        $marks[$guard] = (string) $user->getAuthIdentifier();
        session([config('Plugins/MFA.session_key', 'mfa_verified') => $marks]);
    }
}

if (!function_exists('mfa_clear_verified')) {
    /**
     * Forget the MFA mark of one guard (null = every guard).
     *
     * @param string|null $guard
     * @return void
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-verified-session-per-guard
     */
    function mfa_clear_verified($guard = null)
    {
        $sessionKey = config('Plugins/MFA.session_key', 'mfa_verified');
        if ($guard === null) {
            session()->forget($sessionKey);
            return;
        }
        $marks = mfa_verified_marks();
        unset($marks[$guard]);
        session([$sessionKey => $marks]);
    }
}

if (!function_exists('mfa_setting_fields')) {
    /**
     * The per-guard settings a site owner may edit from the admin screen.
     *
     * WHY: only these user-facing fields are overlaid from the DB; dev-level
     * fields (model, redirect_*) stay in config.php as package defaults.
     *
     * @return array<int, string>
     */
    function mfa_setting_fields()
    {
        return ['enabled', 'forced', 'qr_code_size', 'recovery_codes_count', 'window', 'trust_device_days'];
    }
}

if (!function_exists('mfa_setting_overrides')) {
    /**
     * User-set guard overrides stored in `admin_config` (code `MFA_config`).
     *
     * WHY: config.php is package-owned and gets overwritten on 1-click update
     * (ADR plugin-manager_extension-update-flow #7). Storing the site owner's
     * choices in admin_config — which the update flow preserves — is what keeps
     * them from being reset. Returns [guard => [field => value]].
     *
     * WHY not statically cached: a Livewire save() and the subsequent re-render
     * run in one PHP request; a static cache would show stale values right after
     * saving. The lookup is a single indexed row — cheap enough to read live.
     *
     * @return array<string, array<string, mixed>>
     */
    function mfa_setting_overrides()
    {
        $row = \GP247\Core\Models\AdminConfig::where('group', 'Plugins')
            ->where('key', 'MFA_config')
            ->first();

        $decoded = $row ? json_decode((string) $row->value, true) : null;

        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('mfa_effective_guards')) {
    /**
     * Effective guard config = file defaults ⊕ DB overrides (per guard).
     *
     * @return array<string, array<string, mixed>>
     */
    function mfa_effective_guards()
    {
        $defaults = (array) config('Plugins/MFA.guards', []);
        $overrides = mfa_setting_overrides();

        foreach ($defaults as $guard => $conf) {
            if (isset($overrides[$guard]) && is_array($overrides[$guard])) {
                // Only the whitelisted user-facing fields are overlaid.
                foreach (mfa_setting_fields() as $field) {
                    if (array_key_exists($field, $overrides[$guard])) {
                        $defaults[$guard][$field] = $overrides[$guard][$field];
                    }
                }
            }
        }

        return $defaults;
    }
}

if (!function_exists('mfa_get_guard_config')) {
    /**
     * Get effective MFA configuration for a specific guard (file ⊕ DB override).
     *
     * @param string $guard
     * @return array|null
     */
    function mfa_get_guard_config($guard)
    {
        $guards = mfa_effective_guards();
        return $guards[$guard] ?? null;
    }
}

if (!function_exists('mfa_save_guard_settings')) {
    /**
     * Persist per-guard user settings to `admin_config` (code `MFA_config`),
     * keeping only the whitelisted fields for guards that exist in config.php.
     *
     * @param array<string, array<string, mixed>> $settings guard => [field => value]
     * @return void
     */
    function mfa_save_guard_settings(array $settings)
    {
        $defaults = (array) config('Plugins/MFA.guards', []);
        $clean = [];

        foreach ($settings as $guard => $values) {
            if (!isset($defaults[$guard]) || !is_array($values)) {
                continue;
            }
            $row = [];
            foreach (mfa_setting_fields() as $field) {
                if (array_key_exists($field, $values)) {
                    $row[$field] = $values[$field];
                }
            }
            if ($row !== []) {
                $clean[$guard] = $row;
            }
        }

        \GP247\Core\Models\AdminConfig::updateOrCreate(
            ['group' => 'Plugins', 'key' => 'MFA_config'],
            [
                'code' => 'MFA_config',
                'store_id' => GP247_STORE_ID_GLOBAL,
                'value' => json_encode($clean),
            ]
        );
    }
}

if (!function_exists('mfa_generate_recovery_codes')) {
    /**
     * Generate recovery codes
     *
     * @param int $count
     * @return array
     */
    function mfa_generate_recovery_codes($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(6))), 0, 8));
        }
        return $codes;
    }
}

