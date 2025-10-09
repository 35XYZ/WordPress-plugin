<?php
// 防止直接访问
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// 引入functions.php中的等级计算函数
require_once USER_CENTER_PATH . 'includes/functions.php';

// 登录页面短代码
add_shortcode( 'user_center_login', 'user_center_login_shortcode' );
function user_center_login_shortcode() {
    // 如果已登录，跳转到个人中心
    if ( is_user_logged_in() ) {
        $profile_page_id = get_option( 'user_center_profile_page_id' );
        if ( $profile_page_id ) {
            $profile_url = get_permalink( $profile_page_id );
            // 确保不重定向到自身
            if ( $profile_url && $profile_url !== get_permalink() ) {
                wp_redirect( $profile_url );
                exit;
            }
        }
    }
    
    ob_start();
    include USER_CENTER_PATH . 'templates/login.php';
    return ob_get_clean();
}

// 注册页面短代码
add_shortcode( 'user_center_register', 'user_center_register_shortcode' );
function user_center_register_shortcode() {
    // 如果已登录，跳转到个人中心
    if ( is_user_logged_in() ) {
        $profile_page_id = get_option( 'user_center_profile_page_id' );
        if ( $profile_page_id ) {
            $profile_url = get_permalink( $profile_page_id );
            // 确保不重定向到自身
            if ( $profile_url && $profile_url !== get_permalink() ) {
                wp_redirect( $profile_url );
                exit;
            }
        }
    }
    
    ob_start();
    include USER_CENTER_PATH . 'templates/register.php';
    return ob_get_clean();
}

// 个人中心短代码 - 修复重定向循环
add_shortcode( 'user_center_profile', 'user_center_profile_shortcode' );
function user_center_profile_shortcode() {
    // 如果未登录，显示弹窗登录界面
    if ( !is_user_logged_in() ) {
        // 获取登录和注册表单内容
        ob_start();
        include USER_CENTER_PATH . 'templates/login.php';
        $login_form = ob_get_clean();
        
        ob_start();
        include USER_CENTER_PATH . 'templates/register.php';
        $register_form = ob_get_clean();
        
        // 返回包含弹窗的页面内容，添加首次访问刷新逻辑
        return "<div id='user-center-modal' class='uc-modal'>
            <div class='uc-modal-overlay'></div>
            <div class='uc-modal-content'>
                <div class='uc-modal-header'>
                    <h3>用户登录</h3>
                    <span class='uc-modal-close'>&times;</span>
                </div>
                <div class='uc-modal-body'>
                    <div class='uc-tab-container'>
                        <div class='uc-tab active' data-tab='login'>登录</div>
                        <div class='uc-tab' data-tab='register'>注册</div>
                    </div>
                    <div class='uc-tab-content'>
                        <div class='uc-tab-pane active' id='login-tab'>
                            {$login_form}
                        </div>
                        <div class='uc-tab-pane' id='register-tab'>
                            {$register_form}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // 首次访问未登录用户中心页面时自动刷新
        jQuery(document).ready(function($) {
            // 仅对未登录用户执行刷新逻辑
            if (!user_center.is_logged_in) { 
                // 检查当前页面路径是否包含/user-center（包括/index.php/user-center形式） 
                var currentPath = window.location.pathname; 
                var isUserCenterPage = currentPath.indexOf('/user-center') !== -1 || currentPath.indexOf('/index.php/user-center') !== -1; 
                
                if (isUserCenterPage) { 
                    // 检查URL参数，避免无限循环刷新 
                    var urlParams = new URLSearchParams(window.location.search); 
                    if (!urlParams.has('refreshed')) { 
                        // 添加刷新标记到URL并刷新页面 
                        var newUrl = window.location.href + (window.location.search ? '&' : '?') + 'refreshed=1'; 
                        window.location.replace(newUrl); 
                    } else { 
                        $('#user-center-modal').fadeIn(); 
                    } 
                } else { 
                    $('#user-center-modal').fadeIn(); 
                } 
            } 
        });
        </script>";
    } else {
        // 已登录用户显示个人中心内容
        return "<div id='user-center-modal' class='uc-modal' style='display:none;'>
            </div>" . user_center_profile_content();
    }
    
    return user_center_profile_content();
}

// 用户中心内容
function user_center_profile_content() {
    ob_start();
    include USER_CENTER_PATH . 'templates/profile.php';
    return ob_get_clean();
}

function user_center_shortcode() {
    if ( is_user_logged_in() ) {
        // 已登录，跳转到个人中心页面
        $profile_page_id = get_option( 'user_center_profile_page_id' );
        if ( $profile_page_id ) {
            $profile_url = get_permalink( $profile_page_id );
            if ( $profile_url ) {
                wp_redirect( $profile_url );
                exit;
            }
        }
        // 如果无法获取个人中心页面URL，使用默认路径
        wp_redirect( site_url('/user-center') );
        exit;
    } else {
        // 未登录，输出弹窗 HTML
        ob_start();
        ?>
        <div id="user-center-modal" class="uc-modal" style="display:none;">
            <div class="uc-modal-overlay"></div>
            <div class="uc-modal-content">
                <div class="uc-modal-header">
                    <h3>用户中心</h3>
                    <span class="uc-modal-close">&times;</span>
                </div>
                <div class="uc-modal-body">
                    <div class="uc-tab-container">
                        <div class="uc-tab active" data-tab="login">登录</div>
                        <div class="uc-tab" data-tab="register">注册</div>
                    </div>
                    <div class="uc-tab-content">
                        <div class="uc-tab-pane active" id="login-tab">
                            <?php include USER_CENTER_PATH . 'templates/login.php'; ?>
                        </div>
                        <div class="uc-tab-pane" id="register-tab">
                            <?php include USER_CENTER_PATH . 'templates/register.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // 首次访问未登录用户中心页面时自动刷新
        jQuery(document).ready(function($) {
            // 仅对未登录用户执行刷新逻辑
            if (!user_center.is_logged_in) { 
                // 检查当前页面路径是否包含/user-center（包括/index.php/user-center形式） 
                var currentPath = window.location.pathname; 
                var isUserCenterPage = currentPath.indexOf('/user-center') !== -1 || currentPath.indexOf('/index.php/user-center') !== -1; 
                
                if (isUserCenterPage) { 
                    // 检查URL参数，避免无限循环刷新 
                    var urlParams = new URLSearchParams(window.location.search); 
                    if (!urlParams.has('refreshed')) { 
                        // 添加刷新标记到URL并刷新页面 
                        var newUrl = window.location.href + (window.location.search ? '&' : '?') + 'refreshed=1'; 
                        window.location.replace(newUrl); 
                    } else { 
                        $('#user-center-modal').fadeIn(); 
                    } 
                } else { 
                    $('#user-center-modal').fadeIn(); 
                } 
            } 
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('user_center', 'user_center_shortcode');