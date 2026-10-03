<?php
/*
Plugin Name: 用户中心
Plugin URI: https://xiayuze.xyz/
Description: 提供用户登录、注册和个人中心功能
Version: 1.8
Author: 35XYZ
Author URI: https://xiayuze.xyz/
License: GPL2
*/

// 防止直接访问
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// 从插件头部读取版本，统一用于本插件静态资源
$user_center_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), 'plugin' );
define( 'USER_CENTER_VERSION', $user_center_plugin_data['Version'] );
unset( $user_center_plugin_data );

// 定义插件路径
define( 'USER_CENTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'USER_CENTER_URL', plugin_dir_url( __FILE__ ) );

// 插件激活时创建页面
register_activation_hook( __FILE__, 'user_center_create_pages' );
function user_center_create_pages() {
    // 创建登录/注册页面
    $login_page = get_page_by_title( '用户登录' );
    if ( !$login_page ) {
        $login_page_id = wp_insert_post( array(
            'post_title' => '用户登录',
            'post_content' => '[user_center]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ) );
        update_option( 'user_center_login_page_id', $login_page_id );
    }
    
    // 创建个人中心页面
    $profile_page = get_page_by_title( '个人中心' );
    if ( !$profile_page ) {
        $profile_page_id = wp_insert_post( array(
            'post_title' => '个人中心',
            'post_content' => '[user_center_profile]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ) );
        update_option( 'user_center_profile_page_id', $profile_page_id );
    }
}

// 加载必要文件
require_once USER_CENTER_PATH . 'includes/shortcodes.php';
require_once USER_CENTER_PATH . 'includes/functions.php';
require_once USER_CENTER_PATH . 'includes/ajax-handlers.php';

// 加载样式和脚本
add_action( 'wp_enqueue_scripts', 'user_center_enqueue_assets' );
function user_center_enqueue_assets() {
    // 加载主样式
    wp_enqueue_style( 'user-center-style', USER_CENTER_URL . 'css/style.css', array(), USER_CENTER_VERSION );
    
    // 加载Font Awesome
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
    
    // 加载脚本
    wp_enqueue_script( 'user_center-script', USER_CENTER_URL . 'js/script.js', array( 'jquery' ), USER_CENTER_VERSION, true );
    
    // 本地化脚本
    wp_localize_script( 'user_center-script', 'user_center', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'is_logged_in' => is_user_logged_in(),
        'nonce' => wp_create_nonce( 'user_center_register_nonce' ),
        'login_nonce' => wp_create_nonce( 'user_center_login_nonce' ),
        'update_profile_nonce' => wp_create_nonce( 'user_center_update_profile_nonce' ),
        'checkin_nonce' => wp_create_nonce( 'user_center_checkin_nonce' )
    ) );
    
    // 本地化脚本，传递退出登录必要参数
wp_localize_script( 'user_center-script', 'uc_ajax', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'logout_nonce' => wp_create_nonce( 'uc_logout_nonce' )
) );
}