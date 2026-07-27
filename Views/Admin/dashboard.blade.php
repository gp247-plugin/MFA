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

    {{-- Guard configuration (read-only) --}}
    <x-gp247::card :title="gp247_language_render('Plugins/MFA::lang.guard_settings')">
        <x-slot:header>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                    {{ gp247_language_render('Plugins/MFA::lang.guard_settings') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle"></i>
                    {{ gp247_language_render('Plugins/MFA::lang.config_readonly_note') }}
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-gray-700">app/GP247/Plugins/MFA/config.php</code>
                </p>
            </div>
        </x-slot:header>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach($guardsConfig as $guard => $guardConfig)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100">
                            {{ gp247_language_render('Plugins/MFA::lang.guard_' . $guard) }}
                        </h4>
                        <div class="flex items-center gap-2">
                            @if(!empty($guardConfig['enabled']))
                                <x-gp247::badge color="green">{{ gp247_language_render('Plugins/MFA::lang.enabled') }}</x-gp247::badge>
                            @else
                                <x-gp247::badge color="gray">{{ gp247_language_render('Plugins/MFA::lang.disabled') }}</x-gp247::badge>
                            @endif
                            @if(!empty($guardConfig['forced']))
                                <x-gp247::badge color="amber">{{ gp247_language_render('Plugins/MFA::lang.forced') }}</x-gp247::badge>
                            @endif
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MFA::lang.qr_code_size') }}</dt>
                        <dd class="text-gray-800 dark:text-gray-100">{{ $guardConfig['qr_code_size'] ?? '-' }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MFA::lang.recovery_codes_count') }}</dt>
                        <dd class="text-gray-800 dark:text-gray-100">{{ $guardConfig['recovery_codes_count'] ?? '-' }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/MFA::lang.window') }}</dt>
                        <dd class="text-gray-800 dark:text-gray-100">{{ $guardConfig['window'] ?? '-' }}</dd>
                    </dl>
                </div>
            @endforeach
        </div>
    </x-gp247::card>
</div>
