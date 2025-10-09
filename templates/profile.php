<?php
$current_user = wp_get_current_user();
$avatar_id = get_user_meta($current_user->ID, 'avatar', true);
$avatar_url = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'medium') : get_avatar_url($current_user->ID);

// 经验值和等级相关数据
$exp = get_user_meta($current_user->ID, 'user_center_exp', true) ?: 0;
$level = uc_calculate_user_level($exp);
$range = uc_get_level_exp_range($level);
$max_level = 6;

// 计算进度百分比
if ($level >= $max_level) {
    $percent = 100;
    $exp_info = "Lv{$level} (已满级)";
} else {
    $percent = min(100, (($exp - $range[0]) / ($range[1] - $range[0])) * 100);
    $exp_info = "Lv{$level} ({$exp}/{$range[1]})";
}

$last_checkin = get_user_meta($current_user->ID, 'user_center_last_checkin', true);
$streak_days = get_user_meta($current_user->ID, 'user_center_streak_days', true) ?: 0;
$is_checked_in = $last_checkin == date('Y-m-d');

?>
<!-- 个人中心基础信息（默认显示） -->
<div class="user-center-wrapper">
    <!-- 顶部信息卡片 -->
    <div class="uc-card profile-header">
        <div class="avatar-box">
            <img src="<?php echo esc_url($avatar_url); ?>" class="avatar-img" alt="用户头像">
        </div>
        <div class="user-info">
            <h2><?php echo esc_html($current_user->display_name); ?> <span style="font-size: 14px; color: var(--uc-accent-color);">Lv<?php echo $level; ?></span></h2>
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p><?php echo esc_html($current_user->user_email); ?></p>
                    <p>注册日期: <?php echo esc_html(date('Y-m-d H:i:s', strtotime($current_user->user_registered))); ?></p>
                </div>
                <!-- 签到按钮移动到这里 -->
                <button id="checkin-btn" class="uc-btn uc-btn-primary" style="padding: 6px 16px; margin-left: 16px; <?php echo $is_checked_in ? 'background-color: var(--uc-text-tertiary); cursor: not-allowed;' : ''; ?>">
                    <?php echo $is_checked_in ? '<i class="fa fa-check"></i> 今日已签到' : '<i class="fa fa-calendar-check-o"></i> 每日签到'; ?>
                </button>
            </div>
            <!-- 编辑按钮 -->
            <button class="edit-profile-btn uc-btn uc-btn-outline" style="margin-top: 12px;">编辑个人资料</button>
        </div>
    </div>

    <!-- 经验和签到区域 -->
    <div class="uc-card profile-info">
        <h3>等级与经验</h3>
        
        <div class="info-row">
            <label>当前等级</label>
            <span><?php echo "Lv{$level}"; ?></span>
        </div>
        
        <div class="info-row">
            <label>经验进度</label>
            <span><?php echo $exp_info; ?></span>
        </div>
        
        <!-- 经验条 -->
        <div style="padding: 0 0 16px 80px;">
            <div style="height: 8px; background-color: var(--uc-border-color); border-radius: 4px; overflow: hidden;">
                <div style="width: <?php echo $percent; ?>%; height: 100%; background-color: var(--uc-accent-color); border-radius: 4px; transition: width 0.5s ease;"></div>
            </div>
        </div>
        
        <div class="info-row">
            <label>连续签到</label>
            <span><?php echo "{$streak_days} 天"; ?></span>
        </div>
        

    </div>

    <!-- 个人信息卡片（简化版） -->
    <div class="uc-card profile-info">
        <h3>个人信息</h3>
        <div class="info-row">
            <label>用户名</label>
            <span><?php echo esc_html($current_user->user_login); ?></span>
        </div>
        <div class="info-row">
            <label>昵称</label>
            <span><?php echo esc_html($current_user->nickname); ?></span>
        </div>
        <!-- 可扩展其他信息，保持简洁 -->
    </div>
</div>

<!-- 编辑资料弹窗（默认隐藏） -->
<div id="profile-modal" class="uc-modal">
    <div class="uc-modal-overlay" style="backdrop-filter: blur(4px); background: rgba(0,0,0,0.15);"></div>
    <div class="uc-modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);">
        <div class="uc-modal-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--uc-border-color); margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 600;">编辑个人资料</h3>
            <button class="uc-modal-close" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--uc-text-tertiary);">&times;</button>
        </div>
        <div class="uc-modal-body">
            <form id="profile-edit-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('user_center_update_profile_nonce', 'nonce'); ?>
                <input type="hidden" name="action" value="user_center_update_profile">
                
                <!-- 头像区域 -->
                <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--uc-border-color);">
                    <img src="<?php echo esc_url($avatar_url); ?>" class="avatar-img" style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 12px; border: 2px solid var(--uc-border-color);">
                    <button type="button" id="change-avatar" class="uc-btn uc-btn-outline" style="padding: 6px 16px; font-size: 14px; border-radius: 6px; transition: all 0.2s ease;">更换头像</button>
                    <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none;">
                </div>
                
                <!-- 昵称 -->
                <div class="uc-form-group" style="margin-bottom: 20px;">
                    <label for="edit-nickname" style="font-size: 13px; margin-bottom: 8px; display: block;">昵称</label>
                    <input type="text" id="edit-nickname" name="nickname" class="uc-form-control" 
                           value="<?php echo esc_attr($current_user->nickname); ?>" required 
                           style="padding: 10px 12px; border-radius: 6px; font-size: 15px;">
                </div>
                
                <!-- 密码修改区域 -->
                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--uc-border-color);">
                    <h4 style="margin: 0 0 16px 0; font-size: 15px; font-weight: 600;">密码修改</h4>
                    
                    <div class="uc-form-group" style="margin-bottom: 20px;">
                        <label for="old-password" style="font-size: 13px; margin-bottom: 8px; display: block;">原密码</label>
                        <input type="password" id="old-password" name="old_password" class="uc-form-control" 
                               placeholder="输入原密码（可选）" 
                               style="padding: 10px 12px; border-radius: 6px; font-size: 15px;">
                    </div>
                    
                    <div class="uc-form-group" style="margin-bottom: 20px;">
                        <label for="new-password" style="font-size: 13px; margin-bottom: 8px; display: block;">新密码</label>
                        <input type="password" id="new-password" name="new_password" class="uc-form-control" 
                               placeholder="输入新密码（可选）" 
                               style="padding: 10px 12px; border-radius: 6px; font-size: 15px;">
                    </div>
                </div>
                
                <!-- 操作按钮 -->
                <div class="uc-form-group" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px; padding-top: 16px; border-top: 1px solid var(--uc-border-color);">
                    <button type="button" class="uc-modal-cancel uc-btn uc-btn-outline" style="padding: 8px 20px; border-radius: 6px; transition: all 0.2s ease;">取消</button>
                    <button type="button" id="uc-logout-btn" class="uc-btn uc-btn-outline" style="padding: 8px 20px; border-radius: 6px; transition: all 0.2s ease;">退出登录</button>
                    <button type="submit" class="uc-btn uc-btn-primary" style="padding: 8px 20px; border-radius: 6px; font-weight: 500; transition: all 0.2s ease;">保存修改</button>
                </div>
            </form>
        </div>
    </div>
</div>