<?php

return [
    'title' => '多要素認証 (MFA)',
    'description' => '二要素認証でアカウントのセキュリティを強化します',
    
    // General
    'mfa' => '多要素認証',
    'two_factor_authentication' => '二要素認証',
    'enable_mfa' => 'MFAを有効化',
    'disable_mfa' => 'MFAを無効化',
    'setup_mfa' => 'MFAを設定',
    'manage_mfa' => 'MFAを管理',
    
    // Setup
    'scan_qr_code' => 'QRコードをスキャン',
    'scan_qr_code_desc' => '認証アプリ (Google Authenticator、Authy など) でこのQRコードをスキャンしてください',
    'manual_entry' => '手動入力',
    'manual_entry_desc' => 'QRコードをスキャンできない場合は、次のシークレットコードを認証アプリに入力してください',
    'secret_key' => 'シークレットキー',
    'verify_code' => 'コードを確認',
    'verify_code_desc' => '設定を完了するには、認証アプリに表示される6桁のコードを入力してください',
    'enter_code' => '6桁のコードを入力',
    
    // Recovery codes
    'recovery_codes' => 'リカバリーコード',
    'recovery_codes_desc' => 'これらのリカバリーコードを安全な場所に保管してください。認証デバイスが手元にないときにサインインに使用できます',
    'recovery_codes_warning' => '各コードは1回のみ使用できます。印刷するか安全な場所に保存してください',
    'download_recovery_codes' => 'リカバリーコードをダウンロード',
    'print_recovery_codes' => 'リカバリーコードを印刷',
    'regenerate_recovery_codes' => 'リカバリーコードを再生成',
    'recovery_codes_regenerated' => 'リカバリーコードを再生成しました',
    
    // Verification
    'verification_required' => '二要素認証が必要です',
    'verification_desc' => '認証アプリの6桁のコード、またはリカバリーコードを入力してください',
    'use_recovery_code' => 'リカバリーコードを使用',
    'use_authenticator_code' => '認証アプリのコードを使用',
    'enter_recovery_code' => '8文字のリカバリーコードを入力',
    'verify' => '確認',
    'verification_successful' => '確認に成功しました',
    'recovery_code_used' => 'リカバリーコードが使用されました。必要に応じて新しいコードを生成してください',
    
    // Management
    'mfa_status' => 'MFAの状態',
    'mfa_enabled' => 'MFAは有効です',
    'mfa_disabled' => 'MFAは有効になっていません',
    'mfa_enabled_since' => '有効化日時',
    'last_used' => '最終使用',
    'never_used' => '未使用',
    'disable_mfa_desc' => '二要素認証を無効にすると、アカウントのセキュリティが低下します',
    'confirm_password' => 'パスワードの確認',
    'enter_password_to_confirm' => '確認のためパスワードを入力してください',
    
    // Admin
    'admin_title' => 'MFA管理',
    'dashboard' => 'ダッシュボード',
    'users_management' => 'ユーザー管理',
    'users_management_desc' => 'ユーザー一覧を表示し、必要に応じてMFAをリセットします',
    'guard_settings' => 'ガード設定',
    'guard' => 'ガード',
    'enabled' => '有効',
    'disabled' => '無効',
    'forced' => '必須',
    'qr_code_size' => 'QRコードのサイズ',
    'recovery_codes_count' => 'リカバリーコードの数',
    'window' => '許容時間幅',
    'config_readonly_note' => 'セキュリティ上の理由により、MFAの設定は設定ファイルで直接のみ編集できます。',
    'config_file_path' => '設定ファイルのパス',
    'status_on' => 'オン',
    'status_off' => 'オフ',

    // Configuration guide (shown on the dashboard to help site owners set MFA up)
    'settings_saved' => 'MFAの設定を保存しました',
    'settings_editable_note' => 'これらの設定はデータベースに保存され、プラグインを更新しても保持されます。高度な項目 (ユーザーモデル、リダイレクト) は設定ファイルに残ります。',
    'config_guide_title' => '設定ガイド',
    'config_guide_intro' => '下でアカウント種別ごとにMFAを切り替えて保存してください。変更はすぐに反映され、再ビルドやマイグレーションは不要です。設定はデータベースに保存されます (更新しても失われません)。',
    'config_guide_enable' => '有効: このアカウント種別でMFAをオンにします。オフの場合、そのユーザーにはMFAが要求されません。',
    'config_guide_forced' => '必須: このアカウント種別のすべてのユーザーに、続行する前にMFAの設定を求めます。オフにすると任意になります。',
    'config_guide_qr' => 'QRコードのサイズ: 設定用QRコードのピクセルサイズ (既定値 200)。',
    'config_guide_recovery' => 'リカバリーコードの数: ユーザーごとに生成される使い捨てのバックアップコードの数 (既定値 8)。',
    'config_guide_window' => '許容時間幅: 30秒単位で許容する時刻のずれ (1 = ±30秒)。ユーザーからコード不一致の報告がある場合のみ増やしてください。',
    'select_guard' => 'ガードを選択',
    'load_users' => 'ユーザーを読み込む',
    'name' => '名前',
    'email' => 'メールアドレス',
    'actions' => '操作',
    'user' => 'ユーザー',
    'cancel' => 'キャンセル',
    'processing' => '処理中...',
    'no_mfa' => 'MFAなし',
    'select_guard_and_load' => 'ガードを選択し「ユーザーを読み込む」をクリックするとユーザーが表示されます',
    'no_users_found' => 'ユーザーが見つかりません',
    'no_enabled_guards' => '有効なガードがありません。設定ファイルで少なくとも1つのガードを有効にしてください。',
    
    // Statistics
    'statistics' => '統計',
    'total_users' => 'ユーザー総数',
    'mfa_enabled_users' => 'MFA有効',
    'mfa_setup_users' => 'MFA設定済み',
    'adoption_rate' => '導入率',
    
    // Users management
    'users_list' => 'ユーザー一覧',
    'user_email' => 'メールアドレス',
    'user_name' => '名前',
    'mfa_status_user' => 'MFAの状態',
    'reset_mfa' => 'MFAをリセット',
    'reset_mfa_confirm' => 'このユーザーのMFAをリセットしてもよろしいですか?',
    'mfa_reset_successfully' => 'MFAをリセットしました',
    'mfa_pending' => '設定待ち',
    'not_setup' => '未設定',
    'no_available_guards' => '管理できるアカウント種別がありません。',
    
    // Messages
    'mfa_enabled_successfully' => 'MFAを有効にしました',
    'mfa_disabled_successfully' => 'MFAを無効にしました',
    'mfa_not_setup' => 'MFAが設定されていません',
    'mfa_not_enabled' => 'MFAは有効になっていません',
    'mfa_not_enabled_for_guard' => 'このガードではMFAが有効になっていません',
    'mfa_required' => 'お使いのアカウントではMFAを有効にする必要があります',
    'invalid_code' => 'コードが無効です',
    'incorrect_password' => 'パスワードが正しくありません',
    'user_not_authenticated' => 'ユーザーが認証されていません',
    'invalid_guard' => '無効なガードです',
    
    // Buttons
    'continue' => '続行',
    'cancel' => 'キャンセル',
    'back' => '戻る',
    'back_to_dashboard' => 'ダッシュボードに戻る',
    'close' => '閉じる',
    'save' => '保存',
    'confirm' => '確認',
    
    // Help
    'help_title' => 'ヘルプ',
    'help_what_is_mfa' => 'MFAとは?',
    'help_what_is_mfa_desc' => 'MFA (多要素認証) は、パスワードとユーザー名に加えて、ユーザー本人だけが持っているもの、つまり本人だけが知っている、またはすぐに手元にある情報を要求する追加のセキュリティ層です',
    'help_supported_apps' => '対応アプリ',
    'help_supported_apps_desc' => 'Google Authenticator、Microsoft Authenticator、Authy、1Password およびその他のTOTPアプリ',
    'help_lost_device' => 'デバイスを紛失しましたか?',
    'help_lost_device_desc' => 'リカバリーコードでサインインし、新しいデバイスでMFAを再設定してください',
    
    // Guard names
    'guard_customer' => '顧客',
    'guard_admin' => '管理者',
    'guard_vendor' => 'ベンダー',
    'guard_pmo_partner' => 'PMO',
];
