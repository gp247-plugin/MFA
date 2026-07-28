<?php
#App\GP247\Plugins\MFA\Admin\Livewire\MfaUsers.php

namespace App\GP247\Plugins\MFA\Admin\Livewire;

use App\GP247\Plugins\MFA\Models\TwoFactorAuth;
use GP247\Core\AdminShell\Infrastructure\GP247AdminComponent;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * MFA users management (v2 port of the legacy AdminLTE
 * AdminController@usersManagement + resetUserMFA) — a paginated per-guard user
 * list on the TailAdmin shell with an inline "reset MFA" action, replacing the
 * old jQuery + AJAX POST flow with a native Livewire action (wire:click +
 * wire:confirm) and a toast notification.
 *
 * Gated by `admin_mfa` (same convention as MfaDashboard / the News plugin).
 *
 * @aidlc-unit plugin-mfa
 * @aidlc-story GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005, ADR-007
 */
class MfaUsers extends GP247AdminComponent
{
    use WithPagination;

    /**
     * Permission slug gating this component.
     *
     * @var string|null
     */
    protected ?string $permission = 'admin_mfa';

    /**
     * Selected guard key, kept in the URL so the list is shareable/bookmarkable
     * and survives pagination — mirrors the legacy `/users/{guard}` segment.
     *
     * @var string
     */
    #[Url]
    public string $guard = '';

    /**
     * Guard keys that can actually be managed here: those whose user model
     * class is present on this install.
     *
     * WHY: user management must cover any account type that exists, regardless
     * of whether MFA enforcement is currently enabled for it (an admin still
     * needs to inspect/reset a customer's MFA). Guards whose model is absent
     * (e.g. vendor/pmo plugins not installed) are hidden so their list never
     * errors — mirrors the enrolment logic in function.php.
     *
     * @return array<int, string>
     */
    public function availableGuards(): array
    {
        $available = [];
        foreach ((array) config('Plugins/MFA.guards', []) as $key => $guardConfig) {
            $model = $guardConfig['model'] ?? null;
            if ($model && class_exists($model)) {
                $available[] = $key;
            }
        }

        return $available;
    }

    /**
     * Resolve the guard the screen should show: the selected one when it is
     * available, otherwise the first available guard.
     *
     * @return string|null
     */
    public function currentGuard(): ?string
    {
        $available = $this->availableGuards();
        if ($this->guard !== '' && in_array($this->guard, $available, true)) {
            return $this->guard;
        }

        return $available[0] ?? null;
    }

    /**
     * Switch guard and reset pagination to page 1.
     *
     * WHY: a read interaction (does not mutate data) so it stays outside
     * authorizeAction — the base component already authorized the view on mount.
     *
     * @param string $guard
     * @return void
     */
    public function setGuard(string $guard): void
    {
        $this->guard = $guard;
        $this->resetPage();
    }

    /**
     * Reset (delete) the MFA enrolment of a user for the current guard.
     *
     * @param int|string $userId
     * @return void
     */
    public function resetUser($userId): void
    {
        $this->authorizeAction('resetUser');

        $guardConfig = mfa_get_guard_config($this->currentGuard());
        if (!$guardConfig) {
            $this->notify('error', gp247_language_render('Plugins/MFA::lang.invalid_guard'));
            return;
        }

        $record = TwoFactorAuth::where('user_type', $guardConfig['model'])
            ->where('user_id', $userId)
            ->first();

        if ($record) {
            $record->delete();
            $this->notify('success', gp247_language_render('Plugins/MFA::lang.mfa_reset_successfully'));
        } else {
            $this->notify('warning', gp247_language_render('Plugins/MFA::lang.mfa_not_setup'));
        }
    }

    /**
     * Build the paginated user list for the current guard, decorated with a
     * display name and the matching MFA record.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    public function users()
    {
        $guardConfig = mfa_get_guard_config($this->currentGuard());
        if (!$guardConfig) {
            return null;
        }

        $modelClass = $guardConfig['model'];
        $users = $modelClass::paginate(20);

        $mfaRecords = TwoFactorAuth::where('user_type', $modelClass)
            ->whereIn('user_id', $users->pluck('id')->all())
            ->get()
            ->keyBy('user_id');

        $users->getCollection()->transform(function ($user) use ($mfaRecords) {
            // WHY: user models differ across guards (name vs first/last name);
            // derive a single display field so the view stays model-agnostic.
            if (isset($user->name)) {
                $user->display_name = $user->name;
            } elseif (isset($user->first_name, $user->last_name)) {
                $user->display_name = trim($user->first_name . ' ' . $user->last_name);
            } elseif (isset($user->first_name)) {
                $user->display_name = $user->first_name;
            } else {
                $user->display_name = $user->email;
            }

            $user->two_factor_auth = $mfaRecords->get($user->id);
            return $user;
        });

        return $users;
    }

    /**
     * Render the users screen inside the shared TailAdmin layout.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $currentGuard = $this->currentGuard();
        $users = null;
        $errorMsg = null;

        if ($currentGuard !== null) {
            // WHY: a guard may target a model whose table is absent on this site;
            // surface the error inline instead of 500-ing the whole screen.
            try {
                $users = $this->users();
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
            }
        }

        return view('Plugins/MFA::Admin.users', [
            'users' => $users,
            'guards' => $this->availableGuards(),
            'currentGuard' => $currentGuard,
            'errorMsg' => $errorMsg,
        ])->layout('gp247-admin::layouts.admin', [
            'title' => gp247_language_render('Plugins/MFA::lang.users_management'),
        ]);
    }
}
