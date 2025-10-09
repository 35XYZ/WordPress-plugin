<?php
// 防止直接访问
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 解密前端加密的密码
 * @param string $encrypted_password 加密后的密码
 * @return string|WP_Error 解密后的密码或错误对象
 */
function uc_decrypt_password($encrypted_password) {
    // 检查是否是不安全传输标记或编码错误标记
    if ($encrypted_password === 'INSECURE_TRANSMISSION') {
        error_log('[User Center] Security warning: Password submitted over non-HTTPS connection');
        return new WP_Error('insecure_connection', '请使用HTTPS连接进行安全操作');
    }
    
    if ($encrypted_password === 'ENCODING_ERROR') {
        error_log('[User Center] Error: Password encoding failed');
        return new WP_Error('encoding_error', '密码处理失败，请重试');
    }
    
    // 与前端简化加密函数对应的解密函数
    $decrypted = '';
    try {
        // 直接使用Base64解码，去掉URL解码步骤
        $decrypted = base64_decode($encrypted_password);
        // 如果解码失败，返回错误
        if ($decrypted === false) {
            error_log("[User Center] Base64 decode failed");
            return new WP_Error('decryption_failed', '密码解密失败');
        }
    } catch (Exception $e) {
        // 记录错误日志
        error_log("[User Center] Password decryption exception: " . $e->getMessage());
        // 解密失败时返回错误对象
        return new WP_Error('decryption_failed', '密码解密失败');
    }
    return $decrypted;
}

/**
 * 生成随机验证码（简化版）
 * @return string 生成的6位数字验证码
 */
function uc_generate_verification_code() {
    // 直接返回6位随机数字
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 发送验证码邮件（简化版，更具兼容性）
 * @param string $email 用户邮箱
 * @param string $code 验证码
 * @return bool 是否发送成功
 */
function uc_send_verification_email($email, $code) {
    // 邮件主题
    $subject = '【用户中心】注册验证码';
    
    // 简化邮件内容，确保兼容性
    $message = "您好！

感谢您注册我们的网站。您的注册验证码是：{$code}

此验证码有效期为10分钟，请尽快完成注册。

如果您没有发起此注册请求，请忽略此邮件。";
    
    // 简化邮件头部，确保兼容性
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8'
    );
    
    // 直接使用wp_mail函数，WordPress原生支持
    $result = wp_mail($email, $subject, $message, $headers);
    
    // 记录日志
    if ($result) {
        error_log("[User Center] Verification email sent to: {$email}");
    } else {
        error_log("[User Center] Failed to send verification email to: {$email}");
    }
    
    return $result;
}

// 处理发送验证码请求（简化版）
add_action( 'wp_ajax_nopriv_user_center_send_verify_code', 'uc_handle_send_verify_code' );
add_action( 'wp_ajax_user_center_send_verify_code', 'uc_handle_send_verify_code' );
function uc_handle_send_verify_code() {
    // 检查请求方法
    if ( !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
        wp_send_json_error( array( 'message' => '无效的请求方法' ) );
    }
    
    // 检查nonce安全验证
    if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'user_center_register_nonce' ) ) {
        wp_send_json_error( array( 'message' => '安全验证失败，请刷新页面后重试' ) );
    }
    
    // 获取并验证邮箱
    if ( !isset( $_POST['email'] ) ) {
        wp_send_json_error( array( 'message' => '邮箱不能为空' ) );
    }
    
    $email = sanitize_email( $_POST['email'] );
    
    // 验证邮箱格式
    if ( !is_email( $email ) ) {
        wp_send_json_error( array( 'message' => '邮箱格式不正确' ) );
    }
    
    // 检查邮箱是否已被注册
    if ( email_exists( $email ) ) {
        wp_send_json_error( array( 'message' => '邮箱已被注册' ) );
    }
    
    // 生成验证码
    $code = uc_generate_verification_code();
    
    // 简化存储逻辑，使用WordPress的transient API
    $transient_key = 'uc_verify_' . md5($email);
    set_transient($transient_key, $code, 10 * MINUTE_IN_SECONDS);
    
    // 发送验证码邮件
    if ( uc_send_verification_email( $email, $code ) ) {
        wp_send_json_success( array( 'message' => '验证码已发送，请查收邮箱' ) );
    } else {
        // 如果邮件发送失败，记录验证码用于调试
        error_log("[User Center Debug] Verification code for {$email}: {$code}");
        wp_send_json_error( array( 'message' => '验证码发送失败，请稍后重试' ) );
    }
}

