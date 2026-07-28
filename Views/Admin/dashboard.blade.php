{{--
    MFA admin dashboard — v2 (Livewire + TailAdmin). Replaces the legacy AdminLTE
    view that extended the now-deleted `gp247-core::layout`. Read-only overview:
    MFA adoption per guard (stat cards) + guard configuration as declared in
    config.php. UI text via gp247_language_render; dark-mode aware.

    @aidlc-unit plugin-mfa
    @aidlc-story GP247-v2-compat

    Variables: $guardsConfig (guard key => settings), $stats (guard key => metrics).
--}}
<div class="space-y-5">

    {{-- Header: title + link to the users screen --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
            {{ gp247_language_render('Plugins/MFA::lang.admin_title') }}
        </h2>
        <x-gp247::button variant="primary" size="sm" href="{{ route('admin_mfa.users') }}" wire:navigate>
            <i class="fas fa-users"></i>
            {{ gp247_language_render('Plugins/MFA::lang.users_management') }}
        </x-gp247::button>
    </div>

    {{-- Adoption stats per guard --}}
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ gp247_language_render('Plugins/MFA::lang.statistics') }}
        </h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($guardsConfig as $guard => $guardConfig)
                @php($metric = $stats[$guard] ?? ['mfa_enabled' => 0, 'total_users' => 0, 'percentage' => 0])
                @php($color = ($metric['percentage'] ?? 0) >= 50 ? 'emerald' : (($metric['percentage'] ?? 0) > 0 ? 'amber' : 'sky'))
                <x-gp247::stat-card
                    :label="gp247_language_render('Plugins/MFA::lang.guard_' . $guard) . ' — ' . ($metric['percentage'] ?? 0) . '%'"
                    :value="($metric['mfa_enabled'] ?? 0) . ' / ' . ($metric['total_users'] ?? 0)"
                    icon="fas fa-shield-alt"
                    :color="$color" />
            @endforeach
        </div>
    </div>

    {{-- Guard configuration — editable, persisted to admin_config (update-safe,
         ADR plugin-manager_extension-update-flow #7 / RISK-OPS-plugin-config-file-overwrite).
         Bound to $settings; save() writes the DB override row. --}}
    <form wire:submit="save">
        <x-gp247::card>
            <x-slot:header>
                <div class="flex w-full flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                            {{ gp247_language_render('Plugins/MFA::lang.guard_settings') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-info-circle"></i>
                            {{ gp247_language_render('Plugins/MFA::lang.settings_editable_note') }}
                        </p>
                    </div>
                    <x-gp247::button type="submit" variant="primary" size="sm">
                        <i class="fas fa-save"></i> {{ gp247_language_render('Plugins/MFA::lang.save') }}
                    </x-gp247::button>
                </div>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($guardsConfig as $guard => $guardConfig)
                    @php($isEnabled = !empty($settings[$guard]['enabled']))
                    {{-- Card-level colour tells enabled from disabled at a glance (live via
                         wire:model.live on the toggle). Classes are on the core Tailwind
                         safelist (RISK-TECH-002) so they exist in the built bundle. --}}
                    <div class="rounded-xl border p-4 {{ $isEnabled
                        ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-900'
                        : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' }}">
                        <h4 class="mb-3 font-semibold {{ $isEnabled
                            ? 'text-emerald-700 dark:text-emerald-300'
                            : 'text-gray-500 dark:text-gray-400' }}">
                            {{ gp247_language_render('Plugins/MFA::lang.guard_' . $guard) }}
                        </h4>

                        <div class="mb-4 flex flex-wrap gap-6">
                            <x-gp247::checkbox
                                :label="gp247_language_render('Plugins/MFA::lang.enabled')"
                                wire:model.live="settings.{{ $guard }}.enabled" value="1" />
                            <x-gp247::checkbox
                                :label="gp247_language_render('Plugins/MFA::lang.forced')"
                                wire:model.live="settings.{{ $guard }}.forced" value="1" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <x-gp247::input type="number" min="50" max="1000"
                                :label="gp247_language_render('Plugins/MFA::lang.qr_code_size')"
                                wire:model="settings.{{ $guard }}.qr_code_size"
                                :error="$errors->first('settings.'.$guard.'.qr_code_size')" />
                            <x-gp247::input type="number" min="1" max="50"
                                :label="gp247_language_render('Plugins/MFA::lang.recovery_codes_count')"
                                wire:model="settings.{{ $guard }}.recovery_codes_count"
                                :error="$errors->first('settings.'.$guard.'.recovery_codes_count')" />
                            <x-gp247::input type="number" min="0" max="10"
                                :label="gp247_language_render('Plugins/MFA::lang.window')"
                                wire:model="settings.{{ $guard }}.window"
                                :error="$errors->first('settings.'.$guard.'.window')" />
                        </div>
                    </div>
                @endforeach
            </div>
        </x-gp247::card>
    </form>

    {{-- Configuration guide: how a site owner turns MFA on/off and tunes it --}}
    <x-gp247::card :title="gp247_language_render('Plugins/MFA::lang.config_guide_title')">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
            {{ gp247_language_render('Plugins/MFA::lang.config_guide_intro') }}
        </p>
        <ul class="space-y-2.5 text-sm text-gray-700 dark:text-gray-200">
            @foreach(['config_guide_enable', 'config_guide_forced', 'config_guide_qr', 'config_guide_recovery', 'config_guide_window'] as $step)
                <li class="flex gap-2">
                    <i class="fas fa-circle-check mt-0.5 text-emerald-500"></i>
                    <span>{{ gp247_language_render('Plugins/MFA::lang.' . $step) }}</span>
                </li>
            @endforeach
        </ul>
    </x-gp247::card>
</div>
