<?php

return [
    'title' => 'Xác thực 2 lớp (MFA)',
    'description' => 'Tăng cường bảo mật tài khoản với xác thực 2 lớp',
    
    // General
    'mfa' => 'Xác thực 2 lớp',
    'two_factor_authentication' => 'Xác thực 2 lớp',
    'enable_mfa' => 'Bật MFA',
    'disable_mfa' => 'Tắt MFA',
    'setup_mfa' => 'Thiết lập MFA',
    'manage_mfa' => 'Quản lý MFA',
    
    // Setup
    'scan_qr_code' => 'Quét mã QR',
    'scan_qr_code_desc' => 'Sử dụng ứng dụng xác thực (Google Authenticator, Authy, v.v.) để quét mã QR này',
    'manual_entry' => 'Nhập thủ công',
    'manual_entry_desc' => 'Nếu không thể quét mã QR, bạn có thể nhập mã bí mật sau vào ứng dụng xác thực',
    'secret_key' => 'Mã bí mật',
    'verify_code' => 'Xác minh mã',
    'verify_code_desc' => 'Nhập mã 6 chữ số từ ứng dụng xác thực để hoàn tất thiết lập',
    'enter_code' => 'Nhập mã 6 chữ số',
    
    // Recovery codes
    'recovery_codes' => 'Mã khôi phục',
    'recovery_codes_desc' => 'Lưu các mã khôi phục này ở nơi an toàn. Bạn có thể sử dụng chúng để đăng nhập khi không có thiết bị xác thực',
    'recovery_codes_warning' => 'Mỗi mã chỉ có thể sử dụng một lần. Hãy in ra hoặc lưu ở nơi an toàn',
    'download_recovery_codes' => 'Tải xuống mã khôi phục',
    'print_recovery_codes' => 'In mã khôi phục',
    'regenerate_recovery_codes' => 'Tạo lại mã khôi phục',
    'recovery_codes_regenerated' => 'Mã khôi phục đã được tạo lại thành công',
    
    // Verification
    'verification_required' => 'Yêu cầu xác thực 2 lớp',
    'verification_desc' => 'Nhập mã 6 chữ số từ ứng dụng xác thực hoặc mã khôi phục',
    'use_recovery_code' => 'Sử dụng mã khôi phục',
    'use_authenticator_code' => 'Sử dụng mã từ ứng dụng',
    'enter_recovery_code' => 'Nhập mã khôi phục 8 ký tự',
    'verify' => 'Xác minh',
    'verification_successful' => 'Xác minh thành công',
    'recovery_code_used' => 'Mã khôi phục đã được sử dụng. Hãy tạo mã mới nếu cần',
    
    // Management
    'mfa_status' => 'Trạng thái MFA',
    'mfa_enabled' => 'MFA đã được bật',
    'mfa_disabled' => 'MFA chưa được bật',
    'mfa_enabled_since' => 'Đã bật từ',
    'last_used' => 'Sử dụng lần cuối',
    'never_used' => 'Chưa sử dụng',
    'disable_mfa_desc' => 'Tắt xác thực 2 lớp sẽ giảm bảo mật tài khoản của bạn',
    'confirm_password' => 'Xác nhận mật khẩu',
    'enter_password_to_confirm' => 'Nhập mật khẩu để xác nhận',
    
    // Admin
    'admin_title' => 'Quản lý MFA',
    'dashboard' => 'Tổng quan',
    'users_management' => 'Quản lý người dùng',
    'users_management_desc' => 'Xem danh sách người dùng và reset MFA khi cần thiết',
    'guard_settings' => 'Cài đặt theo Guard',
    'guard' => 'Guard',
    'enabled' => 'Bật',
    'disabled' => 'Tắt',
    'forced' => 'Bắt buộc',
    'qr_code_size' => 'Kích thước mã QR',
    'recovery_codes_count' => 'Số lượng mã khôi phục',
    'window' => 'Cửa sổ thời gian',
    'config_readonly_note' => 'Cấu hình MFA chỉ có thể chỉnh sửa trực tiếp trong file config để đảm bảo bảo mật.',
    'config_file_path' => 'Đường dẫn file config',
    'status_on' => 'Bật',
    'status_off' => 'Tắt',

    // Configuration guide (hiển thị trên dashboard để hỗ trợ chủ site cấu hình MFA)
    'settings_saved' => 'Đã lưu thiết lập MFA',
    'settings_editable_note' => 'Các thiết lập này được lưu vào cơ sở dữ liệu và giữ nguyên khi cập nhật plugin. Trường nâng cao (model người dùng, redirect) vẫn nằm trong file config.',
    'config_guide_title' => 'Hướng dẫn cấu hình',
    'config_guide_intro' => 'Bật/tắt MFA cho từng loại tài khoản bên dưới và lưu lại — thay đổi có hiệu lực ngay, không cần build lại hay migration. Thiết lập lưu trong cơ sở dữ liệu (an toàn khi cập nhật).',
    'config_guide_enable' => 'Bật (Enabled): bật MFA cho loại tài khoản này. Khi tắt, người dùng loại đó không bị yêu cầu MFA.',
    'config_guide_forced' => 'Bắt buộc (Forced): yêu cầu mọi người dùng của loại tài khoản này phải thiết lập MFA trước khi tiếp tục; để tắt thì tùy chọn (không bắt buộc).',
    'config_guide_qr' => 'Kích thước mã QR: kích thước (pixel) của mã QR khi thiết lập (mặc định 200).',
    'config_guide_recovery' => 'Số lượng mã khôi phục: số mã dự phòng dùng-một-lần tạo cho mỗi người dùng (mặc định 8).',
    'config_guide_window' => 'Cửa sổ thời gian: dung sai lệch giờ tính theo bước 30 giây (1 = ±30s). Chỉ tăng khi người dùng báo mã không khớp.',
    'select_guard' => 'Chọn Guard',
    'load_users' => 'Tải danh sách',
    'name' => 'Tên',
    'email' => 'Email',
    'actions' => 'Thao tác',
    'user' => 'Người dùng',
    'cancel' => 'Hủy',
    'processing' => 'Đang xử lý...',
    'no_mfa' => 'Chưa bật MFA',
    'select_guard_and_load' => 'Chọn Guard và nhấn "Tải danh sách" để xem người dùng',
    'no_users_found' => 'Không tìm thấy người dùng',
    'no_enabled_guards' => 'Không có guard nào được bật. Vui lòng bật ít nhất một guard trong file cấu hình.',
    
    // Statistics
    'statistics' => 'Thống kê',
    'total_users' => 'Tổng người dùng',
    'mfa_enabled_users' => 'Đã bật MFA',
    'mfa_setup_users' => 'Đã thiết lập MFA',
    'adoption_rate' => 'Tỷ lệ sử dụng',
    
    // Users management
    'users_list' => 'Danh sách người dùng',
    'user_email' => 'Email',
    'user_name' => 'Tên',
    'mfa_status_user' => 'Trạng thái MFA',
    'reset_mfa' => 'Reset MFA',
    'reset_mfa_confirm' => 'Bạn có chắc muốn reset MFA cho người dùng này?',
    'mfa_reset_successfully' => 'MFA đã được reset thành công',
    'mfa_pending' => 'Chờ thiết lập',
    'not_setup' => 'Chưa thiết lập',
    'no_available_guards' => 'Không có loại tài khoản nào để quản lý.',
    
    // Messages
    'mfa_enabled_successfully' => 'MFA đã được bật thành công',
    'mfa_disabled_successfully' => 'MFA đã được tắt thành công',
    'mfa_not_setup' => 'MFA chưa được thiết lập',
    'mfa_not_enabled' => 'MFA chưa được bật',
    'mfa_not_enabled_for_guard' => 'MFA chưa được bật cho guard này',
    'mfa_required' => 'Tài khoản của bạn yêu cầu bật MFA',
    'invalid_code' => 'Mã không hợp lệ',
    'incorrect_password' => 'Mật khẩu không đúng',
    'user_not_authenticated' => 'Người dùng chưa được xác thực',
    'invalid_guard' => 'Guard không hợp lệ',
    
    // Buttons
    'continue' => 'Tiếp tục',
    'cancel' => 'Hủy',
    'back' => 'Quay lại',
    'back_to_dashboard' => 'Quay lại Dashboard',
    'close' => 'Đóng',
    'save' => 'Lưu',
    'confirm' => 'Xác nhận',
    
    // Help
    'help_title' => 'Trợ giúp',
    'help_what_is_mfa' => 'MFA là gì?',
    'help_what_is_mfa_desc' => 'MFA (Multi-Factor Authentication) là một lớp bảo mật bổ sung yêu cầu không chỉ mật khẩu và tên người dùng mà còn có thứ mà chỉ người dùng đó có, tức là một phần thông tin chỉ họ biết hoặc ngay lập tức có trong tay họ',
    'help_supported_apps' => 'Ứng dụng được hỗ trợ',
    'help_supported_apps_desc' => 'Google Authenticator, Microsoft Authenticator, Authy, 1Password và các ứng dụng TOTP khác',
    'help_lost_device' => 'Mất thiết bị?',
    'help_lost_device_desc' => 'Sử dụng mã khôi phục để đăng nhập, sau đó thiết lập MFA lại với thiết bị mới',
    
    // Guard names
    'guard_customer' => 'Khách hàng',
    'guard_admin' => 'Quản trị viên',
    'guard_vendor' => 'Nhà cung cấp',
    'guard_pmo_partner' => 'PMO',
];