// 处理用户登录
add_action( 'wp_ajax_nopriv_user_center_login', 'uc_user_center_handle_login' );
add_action( 'wp_ajax_user_center_login', 'uc_user_center_handle_login' );
function uc_user_center_handle_login() {
    // 检查nonce安全验证
    if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'user_center_login_nonce' ) ) {
        // 记录错误日志以便调试
        $username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : 'unknown';
        error_log( "[User Center] Login nonce verification failed for user: {$username}" );
        
        // 返回错误信息
        wp_send_json_error( array( 'message' => '安全验证失败，请刷新页面后重试' ) );
    }
    
    $username = sanitize_user( $_POST['username'] );
    // 解密前端加密的密码
    $password = uc_decrypt_password( $_POST['password'] );
    
    // 检查解密是否返回了错误
    if ( is_wp_error( $password ) ) {
        wp_send_json_error( array( 'message' => $password->get_error_message() ) );
    }
    
    // 检查解密是否返回了错误
    if ( is_wp_error( $password ) ) {
        wp_send_json_error( array( 'message' => $password->get_error_message() ) );
    }
    
    // 检查解密是否返回了错误
    if ( is_wp_error( $password ) ) {
        wp_send_json_error( array( 'message' => $password->get_error_message() ) );
    }
    
    $remember = isset( $_POST['remember'] );
    
    // 检查用户名和密码
    $user = wp_signon( array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    ), is_ssl() );
    
    if ( is_wp_error( $user ) ) {
        error_log("Login failed: " . $user->get_error_message());
        wp_send_json_error( array( 'message' => '用户名或密码错误' ) );
    }
    
    // 登录成功，获取重定向URL
    $redirect_url = esc_url( $_POST['redirect_to'] );
    if ( !$redirect_url ) {
        // 如果没有指定重定向URL，尝试获取个人中心页面URL
        $profile_page_id = get_option( 'user_center_profile_page_id' );
        if ( $profile_page_id ) {
            $redirect_url = get_permalink( $profile_page_id );
        } else {
            // 如果没有设置个人中心页面，使用默认URL
            $redirect_url = home_url( '/user-center/' );
        }
    }
    
    // 确保重定向URL是完整的URL
    $redirect_url = esc_url( $redirect_url );
    
    error_log("Login redirect URL: " . $redirect_url);
    
    wp_send_json_success( array( 'redirect' => $redirect_url ) );
}

