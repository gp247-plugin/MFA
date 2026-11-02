<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ gp247_language_render('Plugins/MFA::lang.manage_mfa') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .manage-container {
            max-width: 700px;
            width: 100%;
            padding: 20px;
        }
        .manage-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="manage-container">
        <div class="manage-card">
            <div class="card-header">
                <i class="fas fa-cog fa-3x mb-3"></i>
                <h2>{{ gp247_language_render('Plugins/MFA::lang.manage_mfa') }}</h2>
            </div>

            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                <!-- MFA Status -->
                <div class="mb-4">
                    <h5 class="mb-3">{{ gp247_language_render('Plugins/MFA::lang.mfa_status') }}</h5>
                    
                    @if($mfaRecord && $mfaRecord->enabled)
                        <span class="status-badge status-enabled">
                            <i class="fas fa-check-circle"></i> {{ gp247_language_render('Plugins/MFA::lang.mfa_enabled') }}
                        </span>

                        <div class="mt-4">
                            <div class="info-row">
                                <strong>{{ gp247_language_render('Plugins/MFA::lang.mfa_enabled_since') }}</strong>
                                <span>{{ $mfaRecord->enabled_at ? $mfaRecord->enabled_at->format('Y-m-d H:i') : '-' }}</span>
                            </div>
                            <div class="info-row">
                                <strong>{{ gp247_language_render('Plugins/MFA::lang.last_used') }}</strong>
                                <span>{{ $mfaRecord->last_used_at ? $mfaRecord->last_used_at->format('Y-m-d H:i') : gp247_language_render('Plugins/MFA::lang.never_used') }}</span>
                            </div>
                            <div class="info-row">
                                <strong>{{ gp247_language_render('Plugins/MFA::lang.recovery_codes') }}</strong>
                                <span>
                                    @php
                                        $codes = $mfaRecord->getDecryptedRecoveryCodesAttribute();
                                        $codesCount = $codes ? count($codes) : 0;
                                    @endphp
                                    {{ $codesCount }} {{ gp247_language_render('Plugins/MFA::lang.recovery_codes') }}
                                </span>
                            </div>
                        </div>

                        <!-- Recovery Codes Management -->
                        <div class="mt-4">
                            <h6 class="mb-3">{{ gp247_language_render('Plugins/MFA::lang.recovery_codes') }}</h6>
                            
                            <div class="d-grid gap-2 mb-3">
                                <a href="{{ route('mfa.recovery_codes', $guard) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> {{ gp247_language_render('Plugins/MFA::lang.recovery_codes') }}
                                </a>
                                
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#regenerateModal">
                                    <i class="fas fa-sync"></i> {{ gp247_language_render('Plugins/MFA::lang.regenerate_recovery_codes') }}
                                </button>
                            </div>
                        </div>

                        <!-- Trusted devices (US-mfa-trusted-device-revoke) -->
                        <div class="mt-4">
                            <h6 class="mb-2">{{ gp247_language_render('Plugins/MFA::lang.trusted_devices') }}</h6>
                            <p class="text-muted small">{{ gp247_language_render('Plugins/MFA::lang.trusted_devices_desc') }}</p>

                            @if($trustedDevices->isEmpty())
                                <p class="text-muted small">{{ gp247_language_render('Plugins/MFA::lang.trusted_devices_none') }}</p>
                            @else
                                <ul class="list-group mb-3">
                                    @foreach($trustedDevices as $device)
                                        <li class="list-group-item d-flex justify-content-between align-items-start gap-2"
                                            data-testid="mfa-manage-trusted-device-row">
                                            <div class="small">
                                                <div class="fw-bold text-break">
                                                    {{ \Illuminate\Support\Str::limit($device->user_agent ?: gp247_language_render('Plugins/MFA::lang.trusted_device_unknown'), 90) }}
                                                    @if($device->matches($currentToken))
                                                        <span class="badge bg-success">{{ gp247_language_render('Plugins/MFA::lang.trusted_device_current') }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted">
                                                    {{ gp247_language_render('Plugins/MFA::lang.trusted_device_created') }}: {{ optional($device->created_at)->format('Y-m-d H:i') }}
                                                    · {{ gp247_language_render('Plugins/MFA::lang.last_used') }}: {{ $device->last_used_at ? $device->last_used_at->format('Y-m-d H:i') : gp247_language_render('Plugins/MFA::lang.never_used') }}
                                                    · {{ gp247_language_render('Plugins/MFA::lang.trusted_device_expires') }}: {{ $device->expires_at->format('Y-m-d') }}
                                                </div>
                                            </div>
                                            <form action="{{ route('mfa.trusted_devices.revoke') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="guard" value="{{ $guard }}">
                                                <input type="hidden" name="id" value="{{ $device->id }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        data-testid="mfa-manage-trusted-device-revoke">
                                                    {{ gp247_language_render('Plugins/MFA::lang.trusted_device_revoke') }}
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>

                                <form action="{{ route('mfa.trusted_devices.revoke_all') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="guard" value="{{ $guard }}">
                                    <button type="submit" class="btn btn-outline-danger"
                                            data-testid="mfa-manage-trusted-devices-revoke-all">
                                        <i class="fas fa-ban"></i> {{ gp247_language_render('Plugins/MFA::lang.trusted_devices_revoke_all') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Disable MFA -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="text-danger mb-3">
                                <i class="fas fa-exclamation-triangle"></i> {{ gp247_language_render('Plugins/MFA::lang.disable_mfa') }}
                            </h6>
                            <p class="text-muted small">{{ gp247_language_render('Plugins/MFA::lang.disable_mfa_desc') }}</p>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disableModal">
                                <i class="fas fa-times-circle"></i> {{ gp247_language_render('Plugins/MFA::lang.disable_mfa') }}
                            </button>
                        </div>
                    @else
                        <span class="status-badge status-disabled">
                            <i class="fas fa-times-circle"></i> {{ gp247_language_render('Plugins/MFA::lang.mfa_disabled') }}
                        </span>

                        <div class="mt-4">
                            <p class="text-muted">{{ gp247_language_render('Plugins/MFA::lang.description') }}</p>
                            <a href="{{ route('mfa.setup.show', $guard) }}" class="btn btn-primary">
                                <i class="fas fa-shield-alt"></i> {{ gp247_language_render('Plugins/MFA::lang.setup_mfa') }}
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Back button -->
                <div class="mt-4">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> {{ gp247_language_render('Plugins/MFA::lang.back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Disable Modal -->
    @if($mfaRecord && $mfaRecord->enabled)
    <div class="modal fade" id="disableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ gp247_language_render('Plugins/MFA::lang.disable_mfa') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('mfa.disable') }}" method="POST">
                    @csrf
                    <input type="hidden" name="guard" value="{{ $guard }}">
                    
                    <div class="modal-body">
                        <p>{{ gp247_language_render('Plugins/MFA::lang.disable_mfa_desc') }}</p>
                        <div class="mb-3">
                            <label class="form-label">{{ gp247_language_render('Plugins/MFA::lang.confirm_password') }}</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ gp247_language_render('Plugins/MFA::lang.cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ gp247_language_render('Plugins/MFA::lang.disable_mfa') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Regenerate Recovery Codes Modal -->
    <div class="modal fade" id="regenerateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ gp247_language_render('Plugins/MFA::lang.regenerate_recovery_codes') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('mfa.recovery_codes.regenerate') }}" method="POST">
                    @csrf
                    <input type="hidden" name="guard" value="{{ $guard }}">
                    
                    <div class="modal-body">
                        <p>{{ gp247_language_render('Plugins/MFA::lang.recovery_codes_warning') }}</p>
                        <div class="mb-3">
                            <label class="form-label">{{ gp247_language_render('Plugins/MFA::lang.confirm_password') }}</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ gp247_language_render('Plugins/MFA::lang.cancel') }}</button>
                        <button type="submit" class="btn btn-warning">{{ gp247_language_render('Plugins/MFA::lang.regenerate_recovery_codes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

