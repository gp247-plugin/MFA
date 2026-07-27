<?php
#App\GP247\Plugins\MFA\Admin\Livewire\MfaDashboard.php

namespace App\GP247\Plugins\MFA\Admin\Livewire;

use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;

/**
 * MFA admin dashboard (v2 port of the legacy AdminLTE AdminController@index) —
 * a read-only overview on the TailAdmin shell: MFA adoption stats per guard and
 * the guard configuration as declared in the plugin `config.php`.
 *
 * Extends GP247AdminComponent so the screen inherits Layer-2 RBAC (read
 * authorization on mount) and the shared admin layout, exactly like core/shop
 * admin screens. Gated by `admin_mfa` (super administrator bypasses; granular
 * roles must be granted the slug — same convention as the News plugin).
 *
 * @aidlc-unit plugin-mfa
 * @aidlc-story GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005, ADR-007
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
     * All guards declared in the plugin config (guard key => settings array).
     *
     * @return array<string, array<string, mixed>>
     */
    public function guardsConfig(): array
    {
        return (array) config('Plugins/MFA.guards', []);
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
