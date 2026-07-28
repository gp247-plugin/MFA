{{--
    MFA users management — v2 (Livewire + TailAdmin). Replaces the legacy AdminLTE
    view + jQuery/AJAX reset flow: guard filter, paginated user list, and an
    inline reset action driven by wire:click + wire:confirm (native, no jQuery).
    UI text via gp247_language_render; dark-mode aware.

    @aidlc-unit plugin-mfa
    @aidlc-story GP247-v2-compat

    Variables: $users (paginator|null), $enabledGuards (array), $currentGuard (string|null), $errorMsg (string|null).
--}}
<div class="space-y-5">

    {{-- Header: title + back to dashboard --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                {{ gp247_language_render('Plugins/MFA::lang.users_management') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ gp247_language_render('Plugins/MFA::lang.users_management_desc') }}
            </p>
        </div>
        <x-gp247::button variant="secondary" size="sm" href="{{ route('admin_mfa.index') }}" wire:navigate>
            <i class="fas fa-arrow-left"></i>
            {{ gp247_language_render('Plugins/MFA::lang.back_to_dashboard') }}
        </x-gp247::button>
    </div>

    @if(count($guards) === 0)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-200">
            <i class="fas fa-exclamation-triangle"></i>
            {{ gp247_language_render('Plugins/MFA::lang.no_available_guards') }}
        </div>
    @else
        <x-gp247::card>
            {{-- Account type (guard) filter: every guard whose model exists,
                 so an admin can manage/reset MFA for any account type. --}}
            <div class="mb-4 flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ gp247_language_render('Plugins/MFA::lang.select_guard') }}:
                </span>
                @foreach($guards as $guard)
                    <x-gp247::button
                        size="sm"
                        :variant="$currentGuard === $guard ? 'primary' : 'ghost'"
                        wire:click="setGuard('{{ $guard }}')">
                        {{ gp247_language_render('Plugins/MFA::lang.guard_' . $guard) }}
                    </x-gp247::button>
                @endforeach
            </div>

            @if($errorMsg)
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900 dark:text-red-200">
                    <i class="fas fa-exclamation-triangle"></i> {{ $errorMsg }}
                </div>
            @else
                <x-gp247::table
                    :headers="[
                        'ID',
                        gp247_language_render('Plugins/MFA::lang.name'),
                        gp247_language_render('Plugins/MFA::lang.email'),
                        gp247_language_render('Plugins/MFA::lang.mfa_status'),
                        gp247_language_render('Plugins/MFA::lang.actions'),
                    ]"
                    :empty="($users && $users->count()) ? null : gp247_language_render('Plugins/MFA::lang.no_users_found')">
                    @if($users)
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $user->id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $user->display_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @php($tfa = $user->two_factor_auth)
                                    @if($tfa && $tfa->enabled)
                                        <x-gp247::badge color="green" class="gap-1">
                                            <i class="fas fa-check"></i> {{ gp247_language_render('Plugins/MFA::lang.enabled') }}
                                        </x-gp247::badge>
                                    @elseif($tfa)
                                        {{-- Record exists but not yet activated (setup abandoned midway). --}}
                                        <x-gp247::badge color="amber" class="gap-1">
                                            <i class="fas fa-hourglass-half"></i> {{ gp247_language_render('Plugins/MFA::lang.mfa_pending') }}
                                        </x-gp247::badge>
                                    @else
                                        <x-gp247::badge color="gray" class="gap-1">
                                            <i class="fas fa-times"></i> {{ gp247_language_render('Plugins/MFA::lang.not_setup') }}
                                        </x-gp247::badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    {{-- Reset is offered whenever a record exists (enabled OR pending)
                                         so a stuck/partial enrolment can also be cleared. --}}
                                    @if($tfa)
                                        <x-gp247::button
                                            size="sm"
                                            variant="danger"
                                            wire:click="resetUser('{{ $user->id }}')"
                                            wire:confirm="{{ gp247_language_render('Plugins/MFA::lang.reset_mfa_confirm') }}">
                                            <i class="fas fa-trash"></i> {{ gp247_language_render('Plugins/MFA::lang.reset_mfa') }}
                                        </x-gp247::button>
                                    @else
                                        <span class="text-gray-400">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </x-gp247::table>

                @if($users)
                    <div class="mt-4">{{ $users->links('gp247-admin::partials.pagination') }}</div>
                @endif
            @endif
        </x-gp247::card>
    @endif
</div>
