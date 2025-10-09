<?php
/**
 * 注册表单模板
 */
?>
<div class="user-center-box">
    <div class="user-center-left">
        <h3>创建新账户</h3>
        <p>注册账户后，您可以享受更多个性化服务和功能。</p>
    </div>
    <div class="user-center-right">
        <h4>注册账户</h4>
        <form id="user-center-register-form" method="post" autocomplete="off">
            <?php wp_nonce_field('user_center_register_nonce', 'nonce'); ?>
            <input type="hidden" name="action" value="user_center_register">
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '' ); ?>">
            
            <div class="uc-form-group">
                <label for="register-username">用户名</label>
                <input type="text" id="register-username" name="username" class="uc-form-control" placeholder="请输入用户名" required>
                <div class="field-error" style="display: none;"></div>
            </div>
            
            <div class="uc-form-group">
                <label for="register-nickname">昵称</label>
                <input type="text" id="register-nickname" name="nickname" class="uc-form-control" placeholder="请输入昵称" required>
                <div class="field-error" style="display: none;"></div>
            </div>
            
            <div class="uc-form-group">
                <label for="register-email">邮箱</label>
                <input type="email" id="register-email" name="email" class="uc-form-control" placeholder="请输入邮箱" required>
                <div class="field-error" style="display: none;"></div>
            </div>
            
            <!-- 验证码字段 -->
            <div class="uc-form-group">
                <label for="register-verify-code">验证码</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="register-verify-code" name="verify_code" class="uc-form-control" placeholder="请输入验证码" required style="flex: 1;">
                    <button type="button" id="send-verify-code-btn" class="uc-btn">发送验证码</button>
                </div>
                <div class="field-error" style="display: none;"></div>
            </div>
            
            <div class="uc-form-group">
                <label for="register-password">密码</label>
                <input type="password" id="register-password" name="password" class="uc-form-control" placeholder="请输入密码" required>
                <div class="caps-lock-warning">Caps Lock已开启，请注意密码大小写</div>
                <div class="field-error" style="display: none;"></div>
            </div>
            
            <button type="submit" class="uc-btn" style="width: 100%;">注册</button>
            
            <div style="text-align: center; margin-top: 20px;">
                <span>已有账户? </span>
                <a href="#" class="uc-link" data-tab="login">立即登录</a>
            </div>
            
            <div class="form-message" style="display: none;"></div>
        </form>
    </div>
</div>