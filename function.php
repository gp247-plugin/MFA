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

if (!function_exists('mfa_is_verified')) {
    /**
     * Check if current session has verified MFA
     *
     * @return bool
     */
    function mfa_is_verified()
    {
        $sessionKey = config('Plugins/MFA.session_key', 'mfa_verified');
        return session($sessionKey, false) === true;
    }
}

if (!function_exists('mfa_set_verified')) {
    /**
     * Mark current session as MFA verified
     *
     * @return void
     */
    function mfa_set_verified()
    {
        $sessionKey = config('Plugins/MFA.session_key', 'mfa_verified');
        session([$sessionKey => true]);
    }
}

if (!function_exists('mfa_clear_verified')) {
    /**
     * Clear MFA verification from session
     *
     * @return void
     */
    function mfa_clear_verified()
    {
        $sessionKey = config('Plugins/MFA.session_key', 'mfa_verified');
        session()->forget($sessionKey);
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
        return ['enabled', 'forced', 'qr_code_size', 'recovery_codes_count', 'window'];
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

