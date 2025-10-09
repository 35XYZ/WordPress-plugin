<?php
// 防止直接访问
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// 计算用户等级 (满级6级)
function uc_calculate_user_level($exp) {
    $levels = array(0, 200, 500, 1000, 2000, 3000);
    for ($i = 5; $i >= 0; $i--) {
        if ($exp >= $levels[$i]) {
            return $i + 1;
        }
    }
    return 1;
}

// 获取等级所需经验范围
function uc_get_level_exp_range($level) {
    $ranges = array(
        1 => array(0, 199),
        2 => array(200, 499),
        3 => array(500, 999),
        4 => array(1000, 1999),
        5 => array(2000, 2999),
        6 => array(3000, PHP_INT_MAX)
    );
    return $ranges[$level];
}

// 为登录和注册页面添加body类名
add_filter( 'body_class', 'uc_user_center_add_body_classes' );
function uc_user_center_add_body_classes( $classes ) {
    if ( is_page_template( 'templates/login.php' ) || 
         ( is_singular() && has_shortcode( get_post()->post_content, 'user_center_login' ) ) ) {
        $classes[] = 'login';
    }
    
    if ( is_page_template( 'templates/register.php' ) || 
         ( is_singular() && has_shortcode( get_post()->post_content, 'user_center_register' ) ) ) {
        $classes[] = 'register';
    }
    
    return $classes;
}