// 处理用户注册（简化版验证码验证）
add_action( 'wp_ajax_nopriv_user_center_register', 'uc_user_center_handle_register' );
add_action( 'wp_ajax_user_center_register', 'uc_user_center_handle_register' );
function uc_user_center_handle_register() {
    // 检查nonce
    if ( !wp_verify_nonce( $_POST['nonce'], 'user_center_register_nonce' ) ) {
        wp_send_json_error( array( 'message' => '安全验证失败' ) );
    }
    
    // 获取并验证表单数据
    $username = sanitize_user( $_POST['username'] );
    $email = sanitize_email( $_POST['email'] );
    $verify_code = sanitize_text_field( $_POST['verify_code'] );
    // 解密前端加密的密码
    $password = uc_decrypt_password( $_POST['password'] );
    
    // 检查解密是否返回了错误
    if ( is_wp_error( $password ) ) {
        wp_send_json_error( array( 'message' => $password->get_error_message() ) );
    }
    
    $nickname = sanitize_text_field( $_POST['nickname'] );
    
    // 简化验证码验证逻辑
    $transient_key = 'uc_verify_' . md5($email);
    $stored_code = get_transient( $transient_key );
    
    if ( !$stored_code || $stored_code != $verify_code ) {
        wp_send_json_error( array( 'message' => '验证码不正确或已过期' ) );
    }
    
    // 验证码验证成功后删除transient
    delete_transient( $transient_key );
    
    // 检查用户名
    if ( username_exists( $username ) ) {
        error_log("Registration failed: username already exists - $username");
        wp_send_json_error( array( 'message' => '用户名已存在' ) );
    }
    
    // 检查邮箱
    if ( email_exists( $email ) ) {
        error_log("Registration failed: email already exists - $email");
        wp_send_json_error( array( 'message' => '邮箱已被注册' ) );
    }
    
    // 检查是否正在注册中（防止重复提交）
    $registration_key = 'user_center_registration_' . md5($username . $email);
    if ( get_transient($registration_key) ) {
        error_log("Registration failed: duplicate submission attempt - $username");
        wp_send_json_error( array( 'message' => '请勿重复提交注册信息' ) );
    }
    // 设置临时标记，防止重复提交
    set_transient($registration_key, true, 10);
    
    // 检查密码强度
    if ( strlen( $password ) < 6 ) {
        error_log("Registration failed: password too short - $username");
        wp_send_json_error( array( 'message' => '密码长度不能少于6个字符' ) );
    }
    
    // 创建用户
    $user_id = wp_create_user( $username, $password, $email );
    
    if ( is_wp_error( $user_id ) ) {
        error_log("Registration failed: wp_create_user error - " . $user_id->get_error_message());
        wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
    }
    
    // 更新昵称
    $update_result = wp_update_user( array(
        'ID' => $user_id,
        'nickname' => $nickname,
        'display_name' => $nickname
    ) );
    
    if ( is_wp_error( $update_result ) ) {
        error_log("Registration warning: nickname update failed - " . $update_result->get_error_message());
    }
    
    // 初始化用户经验值
    update_user_meta( $user_id, 'user_center_exp', 0 );
    update_user_meta( $user_id, 'user_center_last_checkin', '' );
    update_user_meta( $user_id, 'user_center_streak_days', 0 );
    
    // 自动登录
    wp_set_auth_cookie( $user_id, true, is_ssl() );
    do_action( 'wp_login', $username, get_user_by( 'id', $user_id ) );
    
    error_log("Registration successful: user_id=$user_id, username=$username");
    
    // 动态获取用户中心URL
    $profile_page_id = get_option( 'user_center_profile_page_id' );
    if ( $profile_page_id ) {
        $redirect_url = get_permalink( $profile_page_id );
    } else {
        // 如果没有设置个人中心页面，使用完整的默认URL
        $redirect_url = home_url( '/index.php/user-center/' );
    }
    
    // 确保重定向URL是完整的URL
    $redirect_url = esc_url( $redirect_url );
    
    // 记录重定向URL用于调试
    error_log("Registration redirect URL: " . $redirect_url);
    
    wp_send_json_success( array( 'redirect' => $redirect_url ) );
}

