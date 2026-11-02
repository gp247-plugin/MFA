<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ gp247_language_render('Plugins/MFA::lang.verification_required') }}</title>
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
        .verify-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        .verify-card {
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
        .code-input {
            font-size: 24px;
            letter-spacing: 10px;
            text-align: center;
            font-family: monospace;
        }
        .btn-toggle {
            cursor: pointer;
            color: #667eea;
            text-decoration: none;
        }
        .btn-toggle:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="card-header">
                <i class="fas fa-shield-alt fa-3x mb-3"></i>
                <h2>{{ gp247_language_render('Plugins/MFA::lang.verification_required') }}</h2>
                <p class="mb-0">{{ gp247_language_render('Plugins/MFA::lang.verification_desc') }}</p>
            </div>

            <div class="card-body p-4">
                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
                    </div>
                @endif

                <!-- Authenticator Code Form -->
                <form action="{{ route('mfa.verify') }}" method="POST" id="authenticator-form">
                    @csrf
                    <input type="hidden" name="guard" value="{{ $guard }}">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-mobile-alt"></i> {{ gp247_language_render('Plugins/MFA::lang.enter_code') }}
                        </label>
                        <input type="text" 
                               name="code" 
                               class="form-control form-control-lg code-input" 
                               placeholder="000000"
                               maxlength="6"
                               pattern="[0-9]{6}"
                               required
                               autofocus>
                        <small class="form-text text-muted">
                            {{ gp247_language_render('Plugins/MFA::lang.verify_code_desc') }}
                        </small>
                    </div>

                    {{-- "Trust this device": opt-in, never on the recovery-code form (US-mfa-trusted-device) --}}
                    @if(($trustDays ?? 0) > 0)
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="trust_device" value="1" id="mfa-trust-device"
                                   data-testid="mfa-verify-trust-device">
                            <label class="form-check-label" for="mfa-trust-device">
                                {{ gp247_language_render('Plugins/MFA::lang.trust_device_label', ['days' => $trustDays]) }}
                            </label>
                            <small class="form-text text-muted d-block">
                                {{ gp247_language_render('Plugins/MFA::lang.trust_device_hint') }}
                            </small>
                        </div>
                    @endif

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> {{ gp247_language_render('Plugins/MFA::lang.verify') }}
                        </button>
                    </div>
                </form>

                <div class="text-center">
                    <a href="#" class="btn-toggle" onclick="toggleRecoveryForm(event)">
                        <i class="fas fa-key"></i> {{ gp247_language_render('Plugins/MFA::lang.use_recovery_code') }}
                    </a>
                </div>

                <!-- Recovery Code Form (Hidden by default) -->
                <form action="{{ route('mfa.verify') }}" method="POST" id="recovery-form" style="display: none;" class="mt-4">
                    @csrf
                    <input type="hidden" name="guard" value="{{ $guard }}">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-key"></i> {{ gp247_language_render('Plugins/MFA::lang.enter_recovery_code') }}
                        </label>
                        <input type="text" 
                               name="code" 
                               class="form-control form-control-lg text-center" 
                               placeholder="XXXXXXXX"
                               maxlength="8"
                               style="letter-spacing: 4px; font-family: monospace;">
                        <small class="form-text text-muted">
                            {{ gp247_language_render('Plugins/MFA::lang.recovery_codes_desc') }}
                        </small>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-check"></i> {{ gp247_language_render('Plugins/MFA::lang.verify') }}
                        </button>
                    </div>

                    <div class="text-center">
                        <a href="#" class="btn-toggle" onclick="toggleRecoveryForm(event)">
                            <i class="fas fa-mobile-alt"></i> {{ gp247_language_render('Plugins/MFA::lang.use_authenticator_code') }}
                        </a>
                    </div>
                </form>

                <!-- Help -->
                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        {{ gp247_language_render('Plugins/MFA::lang.help_lost_device_desc') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleRecoveryForm(e) {
            e.preventDefault();
            const authenticatorForm = document.getElementById('authenticator-form');
            const recoveryForm = document.getElementById('recovery-form');
            
            if (recoveryForm.style.display === 'none') {
                authenticatorForm.style.display = 'none';
                recoveryForm.style.display = 'block';
                recoveryForm.querySelector('input[name="code"]').focus();
            } else {
                recoveryForm.style.display = 'none';
                authenticatorForm.style.display = 'block';
                authenticatorForm.querySelector('input[name="code"]').focus();
            }
        }
    </script>
</body>
</html>

