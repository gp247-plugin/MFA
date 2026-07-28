<?php

return [
    'title' => 'Multi-Factor Authentication (MFA)',
    'description' => 'Enhance account security with two-factor authentication',
    
    // General
    'mfa' => 'Multi-Factor Authentication',
    'two_factor_authentication' => 'Two-Factor Authentication',
    'enable_mfa' => 'Enable MFA',
    'disable_mfa' => 'Disable MFA',
    'setup_mfa' => 'Setup MFA',
    'manage_mfa' => 'Manage MFA',
    
    // Setup
    'scan_qr_code' => 'Scan QR Code',
    'scan_qr_code_desc' => 'Use an authenticator app (Google Authenticator, Authy, etc.) to scan this QR code',
    'manual_entry' => 'Manual Entry',
    'manual_entry_desc' => 'If you cannot scan the QR code, you can enter the following secret code into your authenticator app',
    'secret_key' => 'Secret Key',
    'verify_code' => 'Verify Code',
    'verify_code_desc' => 'Enter the 6-digit code from your authenticator app to complete setup',
    'enter_code' => 'Enter 6-digit code',
    
    // Recovery codes
    'recovery_codes' => 'Recovery Codes',
    'recovery_codes_desc' => 'Store these recovery codes in a safe place. You can use them to sign in when you don\'t have your authenticator device',
    'recovery_codes_warning' => 'Each code can only be used once. Please print or save them in a secure location',
    'download_recovery_codes' => 'Download Recovery Codes',
    'print_recovery_codes' => 'Print Recovery Codes',
    'regenerate_recovery_codes' => 'Regenerate Recovery Codes',
    'recovery_codes_regenerated' => 'Recovery codes have been regenerated successfully',
    
    // Verification
    'verification_required' => 'Two-Factor Authentication Required',
    'verification_desc' => 'Enter the 6-digit code from your authenticator app or a recovery code',
    'use_recovery_code' => 'Use recovery code',
    'use_authenticator_code' => 'Use authenticator code',
    'enter_recovery_code' => 'Enter 8-character recovery code',
    'verify' => 'Verify',
    'verification_successful' => 'Verification successful',
    'recovery_code_used' => 'Recovery code has been used. Please generate new codes if needed',
    
    // Management
    'mfa_status' => 'MFA Status',
    'mfa_enabled' => 'MFA is enabled',
    'mfa_disabled' => 'MFA is not enabled',
    'mfa_enabled_since' => 'Enabled since',
    'last_used' => 'Last used',
    'never_used' => 'Never used',
    'disable_mfa_desc' => 'Disabling two-factor authentication will reduce your account security',
    'confirm_password' => 'Confirm Password',
    'enter_password_to_confirm' => 'Enter your password to confirm',
    
    // Admin
    'admin_title' => 'MFA Management',
    'dashboard' => 'Dashboard',
    'users_management' => 'Users Management',
    'users_management_desc' => 'View users list and reset MFA when needed',
    'guard_settings' => 'Guard Settings',
    'guard' => 'Guard',
    'enabled' => 'Enabled',
    'disabled' => 'Disabled',
    'forced' => 'Forced',
    'qr_code_size' => 'QR Code Size',
    'recovery_codes_count' => 'Recovery Codes Count',
    'window' => 'Time Window',
    'config_readonly_note' => 'MFA configuration can only be edited directly in the config file for security reasons.',
    'config_file_path' => 'Config file path',
    'status_on' => 'On',
    'status_off' => 'Off',

    // Configuration guide (shown on the dashboard to help site owners set MFA up)
    'settings_saved' => 'MFA settings have been saved',
    'settings_editable_note' => 'These settings are saved to the database and are preserved when the plugin is updated. Advanced fields (user model, redirects) stay in the config file.',
    'config_guide_title' => 'Configuration Guide',
    'config_guide_intro' => 'Toggle MFA per account type below and save — changes take effect immediately, no rebuild or migration needed. Settings are stored in the database (update-safe).',
    'config_guide_enable' => 'Enabled: turn MFA on for this account type. When off, MFA is not requested for its users.',
    'config_guide_forced' => 'Forced: require every user of this account type to set up MFA before they can continue; leave off to make it optional.',
    'config_guide_qr' => 'QR Code Size: pixel size of the setup QR code (default 200).',
    'config_guide_recovery' => 'Recovery Codes Count: number of one-time backup codes generated per user (default 8).',
    'config_guide_window' => 'Time Window: allowed clock-drift tolerance in 30-second steps (1 = ±30s). Increase only if users report code mismatches.',
    'select_guard' => 'Select Guard',
    'load_users' => 'Load Users',
    'name' => 'Name',
    'email' => 'Email',
    'actions' => 'Actions',
    'user' => 'User',
    'cancel' => 'Cancel',
    'processing' => 'Processing...',
    'no_mfa' => 'No MFA',
    'select_guard_and_load' => 'Select a guard and click "Load Users" to view users',
    'no_users_found' => 'No users found',
    'no_enabled_guards' => 'No guards are enabled. Please enable at least one guard in the configuration file.',
    
    // Statistics
    'statistics' => 'Statistics',
    'total_users' => 'Total Users',
    'mfa_enabled_users' => 'MFA Enabled',
    'mfa_setup_users' => 'MFA Setup',
    'adoption_rate' => 'Adoption Rate',
    
    // Users management
    'users_list' => 'Users List',
    'user_email' => 'Email',
    'user_name' => 'Name',
    'mfa_status_user' => 'MFA Status',
    'reset_mfa' => 'Reset MFA',
    'reset_mfa_confirm' => 'Are you sure you want to reset MFA for this user?',
    'mfa_reset_successfully' => 'MFA has been reset successfully',
    'mfa_pending' => 'Pending setup',
    'not_setup' => 'Not setup',
    'no_available_guards' => 'No account type is available to manage.',
    
    // Messages
    'mfa_enabled_successfully' => 'MFA has been enabled successfully',
    'mfa_disabled_successfully' => 'MFA has been disabled successfully',
    'mfa_not_setup' => 'MFA has not been setup',
    'mfa_not_enabled' => 'MFA is not enabled',
    'mfa_not_enabled_for_guard' => 'MFA is not enabled for this guard',
    'mfa_required' => 'Your account requires MFA to be enabled',
    'invalid_code' => 'Invalid code',
    'incorrect_password' => 'Incorrect password',
    'user_not_authenticated' => 'User is not authenticated',
    'invalid_guard' => 'Invalid guard',
    
    // Buttons
    'continue' => 'Continue',
    'cancel' => 'Cancel',
    'back' => 'Back',
    'back_to_dashboard' => 'Back to Dashboard',
    'close' => 'Close',
    'save' => 'Save',
    'confirm' => 'Confirm',
    
    // Help
    'help_title' => 'Help',
    'help_what_is_mfa' => 'What is MFA?',
    'help_what_is_mfa_desc' => 'MFA (Multi-Factor Authentication) is an additional security layer that requires not only a password and username but also something that only the user has on them, i.e., a piece of information only they should know or immediately have at hand',
    'help_supported_apps' => 'Supported Apps',
    'help_supported_apps_desc' => 'Google Authenticator, Microsoft Authenticator, Authy, 1Password and other TOTP apps',
    'help_lost_device' => 'Lost your device?',
    'help_lost_device_desc' => 'Use a recovery code to sign in, then setup MFA again with your new device',
    
    // Guard names
    'guard_customer' => 'Customer',
    'guard_admin' => 'Administrator',
    'guard_vendor' => 'Vendor',
    'guard_pmo_partner' => 'PMO',
];

