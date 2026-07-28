<?php
#App\GP247\Plugins\MFA\Admin\Livewire\MfaDashboard.php

namespace App\GP247\Plugins\MFA\Admin\Livewire;

use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;

/**
 * MFA admin dashboard (v2 port of the legacy AdminLTE AdminController@index) —
 * MFA adoption stats per guard plus an EDITABLE per-guard settings form on the
 * TailAdmin shell.
 *
 * Settings are read/written through admin_config (helper mfa_* in function.php),
 * NOT config.php: config.php holds package defaults that a 1-click plugin update
 * overwrites, whereas admin_config is preserved by the update flow — so the site
 * owner's choices survive updates (ADR plugin-manager_extension-update-flow #7,
 * RISK-OPS-plugin-config-file-overwrite).
 *
 * Extends GP247AdminComponent for Layer-2 RBAC + shared layout. Gated by
 * `admin_mfa` (super administrator bypasses; granular roles need the slug).
 *
 * @aidlc-unit plugin-mfa
 * @aidlc-story US-PLG-004, GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005, ADR-007, plugin-manager_extension-update-flow
 */
class MfaDashboard extends GP247AdminComponent
{
    /**
     * Permission slug gating this component.
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_mfa';

    /**
     * Editable per-guard settings, keyed by guard: enabled, forced,
     * qr_code_size, recovery_codes_count, window. Bound to the settings form.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $settings = [];

    /**
     * Livewire lifecycle hook: authorize (parent) then hydrate the form from the
     * effective config (file defaults ⊕ admin_config overrides).
     *
     * @return void
     */
    public function mount(): void
    {
        parent::mount();
        $this->loadSettings();
    }

    /**
     * Populate $this->settings from the effective guard config.
     *
     * @return void
     */
    protected function loadSettings(): void
    {
        $this->settings = [];
        foreach (mfa_effective_guards() as $guard => $conf) {
            $this->settings[$guard] = [
                'enabled' => (int) ($conf['enabled'] ?? 0),
                'forced' => (int) ($conf['forced'] ?? 0),
                'qr_code_size' => (int) ($conf['qr_code_size'] ?? 200),
                'recovery_codes_count' => (int) ($conf['recovery_codes_count'] ?? 8),
                'window' => (int) ($conf['window'] ?? 1),
            ];
        }
    }

    /**
     * Persist the edited settings to admin_config (update-safe store).
     *
     * @return void
     */
    public function save(): void
    {
        $this->authorizeAction('save');

        $this->validate([
            'settings.*.enabled' => ['boolean'],
            'settings.*.forced' => ['boolean'],
            'settings.*.qr_code_size' => ['integer', 'min:50', 'max:1000'],
            'settings.*.recovery_codes_count' => ['integer', 'min:1', 'max:50'],
            'settings.*.window' => ['integer', 'min:0', 'max:10'],
        ]);

        mfa_save_guard_settings($this->settings);
        $this->loadSettings();
        $this->notify('success', gp247_language_render('Plugins/MFA::lang.settings_saved'));
    }

    /**
     * All guards with their effective config (for stats + display).
     *
     * @return array<string, array<string, mixed>>
     */
    public function guardsConfig(): array
    {
        return mfa_effective_guards();
    }

    /**
     * Build MFA adoption statistics for every configured guard.
     *
     * WHY: iterate over the config guard keys directly (not a hardcoded list) so
     * a guard added/removed in config.php is reflected without touching this
     * class — this also fixes the legacy `pmo` vs `pmo_partner` key mismatch.
     *
     * @return array<string, array{total_users:int, mfa_enabled:int, mfa_setup:int, percentage:float}>
     */
    public function statistics(): array
    {
        $stats = [];

        foreach ($this->guardsConfig() as $guard => $guardConfig) {
            $modelClass = $guardConfig['model'] ?? null;
            if (!$modelClass || !class_exists($modelClass)) {
                continue;
            }

            // WHY: a guard may point at a model whose table is not installed on
            // this site (e.g. vendor/pmo plugins absent) — never let counting
            // break the whole dashboard.
            try {
                $totalUsers = $modelClass::count();
            } catch (\Throwable $e) {
                $totalUsers = 0;
            }

            $mfaEnabled = TwoFactorAuth::where('user_type', $modelClass)->where('enabled', 1)->count();
            $mfaSetup = TwoFactorAuth::where('user_type', $modelClass)->where('enabled', 0)->count();

            $stats[$guard] = [
                'total_users' => $totalUsers,
                'mfa_enabled' => $mfaEnabled,
                'mfa_setup' => $mfaSetup,
                'percentage' => $totalUsers > 0 ? round(($mfaEnabled / $totalUsers) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Render the dashboard inside the shared TailAdmin layout.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('Plugins/MFA::Admin.dashboard', [
            'guardsConfig' => $this->guardsConfig(),
            'stats' => $this->statistics(),
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('Plugins/MFA::lang.admin_title'),
        ]);
    }
}
