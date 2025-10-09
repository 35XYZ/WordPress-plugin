jQuery(document).ready(function ($) {
    // 检测并设置初始暗黑模式
    function updateDarkMode() {
        // 优先使用本地存储，其次使用系统偏好
        var isDark = localStorage.getItem('darkMode') === 'true' || 
               (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            $('body').addClass('dark-mode');
            $('#fabtn_toggle_darkmode .fa-moon-o').hide();
            $('#fabtn_toggle_darkmode .fa-lightbulb-o').show();
        } else {
            $('body').removeClass('dark-mode');
            $('#fabtn_toggle_darkmode .fa-moon-o').show();
            $('#fabtn_toggle_darkmode .fa-lightbulb-o').hide();
        }
    }
    
    // 初始化暗黑模式和按钮状态
    updateDarkMode();
    
    // 监听现有日夜模式切换按钮
    $('#fabtn_toggle_darkmode').click(function() {
        var isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('darkMode', !isDark);
        updateDarkMode();
    });
    
    // 显示用户中心模态窗口
    if ($('#user-center-modal').length > 0) {
        $('#user-center-modal').addClass('show');
        
        // 关闭用户中心模态窗口
        $('#user-center-modal .uc-modal-close, #user-center-modal .uc-modal-overlay').on('click', function() {
            $('#user-center-modal').removeClass('show');
        });
    }
    
    // 个人资料弹窗控制
    var $modal = $('#profile-modal');
    var $modalOverlay = $('.uc-modal-overlay');
    
    // 显示弹窗
    $(document).on('click', '.edit-profile-btn', function() {
        $('#profile-modal').addClass('show');
    });
    
    // 关闭弹窗的通用函数
    function closeModal() {
        $modal.removeClass('active');
        $('body').removeClass('modal-open');
    }
    
    // 关闭弹窗（关闭按钮和取消按钮）
    $(document).on('click', '.uc-modal-close, .uc-modal-cancel, .uc-modal-overlay', function() {
        $('#profile-modal').removeClass('show');
    });
    
    // 密码安全处理函数 - 提示用户使用HTTPS而非简单编码
    function encryptPassword(password) {
        try {
            // 在HTTPS环境下，我们可以使用简单的编码防止在网络日志中明文显示密码
            // 注意：这不是真正的加密，仅作为基本保护措施
            if (isHttps()) {
                var encoded = btoa(unescape(encodeURIComponent(password)));
                console.log('Password encoded for transmission');
                return encoded;
            }
            // 在非HTTPS环境下，密码将被标记以在服务器端拒绝处理
            return 'INSECURE_TRANSMISSION';
        } catch (e) {
            console.error('Password encoding failed:', e);
            // 编码失败时返回错误标记
            return 'ENCODING_ERROR';
        }
    }
    
    // 检查是否使用HTTPS
    function isHttps() {
        return window.location.protocol === 'https:';
    }
    
    // 登录表单提交处理
    $('#user-center-login-form').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $message = $form.find('.form-message');
        var $submitBtn = $form.find('button[type="submit"]');
        
        // 检查是否使用HTTPS
        if (!isHttps()) {
            $message.text('为了您的账户安全，请使用HTTPS连接进行登录').removeClass('info success').addClass('error').show();
            return;
        }
        
        // 防止重复提交
        if ($submitBtn.prop('disabled')) {
            return;
        }
        
        // 创建加密后的表单数据
        var formData = $form.serializeArray();
        for (var i = 0; i < formData.length; i++) {
            if (formData[i].name === 'password') {
                formData[i].value = encryptPassword(formData[i].value);
                break;
            }
        }
        
        $.ajax({
            url: user_center.ajax_url + '?t=' + new Date().getTime(),
            type: 'POST',
            data: $.param(formData),
            beforeSend: function() {
                $submitBtn.prop('disabled', true).text('正在登录...');
                $message.text('正在登录...').removeClass('success error').addClass('info').show();
            },
            success: function(response) {
                if (response.success) {
                    $message.text('登录成功，正在刷新...').removeClass('info error').addClass('success');
                    // 登录成功后刷新页面
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $message.text(response.data.message).removeClass('info success').addClass('error').show();
                    $submitBtn.prop('disabled', false).text('登录');
                }
            },
            error: function() {
                $message.text('登录失败，请重试').removeClass('info success').addClass('error').show();
                $submitBtn.prop('disabled', false).text('登录');
            }
        });
    });
    
    // 邮箱格式验证函数
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // 发送验证码按钮点击处理 - 简化版
    $(document).on('click', '#send-verify-code-btn', function() {
        var $btn = $(this);
        var $form = $btn.closest('form');
        var $message = $form.find('.form-message');
        var email = $form.find('input[name="email"]').val();
        var emailField = $form.find('#register-email');
        
        // 验证邮箱
        if (!email) {
            emailField.siblings('.field-error').text('请输入邮箱').show();
            return;
        } else if (!isValidEmail(email)) {
            emailField.siblings('.field-error').text('请输入有效的邮箱地址').show();
            return;
        }
        
        // 清除邮箱字段的错误提示
        emailField.siblings('.field-error').hide().text('');
        $message.hide();
        
        // 禁用按钮
        $btn.prop('disabled', true).text('发送中...');
        
        // 获取表单中的nonce值
        var nonce = $form.find('input[name="nonce"]').val();
        
        // 发送AJAX请求
        $.ajax({
            url: user_center.ajax_url + '?t=' + new Date().getTime(),
            type: 'POST',
            data: {
                action: 'user_center_send_verify_code',
                email: email,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $message.text(response.data.message).removeClass('error').addClass('success').show();
                    // 开始倒计时
                    startCountdown($btn);
                } else {
                    $message.text(response.data.message).removeClass('success').addClass('error').show();
                    $btn.prop('disabled', false).text('发送验证码');
                }
            },
            error: function() {
                $message.text('发送失败，请重试').removeClass('success').addClass('error').show();
                $btn.prop('disabled', false).text('发送验证码');
            }
        });
    });
    
    // 验证码倒计时函数 - 简化版
    function startCountdown(button) {
        var seconds = 60;
        button.text(seconds + '秒后重新发送');
        
        var timer = setInterval(function() {
            seconds--;
            button.text(seconds + '秒后重新发送');
            
            if (seconds <= 0) {
                clearInterval(timer);
                button.text('发送验证码');
                button.prop('disabled', false);
            }
        }, 1000);
    }
    
    // 注册表单提交处理 - 简化版验证码验证
    $('#user-center-register-form').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $message = $form.find('.form-message');
        var $submitBtn = $form.find('button[type="submit"]');
    
        // 检查是否使用HTTPS
        if (!isHttps()) {
            $message.text('为了您的账户安全，请使用HTTPS连接进行注册').removeClass('info success').addClass('error').show();
            return;
        }
        
        // 防止重复提交
        if ($submitBtn.prop('disabled')) {
            return;
        }
    
        // 清除之前的错误提示
        $form.find('.field-error').hide().text('');
        $message.hide();
    
        // 前端验证
        var isValid = true;
        var username = $form.find('input[name="username"]').val();
        var email = $form.find('input[name="email"]').val();
        var verify_code = $form.find('input[name="verify_code"]').val();
        var password = $form.find('input[name="password"]').val();
        var nickname = $form.find('input[name="nickname"]').val();
    
        // 验证用户名
        if (!username) {
            $form.find('#register-username').siblings('.field-error').text('请输入用户名').show();
            isValid = false;
        }
    
        // 验证昵称
        if (!nickname) {
            $form.find('#register-nickname').siblings('.field-error').text('请输入昵称').show();
            isValid = false;
        }
    
        // 验证邮箱
        if (!email) {
            $form.find('#register-email').siblings('.field-error').text('请输入邮箱').show();
            isValid = false;
        } else if (!isValidEmail(email)) {
            $form.find('#register-email').siblings('.field-error').text('请输入有效的邮箱地址').show();
            isValid = false;
        }
        
        // 验证验证码
        if (!verify_code) {
            $form.find('#register-verify-code').siblings('.field-error').text('请输入验证码').show();
            isValid = false;
        } else if (!/^\d{6}$/.test(verify_code)) {
            $form.find('#register-verify-code').siblings('.field-error').text('验证码格式不正确').show();
            isValid = false;
        }
    
        // 验证密码
        if (!password) {
            $form.find('#register-password').siblings('.field-error').text('请输入密码').show();
            isValid = false;
        } else if (password.length < 6) {
            $form.find('#register-password').siblings('.field-error').text('密码长度不能少于6个字符').show();
            isValid = false;
        }
    
        // 如果验证失败，显示错误信息并返回
        if (!isValid) {
            $message.text('请修正表单中的错误').removeClass('info success').addClass('error').show();
            return;
        }
    
        // 添加提交标志防止重复提交
        if ($form.data('submitting')) {
            $message.text('请勿重复提交').removeClass('info success').addClass('error').show();
            return;
        }
        $form.data('submitting', true);
    
        // 创建加密后的表单数据
        var formData = $form.serializeArray();
        for (var i = 0; i < formData.length; i++) {
            if (formData[i].name === 'password') {
                formData[i].value = encryptPassword(formData[i].value);
                break;
            }
        }
        
        $.ajax({
            url: user_center.ajax_url + '?t=' + new Date().getTime(),
            type: 'POST',
            data: $.param(formData),
            beforeSend: function() {
                $submitBtn.prop('disabled', true).text('正在注册...');
                $message.text('正在注册...').removeClass('success error').addClass('info').show();
            },
            success: function(response) {
                if (response.success) {
                    $message.text('注册成功，正在刷新...').removeClass('info error').addClass('success');
                    // 关闭模态框
                    $('#user-center-modal').removeClass('show');
                    // 延迟刷新确保用户看到成功信息
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $message.text(response.data.message).removeClass('info success').addClass('error').show();
                    $submitBtn.prop('disabled', false).text('注册');
                }
            },
            error: function(xhr) {
                var errorMsg = '注册失败，请重试';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                $message.text(errorMsg).removeClass('info success').addClass('error').show();
                $submitBtn.prop('disabled', false).text('注册');
            },
            complete: function() {
                // 确保按钮在请求完成后恢复可用状态
                $submitBtn.prop('disabled', false);
                // 清除提交标志
                $form.data('submitting', false);
            }
        });
    });
    
    // 头像上传处理
    $(document).on('click', '#change-avatar', function() {
        $('#avatar-input').click();
    });
    
    $('#avatar-input').change(function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                // 预览头像
                $('.avatar-img').attr('src', e.target.result);
                
                // 上传头像到服务器
                var formData = new FormData();
                formData.append('action', 'user_center_update_profile');
                formData.append('nonce', user_center.update_profile_nonce);
                formData.append('avatar', file);
                
                $.ajax({
                    url: user_center.ajax_url + '?t=' + new Date().getTime(),
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            showNotification('头像更新成功', 'success');
                        } else {
                            showNotification('头像更新失败: ' + response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('网络错误，头像上传失败', 'error');
                    }
                });
            }
            reader.readAsDataURL(file);
        }
    });
    
    // 资料编辑表单提交处理
    $('#profile-edit-form').submit(function(e) {
        e.preventDefault();
        var $form = $(this);
        var $message = $form.find('.form-message');
        
        // 检查是否使用HTTPS
        if (!isHttps()) {
            $message.text('为了您的账户安全，请使用HTTPS连接修改资料').removeClass('info success').addClass('error').show();
            return;
        }
        
        // 创建加密后的表单数据（如果有密码字段）
        var formData = $form.serializeArray();
        for (var i = 0; i < formData.length; i++) {
            if (formData[i].name === 'old_password' || formData[i].name === 'new_password') {
                formData[i].value = encryptPassword(formData[i].value);
            }
        }
        
        $.ajax({
            url: user_center.ajax_url + '?t=' + new Date().getTime(),
            type: 'POST',
            data: $.param(formData),
            beforeSend: function() {
                $message.text('正在保存...').removeClass('success error').addClass('info').show();
            },
            success: function(response) {
                if (response.success) {
                    $message.text(response.data.message).removeClass('info error').addClass('success').show();
                    // 保存成功后1.5秒关闭弹窗并刷新页面
                    setTimeout(function() {
                        closeModal();
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    $message.text(response.data.message).removeClass('info success').addClass('error').show();
                }
            },
            error: function() {
                $message.text('保存失败，请重试').removeClass('info success').addClass('error').show();
            }
        });
    });
    
    // 签到功能
    $(document).on('click', '#checkin-btn', function() {
        var $btn = $(this);
        
        // 如果已签到则不执行
        if ($btn.find('.fa-check').length) {
            return;
        }
        
        $.ajax({
                url: user_center.ajax_url + '?t=' + new Date().getTime(),
                type: 'POST',
                data: {
                    action: 'user_center_checkin',
                    nonce: user_center.checkin_nonce
                },
                beforeSend: function() {
                    $btn.text('签到中...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                    $btn.text('已签到').prop('disabled', true);
                },
                error: function() {
                    showNotification('签到失败，请重试', 'error');
                    $btn.text('签到').prop('disabled', false);
                }
            });
    });
    
    // 退出登录处理
    $(document).on('click', '#uc-logout-btn', function() {
        if (confirm('确定要退出登录吗？')) {
            $.ajax({
                url: user_center.ajax_url + '?t=' + new Date().getTime(),
                type: 'POST',
                data: {
                    action: 'uc_ajax_logout',
                    nonce: uc_ajax.logout_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // 退出成功后跳转到主页
                        window.location.href = response.data.home_url;
                    } else {
                        showNotification('退出失败，请重试', 'error');
                    }
                },
                error: function() {
                    showNotification('网络错误，退出失败', 'error');
                }
            });
        }
    });
    
    // 显示通知
    function showNotification(message, type) {
        // 移除已存在的通知
        $('.uc-notification').remove();
        
        var $notification = $('<div class="uc-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        // 显示通知
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        // 3秒后隐藏通知
        setTimeout(function() {
            $notification.removeClass('show');
            // 动画结束后移除元素
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    // 弹窗Tab切换
    $(document).on('click', '.uc-tab, .uc-link[data-tab]', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');
        
        // 更新激活状态
        $('.uc-tab').removeClass('active');
        $('.uc-tab[data-tab="' + tab + '"]').addClass('active');
        
        // 切换内容
        $('.uc-tab-pane').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
        
        // 更新标题
        if (tab === 'login') {
            $('.uc-modal-header h3').text('用户登录');
        } else {
            $('.uc-modal-header h3').text('用户注册');
        }
    });
    
    // 关闭弹窗
    $(document).on('click', '.uc-modal-close, .uc-modal-overlay', function() {
        $('#user-center-modal').removeClass('show');
    });
    
    // 页面加载完成后显示弹窗（如果存在且用户未登录）
    if ($('#user-center-modal').length > 0 && typeof user_center !== 'undefined' && !user_center.is_logged_in) {
        $('#user-center-modal').addClass('show');
    }
    
    // 关闭弹窗
    $(document).on('click', '#user-center-modal', function(e){
        if(e.target === this){
            $(this).fadeOut();
        }
    });
    
    // 检测Caps Lock状态
    $('#login-password, #register-password').on('keypress', function(e) {
        var $warning = $(this).siblings('.caps-lock-warning');
        var keyCode = e.keyCode || e.which;
        var isShift = e.shiftKey;
        
        // 检查是否为大写字母
        if (keyCode >= 65 && keyCode <= 90) {
            // 如果没有按Shift键但输入的是大写字母，则Caps Lock开启
            if (!isShift) {
                $warning.show();
            } else {
                $warning.hide();
            }
        } 
        // 如果按了Shift键且输入的是小写字母，则Caps Lock开启
        else if (keyCode >= 97 && keyCode <= 122 && isShift) {
            $warning.show();
        } 
        // 其他情况隐藏提示
        else {
            $warning.hide();
        }
    });
    
    // 当输入框失去焦点时隐藏提示
    $('#login-password, #register-password').on('blur', function() {
        $(this).siblings('.caps-lock-warning').hide();
    });
});