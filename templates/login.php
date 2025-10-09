<div class="user-center-box">
    <div class="user-center-left">
        <h3>欢迎回来</h3>
        <p>请登录您的账户以访问个人中心，管理您的个人信息和设置。</p>
    </div>
    <div class="user-center-right">
        <h4>登录账户</h4>
        <form id="user-center-login-form" method="post" autocomplete="off">
            <?php wp_nonce_field( 'user_center_login_nonce', 'nonce' ); ?>
            <input type="hidden" name="action" value="user_center_login">
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '' ); ?>">
            
            <div class="uc-form-group">
                <label for="login-username">用户名</label>
                <input type="text" id="login-username" name="username" class="uc-form-control" placeholder="请输入用户名" required>
            </div>
            
            <div class="uc-form-group">
                <label for="login-password">密码</label>
                <input type="password" id="login-password" name="password" class="uc-form-control" placeholder="请输入密码" required>
                <div class="caps-lock-warning">Caps Lock已开启，请注意密码大小写</div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <div style="display: flex; align-items: center;">
                    <input type="checkbox" id="remember-me" name="remember" style="margin-right: 8px;">
                    <label for="remember-me" style="margin-bottom: 0;">记住我</label>
                </div>
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="uc-link">忘记密码?</a>
            </div>
            
            <button type="submit" class="uc-btn" style="width: 100%;">登录</button>
            
            <div style="text-align: center; margin-top: 20px;">
                <span>还没有账户? </span>
                <a href="#" class="uc-link" data-tab="register">立即注册</a>
            </div>
            
            <div class="form-message" style="display: none;"></div>
        </form>
    </div>
</div>