// 处理资料更新
add_action( 'wp_ajax_user_center_update_profile', 'uc_user_center_handle_update_profile' );
function uc_user_center_handle_update_profile() {
    if ( !is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => '请先登录' ) );
    }
    
    check_ajax_referer( 'user_center_update_profile_nonce', 'nonce' );
    
    $user_id = get_current_user_id();
    $user_data = array( 'ID' => $user_id );
    
    // 更新昵称
    if ( isset( $_POST['nickname'] ) ) {
        $user_data['nickname'] = sanitize_text_field( $_POST['nickname'] );
    }
    
    // 更新密码
    if ( isset( $_POST['new_password'] ) && !empty( $_POST['new_password'] ) ) {
        // 解密前端加密的密码
        $old_password = uc_decrypt_password( $_POST['old_password'] );
        
        // 检查解密是否返回了错误
        if ( is_wp_error( $old_password ) ) {
            wp_send_json_error( array( 'message' => $old_password->get_error_message() ) );
        }
        
        $user = wp_authenticate( wp_get_current_user()->user_login, $old_password );
        
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => '原密码不正确' ) );
        }
        
        // 解密前端加密的新密码
        $user_data['user_pass'] = uc_decrypt_password( $_POST['new_password'] );
        
        // 检查解密是否返回了错误
        if ( is_wp_error( $user_data['user_pass'] ) ) {
            wp_send_json_error( array( 'message' => $user_data['user_pass']->get_error_message() ) );
        }
    }
    
    // 更新用户资料
    $result = wp_update_user( $user_data );
    
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }
    
    // 处理头像上传
    if ( isset( $_FILES['avatar'] ) && !empty( $_FILES['avatar']['name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        
        $attachment_id = media_handle_upload( 'avatar', 0 );
        
        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
        }
        
        update_user_meta( $user_id, 'avatar', $attachment_id );
    }
    
    wp_send_json_success( array( 'message' => '资料更新成功' ) );
}

// 处理登录用户的退出请求（前台用户）
add_action('wp_ajax_nopriv_uc_ajax_logout', 'uc_handle_logout');
add_action('wp_ajax_uc_ajax_logout', 'uc_handle_logout');

function uc_handle_logout() {
    // 验证安全参数
    if (!wp_verify_nonce($_POST['nonce'], 'uc_logout_nonce')) {
        wp_send_json_error(array('message' => '安全验证失败'));
    }
    
    // 执行WordPress退出登录
    wp_logout();
    
    // 返回主页地址
    wp_send_json_success(array(
        'home_url' => home_url() // 网站主页地址
    ));
}

// 处理用户签到
add_action( 'wp_ajax_user_center_checkin', 'uc_user_center_handle_checkin' );
function uc_user_center_handle_checkin() {
    if ( !is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => '请先登录' ) );
    }
    
    check_ajax_referer( 'user_center_checkin_nonce', 'nonce' );
    
    $user_id = get_current_user_id();
    $today = date('Y-m-d');
    $last_checkin = get_user_meta( $user_id, 'user_center_last_checkin', true );
    $streak_days = get_user_meta( $user_id, 'user_center_streak_days', true ) ?: 0;
    $exp = get_user_meta( $user_id, 'user_center_exp', true ) ?: 0;
    
    // 检查是否已签到
    if ( $last_checkin == $today ) {
        wp_send_json_error( array( 'message' => '今日已签到' ) );
    }
    
    // 计算连续签到天数
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ( $last_checkin == $yesterday ) {
        $streak_days++;
    } else {
        $streak_days = 1;
    }
    
    // 计算签到经验 (10-30点基础经验 + 连续签到奖励)
    $base_exp = rand(10, 30);
    $bonus_exp = min(10, $streak_days - 1); // 连续签到每天额外+1，最多+10
    $total_exp = $base_exp + $bonus_exp;
    $new_exp = $exp + $total_exp;
    
    // 更新用户数据
    update_user_meta( $user_id, 'user_center_last_checkin', $today );
    update_user_meta( $user_id, 'user_center_streak_days', $streak_days );
    update_user_meta( $user_id, 'user_center_exp', $new_exp );
    
    // 计算等级
    $level = uc_calculate_user_level($new_exp);
    
    wp_send_json_success( array(
        'message' => "签到成功！获得 {$total_exp} 经验值",
        'exp' => $new_exp,
        'level' => $level,
        'streak_days' => $streak_days
    ) );
}