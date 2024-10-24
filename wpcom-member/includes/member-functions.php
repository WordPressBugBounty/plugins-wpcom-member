<?php defined( 'ABSPATH' ) || exit;

use WPCOM\Themer\Session;

add_filter( 'wpcom_login_form_items', 'wpcom_login_form_items' );
function wpcom_login_form_items( $items = array() ){
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', WPMX_TD),
            'icon' => 'user',
            'name' => 'user_login',
            'require' => true,
            'placeholder' =>  is_wpcom_enable_phone() ? __('Phone number / E-mail / Username', WPMX_TD) : __('Username or email address', WPMX_TD)
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('Password', 'label', WPMX_TD),
            'icon' => 'lock',
            'name' => 'user_password',
            'require' => true,
            'placeholder' => _x('Password', 'placeholder', WPMX_TD),
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_register_form_items', 'wpcom_register_form_items' );
function wpcom_register_form_items( $items = array() ){
    if(is_wpcom_enable_phone()) {
        $items += apply_filters( 'wpcom_sms_code_items', array() );
        $items += array(
            40 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', WPMX_TD),
                'icon' => 'lock',
                'name' => 'user_pass',
                'require' => true,
                'validate' => 'password',
                'placeholder' => _x('Password', 'placeholder', WPMX_TD),
            )
        );
    }else{
        $items += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Email address', 'label', WPMX_TD),
                'icon' => 'mail',
                'name' => 'user_email',
                'require' => true,
                'validate' => 'email',
                'placeholder' => _x('Email address', 'placeholder', WPMX_TD),
            ),
            20 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', WPMX_TD),
                'icon' => 'lock',
                'name' => 'user_pass',
                'require' => true,
                'validate' => 'password',
                'placeholder' => _x('Password', 'placeholder', WPMX_TD),
            ),
            30 => array(
                'type' => 'password',
                'label' => _x('Password', 'label', WPMX_TD),
                'icon' => 'lock',
                'name' => 'user_pass2',
                'require' => true,
                'validate' => 'password:user_pass',
                'placeholder' => _x('Confirm password', 'placeholder', WPMX_TD),
            ),
            40 => array(
                'type' => wpcom_member_captcha_type()
            ),
        );
    }
    return $items;
}

add_filter( 'wpcom_email_code_items', 'wpcom_email_code_items' );
function wpcom_email_code_items($items){
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', WPMX_TD),
            'icon' => 'envelope',
            'name' => 'user_email',
            'require' => true,
            'validate' => 'email',
            'placeholder' => _x('Email address', 'placeholder', WPMX_TD),
        ),
        20 => array(
            'type' => wpcom_member_captcha_type()
        ),
        30 => array(
            'type' => 'smsCode',
            'label' => _x('Verification code', 'label', WPMX_TD),
            'name' => 'sms_code',
            'icon' => 'shield-check',
            'validate' => 'sms_code:user_email',
            'target' => 'user_email',
            'require' => true,
            'placeholder' => _x('Please enter your verification code', 'placeholder', WPMX_TD)
        )
    );
    return $items;
}

// 插入默认的配置数据
add_filter( 'wpcom_account_tabs', 'wpcom_account_default_tabs' );
function wpcom_account_default_tabs( $tabs = array() ){
    $options = $GLOBALS['wpmx_options'];
    $tabs += array(
        10 => array(
            'slug' => 'general',
            'title' => __('General', WPMX_TD),
            'icon' => 'personal-circle'
        ),
        50 => array(
            'slug' => 'password',
            'title' => __('Password', WPMX_TD),
            'icon' => 'lock-circle'
        ),
        99 => array(
            'slug' => 'logout',
            'title' => __('Logout', WPMX_TD),
            'icon' => 'out-circle'
        )
    );
    if(!is_wpcom_enable_phone() && !(isset($options['social_login_on']) && $options['social_login_on']=='1')){
        $tabs[98989] = array(
            'slug' => 'bind',
            'title' => _x('Connect', 'title', WPMX_TD),
            'icon' => 'bind-circle',
            'parent' => 'general'
        );
    }else{
        $tabs[40] = array(
            'slug' => 'bind',
            'title' => _x('Connect', 'title', WPMX_TD),
            'icon' => 'bind-circle'
        );
    }
    return $tabs;
}

add_filter( 'wpcom_account_tabs_general_metas', 'wpcom_account_tabs_general_metas' );
function wpcom_account_tabs_general_metas( $metas ){
    $user = wp_get_current_user();
    if( !$user->ID ) return $metas;

    if(is_wpcom_enable_phone()) {
        $phone = $user->mobile_phone;
        if($phone){
            $url = add_query_arg(array('type' => 'phone', 'action' => 'change'), wpcom_subpage_url('bind'));
            $phone .= '<a class="member-bind-url" href="'.$url.'">'.__('Edit', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
        }else{
            $url = add_query_arg(array('type' => 'phone', 'action' => 'bind'), wpcom_subpage_url('bind'));
            $phone = __('Not set', WPMX_TD) . '<a class="member-bind-url" href="'.$url.'">'.__('Add phone number', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
        }

        $metas += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Phone number', 'label', WPMX_TD),
                'name' => 'mobile_phone',
                'value' => $phone,
                'disabled' => true
            )
        );
    }
    $email = $user->user_email;
    if($email && !wpcom_is_empty_mail($email)){
        $url = add_query_arg(array('type' => 'email', 'action' => 'change'), wpcom_subpage_url('bind'));
        $email .= '<a class="member-bind-url" href="'.$url.'">'.__('Edit', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
    }else{
        $url = add_query_arg(array('type' => 'email', 'action' => 'bind'), wpcom_subpage_url('bind'));
        $email = __('Not set', WPMX_TD) . '<a class="member-bind-url" href="'.$url.'">'.__('Add email address', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
    }

    $metas += array(
        20 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', WPMX_TD),
            'name' => 'user_email',
            'maxlength' => 64,
            'require' => true,
            'validate' => 'email',
            'value' => $email,
            'disabled' => true
        ),
        30 => array(
            'type' => 'text',
            'label' => __('Nickname', WPMX_TD),
            'name' => 'display_name',
            'maxlength' => 20,
            'require' => true,
            'value' => $user->display_name
        ),
        40 => array(
            'type' => 'textarea',
            'label' => __('Description', WPMX_TD),
            'maxlength' => 200,
            'rows' => 3,
            'name' => 'description',
            'desc' => __('Optional, description can not exceed 200 characters', WPMX_TD),
            'value' => $user->description
        )
    );

    return $metas;
}

add_filter( 'wpcom_account_tabs_bind_metas', 'wpcom_account_tabs_bind_metas' );
function wpcom_account_tabs_bind_metas( $metas ){
    global $wpdb;
    $options = $GLOBALS['wpmx_options'];
    $user = wp_get_current_user();
    if( !$user->ID ) return $metas;

    if(is_wpcom_enable_phone()) {
        $phone = $user->mobile_phone;
        if($phone){
            $url = add_query_arg(array('type' => 'phone', 'action' => 'change'), wpcom_subpage_url('bind'));
            $phone .= '<a class="member-bind-url" href="'.$url.'">'.__('Edit', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
        }else{
            $url = add_query_arg(array('type' => 'phone', 'action' => 'bind'), wpcom_subpage_url('bind'));
            $phone = __('Not set', WPMX_TD) . '<a class="member-bind-url" href="'.$url.'">'.__('Add phone number', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
        }
        $metas += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Phone number', 'label', WPMX_TD),
                'name' => 'mobile_phone',
                'value' => $phone,
                'disabled' => true
            )
        );
    }
    $email = $user->user_email;
    if($email && !wpcom_is_empty_mail($email)){
        $url = add_query_arg(array('type' => 'email', 'action' => 'change'), wpcom_subpage_url('bind'));
        $email .= '<a class="member-bind-url" href="'.$url.'">'.__('Edit', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
    }else{
        $url = add_query_arg(array('type' => 'email', 'action' => 'bind'), wpcom_subpage_url('bind'));
        $email = __('Not set', WPMX_TD) . '<a class="member-bind-url" href="'.$url.'">'.__('Add email address', WPMX_TD).'</a><span class="member-bind-tip">'.__('Private', WPMX_TD).'</span>';
    }
    $metas += array(
        20 => array(
            'type' => 'text',
            'label' => _x('Email address', 'label', WPMX_TD),
            'name' => 'user_email',
            'maxlength' => 64,
            'require' => true,
            'validate' => 'email',
            'value' => $email,
            'disabled' => true
        )
    );
    if(isset($options['social_login_on']) && $options['social_login_on']=='1'){
        $key = 20;
        $socials = apply_filters( 'wpcom_socials', array() );
        ksort($socials);
        if( $socials ){
            foreach ( $socials as $social ){
                if( $social['id'] && $social['key'] ) {
                    $key += 10;
                    $url = add_query_arg(array('from' => 'bind'), wpcom_social_login_url($social['name']));
                    $value = __('Not set', WPMX_TD) . '<a class="member-bind-url j-social-bind '.$social['name'].'" href="'.$url.'">'.__('Connect', WPMX_TD).'</a>';
                    $social_name = $social['name'];
                    $social['name'] = $social['name'] === 'wechat2' ? 'wechat' : $social['name'];
                    $type_name = $social['name'] === 'weapp' ? 'wxxcx' : $social['name'];
                    $openid = get_user_meta($user->ID, $wpdb->get_blog_prefix() . 'social_type_'.$type_name, true);
                    if($openid){
                        $value = __('Connected', WPMX_TD);
                        $name = get_user_meta($user->ID, $wpdb->get_blog_prefix() . 'social_type_'.$type_name.'_name', true);
                        $value = $name ?: $value;
                        $value = $value . '<a class="member-bind-url j-social-unbind" href="#" data-name="'.$social_name.'">'.__('Delete', WPMX_TD).'</a>';
                    }
                    $metas += array(
                        $key => array(
                            'type' => 'text',
                            'label' => $social['title'],
                            'name' =>  $social['name'],
                            'value' => $value,
                            'disabled' => true
                        )
                    );
                }
            }
        }
    }
    return $metas;
}

add_filter( 'wpcom_account_tabs_password_metas', 'wpcom_account_tabs_password_metas' );
function wpcom_account_tabs_password_metas( $metas ){
    $metas += array(
        10 => array(
            'type' => 'password',
            'label' => _x('Old password', 'label', WPMX_TD),
            'name' => 'old-password',
            'require' => true,
            'value' => '',
            'placeholder' => _x('Please enter your old password', 'placeholder', WPMX_TD)
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('New password', 'label', WPMX_TD),
            'name' => 'password',
            'require' => true,
            'validate' => 'password',
            'maxlength' => 32,
            'minlength' => 6,
            'desc' => __('Password must be 6-32 characters', WPMX_TD),
            'value' => '',
            'placeholder' => _x('Please enter your new password', 'placeholder', WPMX_TD)
        ),
        30 => array(
            'type' => 'password',
            'label' => _x('New password', 'label2', WPMX_TD),
            'name' => 'password2',
            'require' => true,
            'validate' => 'password:password',
            'value' => '',
            'placeholder' => _x('Please confirm your new password', 'placeholder', WPMX_TD)
        )
    );

    return $metas;
}

add_filter( 'wpcom_lostpassword_form_items', 'wpcom_lostpassword_form_items' );
function wpcom_lostpassword_form_items( $items = array() ){
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', WPMX_TD),
            'icon' => 'user',
            'name' => 'user_login',
            'require' => true,
            'placeholder' =>  is_wpcom_enable_phone() ? __('Phone number / E-mail / Username', WPMX_TD) : __('Username or email address', WPMX_TD)
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_resetpassword_form_items', 'wpcom_resetpassword_form_items' );
function wpcom_resetpassword_form_items( $items = array() ){
    $items += array(
        10 => array(
            'type' => 'password',
            'label' => _x('New password', 'label', WPMX_TD),
            'name' => 'password',
            'icon' => 'lock',
            'require' => true,
            'validate' => 'password',
            'maxlength' => 32,
            'minlength' => 6,
            'desc' => __('Password must be 6-32 characters', WPMX_TD),
            'value' => '',
            'placeholder' => _x('Please enter your new password', 'placeholder', WPMX_TD)
        ),
        20 => array(
            'type' => 'password',
            'label' => _x('New password', 'label2', WPMX_TD),
            'name' => 'password2',
            'icon' => 'lock',
            'require' => true,
            'validate' => 'password:password',
            'value' => '',
            'placeholder' => _x('Please confirm your new password', 'placeholder', WPMX_TD)
        ),
        30 => array(
            'type' => wpcom_member_captcha_type()
        )
    );
    return $items;
}

add_filter( 'wpcom_member_errors', 'wpcom_member_errors' );
function wpcom_member_errors( $errors ){
    $captcha = wpcom_member_captcha_type();
    $errors += array(
        'require' => __( ' is required', WPMX_TD ),
        'email' => __( 'This is not a valid email', WPMX_TD ),
        'pls_enter' => __( 'Please enter your ', WPMX_TD ),
        'password' => __( 'Your password must be 6-32 characters', WPMX_TD ),
        'passcheck' => __( 'Your passwords do not match', WPMX_TD ),
        'phone' => __( 'Please enter a valid phone number', WPMX_TD ),
        'terms' => __( 'Please read and agree with the terms', WPMX_TD ),
        'sms_code' => __( 'Your verification code error', WPMX_TD ),
        'captcha_verify' => $captcha == 'noCaptcha' ? __( 'Please slide to verify', WPMX_TD ) : __( 'Please click to verify', WPMX_TD ),
        'captcha_fail' => __( 'Security verification failed, please try again', WPMX_TD ),
        'nonce' => __( 'The nonce check failed', WPMX_TD ),
        'req_error' => __( 'Request Error!', WPMX_TD )
    );
    return $errors;
}

// 插入默认的配置数据
add_filter( 'wpcom_profile_tabs', 'wpcom_profile_default_tabs' );
function wpcom_profile_default_tabs( $tabs = array() ){
    $tabs[10] = array(
        'slug' => 'posts',
        'title' => __( 'Posts', WPMX_TD )
    );
    if ( wpmx_comment_status() ) {
        $tabs[20] = array(
            'slug' => 'comments',
            'title' => __( 'Comments', WPMX_TD )
        );
    }
    return $tabs;
}

add_filter( 'wpcom_socials', 'wpcom_socials' );
function wpcom_socials( $social ){
    $options = $GLOBALS['wpmx_options'];
    $types = array(
        'qq' => array(
            'title' => _x('QQ', 'social login', WPMX_TD),
            'icon' => defined('FRAMEWORK_VERSION') && version_compare(FRAMEWORK_VERSION, '2.7.17', '<=') ? 'qq' : 'qq-logo'
        ),
        'weibo' => array(
            'title' => _x('Weibo', 'social login', WPMX_TD),
            'icon' => 'weibo'
        ),
        'wechat' => array(
            'title' => _x('WeChat', 'social login', WPMX_TD),
            'icon' => 'wechat'
        ),
        'wechat2' => array(
            'title' => _x('WeChat', 'social login', WPMX_TD),
            'icon' => 'wechat'
        ),
        'weapp' => array(
            'title' => _x('WeChat', 'social login', WPMX_TD),
            'icon' => 'wechat'
        ),
        'google' => array(
            'title' => _x('Google', 'social login', WPMX_TD),
            'icon' => 'google-logo'
        ),
        'facebook' => array(
            'title' => _x('Facebook', 'social login', WPMX_TD),
            'icon' => 'facebook-circle'
        ),
        'twitter' => array(
            'title' => _x('Twitter', 'social login', WPMX_TD),
            'icon' => 'twitter'
        ),
        'github' => array(
            'title' => _x('Github', 'social login', WPMX_TD),
            'icon' => 'github-circle'
        )
    );

    $has_wechat = -1;
    $has_wechat2 = -1;
    $has_weapp = -1;
    if(isset($options['sl_type']) && is_array($options['sl_type']) && !empty($options['sl_type'])){
        foreach ($options['sl_type'] as $i => $type){
            if(isset($types[$type]) && isset($options['sl_id'][$i]) && $options['sl_id'][$i] && isset($options['sl_key'][$i]) && $options['sl_key'][$i]){
                $item = $types[$type];
                $item['name'] = $type;
                $item['id'] = $options['sl_id'][$i];
                $item['key'] = $options['sl_key'][$i];
                $item['index'] = $i;
                if($type === 'wechat') $has_wechat = $i*10;
                if($type === 'wechat2') {
                    $has_wechat2 = $i*10;
                    if(isset($options['sl_wechat_follow']) && isset($options['sl_wechat_follow'][$i]) && $options['sl_wechat_follow'][$i]){
                        $item['follow'] = 1;
                        $item['aeskey'] = trim($options['sl_wechat2_aeskey'][$i]);
                        if(isset($options['sl_wechat2_type']) && isset($options['sl_wechat2_type'][$i]) && $options['sl_wechat2_type'][$i] == '1'){
                            $item['code'] = isset($options['sl_wechat2_code'][$i]) && $options['sl_wechat2_code'][$i] ? $options['sl_wechat2_code'][$i] : '验证码：%CODE%。此验证码只用于网站登录，请勿转发他人，%TIME%分钟内有效。';
                            $item['qrcode'] = ($_qrcode = $options['sl_wechat2_qr'][$i] ?? '') ? wp_get_attachment_image_url($_qrcode, 'full') : '';
                            $item['keyword'] = isset($options['sl_wechat2_keyword'][$i]) && $options['sl_wechat2_keyword'][$i] ? $options['sl_wechat2_keyword'][$i] : '登录';
                        }else{
                            $item['welcome'] = isset($options['sl_wechat2_welc'][$i]) && $options['sl_wechat2_welc'][$i] ? $options['sl_wechat2_welc'][$i] : '登录成功！';
                        }
                    }
                }
                if($type === 'weapp') {
                    $has_weapp = $i*10;
                    $item['mobile'] = 0;
                    if(isset($options['sl_weapp_type']) && isset($options['sl_weapp_type'][$i]) && $options['sl_weapp_type'][$i]){
                        $item['mobile'] = 1;
                    }
                }
                $social[$i*10] = $item;
            }
        }
    }

    $is_wechat = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    if($is_wechat && $has_wechat2 > -1){ // 微信内置浏览器，并且配置了公众号登录，则以公众号登录为准
        if ($has_wechat > -1) unset($social[$has_wechat]);
        if ($has_weapp > -1) unset($social[$has_weapp]);
    }else if($has_wechat > -1 && !wp_is_mobile()){ // 电脑端，配置了开放平台登录，则使用开放平台登录
        if ($has_wechat2 > -1) unset($social[$has_wechat2]);
        if ($has_weapp > -1) unset($social[$has_weapp]);
    } else if ($is_wechat && $has_wechat && $has_weapp) { // 微信内置浏览器，未配置公众号登录，同时配置了开放平台登录和小程序登录，使用小程序
        unset($social[$has_wechat]);
    } else if (!wp_is_mobile() && $has_wechat2 && $has_weapp) { // 电脑端，未配置开放平台登录，同时配置了公众号登录和小程序登录，使用公众号
        unset($social[$has_weapp]);
    }else if(wp_is_mobile() && !$is_wechat){ // 手机浏览器
        if($has_weapp > -1){ // 优先使用小程序，企业认证小程序可直接跳转，未认证小程序也可截图
            if ($has_wechat > -1) unset($social[$has_wechat]);
            if ($has_wechat2 > -1) unset($social[$has_wechat2]);
        }else if($has_wechat > -1 && $has_wechat2 > -1){ // 开放平台和公众号
            unset($social[$has_wechat2]);
        }
    }
    return $social;
}

add_filter( 'wpcom_approve_resend_form_items', 'wpcom_approve_resend_form_items' );
function wpcom_approve_resend_form_items( $items = array() ){
    $value = isset($_REQUEST['login']) ? wp_unslash($_REQUEST['login']) : '';
    $items += array(
        10 => array(
            'type' => 'text',
            'label' => _x('Username', 'label', WPMX_TD),
            'icon' => 'user',
            'name' => 'user_login',
            'require' => true,
            'placeholder' => __('Username or email address', WPMX_TD),
            'disabled' => true,
            'value' => $value
        ),
        20 => array(
            'type' => wpcom_member_captcha_type()
        ),
    );
    if($value){
        $items += array(
            30 => array(
                'type' => 'hidden',
                'name' => 'user_login',
                'value' => $value
            )
        );
    }
    return $items;
}

if( ! function_exists('is_wpcom_member_page') ){
    function is_wpcom_member_page( $page = 'account', $pageid = 0 ){
        $options = $GLOBALS['wpmx_options'];
        $key = $page === 'social_login' ? 'social_login_page' : 'member_page_' . $page;
        if ( isset($options[$key]) && $options[$key] && ( ($pageid && $pageid == $options[$key]) || is_page($options[$key]) ) ) {
            return true;
        }
        return false;
    }
}

function wpcom_get_cover_url( $user_id ){
    $cover_img = WPMX_URI . 'images/lazy.png';
    if ( $user_id && $url = get_user_meta( $user_id, 'wpcom_cover', 1) ) {
        if(preg_match('/^(http|https|\/\/)/i', $url)){
            $cover_img = $url;
        }else{
            $uploads = wp_upload_dir();
            $cover_img = $uploads['baseurl'] . $url;
        }
    } else {
        $options = $GLOBALS['wpmx_options'];
        if( isset($options['member_cover']) && $options['member_cover'] )
            $cover_img = esc_url($options['member_cover']);
    }
    $cover_img = apply_filters( 'wpcom_member_user_cover', $cover_img, $user_id );

    $cover_img = preg_replace('/^(http|https):/i', '', $cover_img);
    return $cover_img;
}

function wpcom_get_user_group($user_id){
    if($user_id) {
        $group = wp_get_object_terms($user_id, 'user-groups');
        if (!is_wp_error($group) && isset($group[0])) return $group[0];
    }
}

function wpcom_send_active_email( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID || wpcom_is_empty_mail($user->user_email)) return false;

    $key = get_password_reset_key( $user );
    $url = add_query_arg( array(
        'approve' => 'pending',
        'key' => $key,
        'login' => rawurlencode( $user->user_login )
    ), wp_registration_url() );

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    /* translators: %s: display_name */
    $message = '<p>' . sprintf( __( 'Hi, %s!', WPMX_TD ), $user->display_name ) . '</p>';
    /* translators: %s: site_name */
    $message .= '<p>' . sprintf( __( 'Welcome to %s. To activate your account and verify your email address, please click the following link:', WPMX_TD ), $site_name ) . '</p>';
    $message .= '<p><a href="'.$url.'">'.$url.'</a></p><p></p>';
    $message .= '<p>' . __( 'If this was a mistake, ignore this email and nothing will happen.', WPMX_TD ) . "</p>";

    /* translators: %s: site_name */
    $title = sprintf( __( '[%s] Please verify your email address', WPMX_TD ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $user->user_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', WPMX_TD);

    return true;
}

function wpcom_send_active_to_admin( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID) return __( 'The user does not exist', WPMX_TD );

    $admin_email = get_option('admin_email');

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    /* translators: %1$s: display_name, %2$s: site_name */
    $message = '<p>' . sprintf( __( '%1$s has just created an account on %2$s!', WPMX_TD ), $user->display_name, $site_name ) . '</p>';
    /* translators: %s: user_login */
    $message .= '<p>' .sprintf( __( 'Username: %s', WPMX_TD ), $user->user_login ) . '</p>';
    /* translators: %s: user_email */
    $message .= '<p>' .sprintf( __( 'E-Mail: %s', WPMX_TD ), $user->user_email ) . '</p><p></p>';

    $message .= '<p>' . __( 'If you want to approve the new user, please go to wp-admin page.', WPMX_TD ) . '</p>';

    /* translators: %s: site_name */
    $title = sprintf( __( '[%s] New user account', WPMX_TD ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $admin_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', WPMX_TD);

    return true;
}

function wpcom_send_actived_email( $user_id ){
    $user = get_user_by( 'ID', $user_id );
    if(!$user->ID || wpcom_is_empty_mail($user->user_email)) return false;

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $login_url = wpcom_login_url();

    /* translators: %s: display_name */
    $message = '<p>' . sprintf( __( 'Hi, %s!', WPMX_TD ), $user->display_name ) . '</p>';
    /* translators: %s: login url */
    $message .= '<p>' . sprintf( __( 'Congratulations, your account has been activated successfully, you can now login: <a href="%1$s">%2$s</a>', WPMX_TD ), $login_url, $login_url ) . '</p>';

    /* translators: %s: site_name */
    $title = sprintf( __( '[%s] Welcome to join us', WPMX_TD ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $user->user_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', WPMX_TD);

    return true;
}

function wpcom_send_email_code( $email ){
    $user = wp_get_current_user();
    if(!$user->ID || wpcom_is_empty_mail($email)) return false;

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $code = wpcom_generate_sms_code(sanitize_user($email, true));
    /* translators: %s: display_name */
    $message = '<p>' . sprintf( __( 'Hi, %s!', WPMX_TD ), $user->display_name ) . '</p>';
    /* translators: %s: verification code */
    $message .= '<p>' . sprintf( __( 'Your verification code is <b style="color:red;">%s</b>, please enter in 10 minutes.', WPMX_TD ), $code ) . '</p>';
    $message .= '<p></p>';
    $message .= '<p>' . __( 'If this was a mistake, ignore this email and nothing will happen.', WPMX_TD ) . "</p>";

    /* translators: %s: site_name */
    $title = sprintf( __( '[%s] Your verification code', WPMX_TD ), $site_name );

    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', WPMX_TD);

    return true;
}

add_action('wp_ajax_wpcom_is_login', 'wpcom_is_login');
add_action('wp_ajax_nopriv_wpcom_is_login', 'wpcom_is_login');
// 登录状态
function wpcom_is_login(){
    $res = array();
    $current_user = wp_get_current_user();
    if($current_user->ID){
        $options = $GLOBALS['wpmx_options'];
        $res['result'] = 0;
        $res['avatar'] = get_avatar( $current_user->ID, 60 );
        $res['url'] = get_author_posts_url( $current_user->ID );
        if( function_exists('wpcom_account_url') ) $res['account'] = wpcom_account_url();
        $res['display_name'] = $current_user->display_name;

        $menus = array();

        $show_profile = apply_filters( 'wpcom_member_show_profile' , true );
        if($show_profile) {
            $menus[] = array(
                'url' => $res['url'],
                'title' => __('Profile', WPMX_TD)
            );
        }

        if(isset($options['profile_menu_url']) && isset($options['profile_menu_title']) && $options['profile_menu_url']){
            $i=1;
            foreach($options['profile_menu_url'] as $menu){
                if($menu && $options['profile_menu_title'][$i-1]) {
                    $menus[] = array(
                        'url' => esc_url($menu),
                        'title' => $options['profile_menu_title'][$i-1]
                    );
                }
                $i++;
            }
        }

        $menus[] = array(
            'url' => isset( $res['account'] ) ? $res['account'] : $res['url'],
            'title' => __('Account', WPMX_TD)
        );
        $menus[] = array(
            'url' => wp_logout_url(),
            'title' => __( 'Logout', WPMX_TD )
        );
        $res['menus'] = apply_filters('wpcom_profile_menus', $menus);
    }else{
        $res['result'] = -1;
    }

    if ( function_exists('is_woocommerce') ) {
        ob_start();

        woocommerce_mini_cart();

        $mini_cart = ob_get_clean();

        $data = array(
            'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', array(
                    'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
                )
            ),
            'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', WC()->cart->get_cart_for_session() ? md5( wp_json_encode( WC()->cart->get_cart_for_session() ) ) : '', WC()->cart->get_cart_for_session() ),
        );

        $res['wc'] = $data;
    }
    $res = apply_filters('wpcom_is_login', $res);
    wp_send_json($res);
}

function is_wpcom_enable_phone(){
    $options = $GLOBALS['wpmx_options'];
    return function_exists('wpcom_sms_code_sender') && isset($options['enable_phone']) && $options['enable_phone'];
}

function wpcom_check_sms_code($phone, $val){
    // 检查session、验证码值
    $key = 'code_'.$phone;
    $code = Session::get($key);
    if($code && $code == $val ){
        return true;
    }
    return false;
}

function wpcom_generate_sms_code($phone){
    $code = '' . wp_rand(0,9) . '' . wp_rand(0,9) . '' . wp_rand(0,9) . '' . wp_rand(100,999);
    $key = 'code_'.$phone;
    Session::set($key, $code, 600);
    return $code;
}

function wpcom_generate_unique_username( $username ) {
    $username = sanitize_user( $username, true );
    static $i;
    if ( null === $i ) {
        $i = 1;
    } else {
        $i ++;
    }
    if ( ! username_exists( $username ) ) {
        return $username;
    }
    $new_username = sprintf( '%s%s', $username, $i );
    if ( ! username_exists( $new_username ) ) {
        return $new_username;
    } else {
        return call_user_func( __FUNCTION__, $username );
    }
}

function wpcom_mobile_phone_exists($phone){
    $args = array(
        'meta_key'     => 'mobile_phone',
        'meta_value'   => $phone,
    );
    $users = get_users($args);
    return isset($users[0]) && $users[0]->ID ? $users[0]->ID : false;
}

function wpcom_aliyun_afs( $csessionid, $token, $sig, $scene ){
    $options = $GLOBALS['wpmx_options'];
    $body = array(
        'SessionId' => $csessionid,
        'Token' => $token,
        'Sig' => $sig,
        'Scene' => $scene,
        'AppKey' => trim($options['nc_appkey']),
        'RemoteIp' => wpmx_get_ip(),
        'RegionId' => 'cn-shanghai',
        'AccessKeyId' => trim($options['nc_access_id']),
        'Format' => 'JSON',
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureVersion' => '1.0',
        'SignatureNonce' => md5(uniqid(wp_rand(), true)),
        'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'Action' => 'AuthenticateSig',
        'Version' => '2018-01-12'
    );
    $body['Signature'] = wpcom_aliyun_afs_signature($body, trim($options['nc_access_secret']));
    $result = wp_remote_request('http://afs.aliyuncs.com/',
        array(
            'method' => 'POST',
            'timeout' => 10,
            'headers' => array(
                'x-sdk-client' => 'php/2.0.0'
            ),
            'body' => $body
        )
    );
    if( !is_wp_error( $result ) ){
        $data = isset($result['body']) ? json_decode($result['body']) : '';
        if(isset($data->Code) && $data->Code == '100'){
            return true;
        }else if(isset($data->Msg)){
            return new WP_Error('aliyun_afs_error', $data->Msg);
        }
    }
    return false;
}

function wpcom_aliyun_afs_signature($parameters, $accessKeySecret) {
    ksort($parameters);
    $canonicalizedQueryString = '';
    foreach ($parameters as $key => $value) {
        $canonicalizedQueryString .= '&' . wpcom_aliyun_afs_encode($key) . '=' . wpcom_aliyun_afs_encode($value);
    }
    $stringToSign = 'POST&%2F&' . wpcom_aliyun_afs_encode(substr($canonicalizedQueryString, 1));
    return base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . "&", true));
}

function wpcom_aliyun_afs_encode($str) {
    $res = urlencode($str);
    $res = preg_replace('/\+/', '%20', $res);
    $res = preg_replace('/\*/', '%2A', $res);
    $res = preg_replace('/%7E/', '~', $res);
    return $res;
}

function wpmx_aliyun_captcha($CaptchaVerifyParam) {
    $sha256 = bin2hex(wpmx_aliyun_toString(wpmx_aliyun_toBytes(hash('sha256', '', true))));
    $headers = [
        'host' => 'captcha.cn-shanghai.aliyuncs.com',
        'x-acs-version' => '2023-03-05',
        'x-acs-action' => 'VerifyCaptcha',
        'user-agent' => sprintf('AlibabaCloud (%s; %s) PHP/%s Core/3.1 TeaDSL/1', PHP_OS, \PHP_SAPI, PHP_VERSION),
        'x-acs-date' => gmdate('Y-m-d\\TH:i:s\\Z'),
        'x-acs-signature-nonce' => md5(uniqid() . uniqid(md5(microtime(true)), true)),
        'accept' => 'application/json',
        'x-acs-content-sha256' => $sha256
    ];
    $headers['Authorization'] = wpmx_aliyun_authorization($headers, $CaptchaVerifyParam);
    if($headers['Authorization']){
        $result = wp_remote_request(
            'https://captcha.cn-shanghai.aliyuncs.com/?CaptchaVerifyParam=' . urlencode($CaptchaVerifyParam),
            array(
                'method' => 'POST',
                'timeout' => 10,
                'headers' => $headers
            )
        );
        if (!is_wp_error($result)) {
            $data = isset($result['body']) ? json_decode($result['body']) : '';
            if (isset($data->Code) && $data->Code == 'Success' && isset($data->Result)) {
                return $data->Result->VerifyResult;
            } else if (isset($data->Message)) {
                return new WP_Error('aliyun_aliyun_captcha', $data->Code . ': ' . $data->Message);
            }
        }
    }
    return false;
}

function wpmx_aliyun_authorization($headers, $CaptchaVerifyParam){
    $options = $GLOBALS['wpmx_options'];
    $accessKeyId = isset($options['alic_access_id']) && $options['alic_access_id'] ? trim($options['alic_access_id']) : '';
    $accessKeySecret = isset($options['alic_access_secret']) && $options['alic_access_secret'] ? trim($options['alic_access_secret']) : '';
    if($accessKeyId === '' || $accessKeySecret === '') return false;

    $signatureAlgorithm = 'ACS3-HMAC-SHA256';
    $auth = $signatureAlgorithm . ' Credential='. $accessKeyId.',SignedHeaders=host;x-acs-action;x-acs-content-sha256;x-acs-date;x-acs-signature-nonce;x-acs-version,Signature=';
    $signHeaders = [];
    foreach ($headers as $k => $v) {
        $k = strtolower($k);
        if (0 === strpos($k, 'x-acs-') || 'host' === $k || 'content-type' === $k) {
            $signHeaders[$k] = $v;
        }
    }
    ksort($signHeaders);
    $_headers = [];
    foreach ($headers as $k => $v) {
        $k = strtolower($k);
        if (0 === strpos($k, 'x-acs-') || 'host' === $k || 'content-type' === $k) {
            $_headers[$k] = trim($v);
        }
    }
    $canonicalHeaderString = '';
    ksort($_headers);
    foreach ($_headers as $k => $v) {
        $canonicalHeaderString .= $k . ':' . trim(str_replace(["\t", "\n", "\r", "\f"], '', $v)) . "\n";
    }
    if (empty($canonicalHeaderString)) {
        $canonicalHeaderString = "\n";
    }
    $canonicalRequest = "POST\n/\n" . "CaptchaVerifyParam=" . urlencode($CaptchaVerifyParam) . "\n" .
    $canonicalHeaderString . "\n" . implode(';', array_keys($signHeaders)) . "\n" . $headers['x-acs-content-sha256'];

    $strtosign = "ACS3-HMAC-SHA256\n" . bin2hex(wpmx_aliyun_toString(wpmx_aliyun_toBytes(hash('sha256', $canonicalRequest, true))));
    $signature = bin2hex(hash_hmac('sha256', $strtosign, $accessKeySecret, true));
    $auth .= $signature;
    return $auth;
}

function wpmx_aliyun_toBytes($string) {
    $bytes = [];
    for ($i = 0; $i < \strlen($string); ++$i) {
        $bytes[] = \ord($string[$i]);
    }

    return $bytes;
}

function wpmx_aliyun_toString($bytes) {
    if (\is_string($bytes)) {
        return $bytes;
    }
    $str = '';
    foreach ($bytes as $ch) {
        $str .= \chr($ch);
    }

    return $str;
}

function wpmx_get_ip(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $cip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $cip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])){
        $cip = $_SERVER['REMOTE_ADDR'];
    }
    $cip = isset($cip) ? filter_var($cip, FILTER_VALIDATE_IP) : '';
    return $cip ?: 'none';
}

function wpcom_member_captcha_type(){
    $options = $GLOBALS['wpmx_options'];
    $type = '';
    if(isset($options['member_captcha']) && $options['member_captcha']!==''){
        switch ($options['member_captcha']){
            case '0': // 防水墙
                $type = 'TCaptcha';
                break;
            case '1': // 阿里云1.0
                $type = 'noCaptcha';
                break;
            case '4': // 阿里云2.0
                $type = 'aliCaptcha';
                break;
            case '2': // hCaptcha
                $type = 'hCaptcha';
                break;
            case '3': // reCAPTCHA
                $type = 'reCAPTCHA';
                break;
            case '9': // WPCOM内置
                $type = '_Captcha';
                break;
        }
    }
    return $type;
}

function wpcom_is_empty_mail($mail){
    if(preg_match('/@email\.empty$/i', $mail) || preg_match('/@weixin\.qq$/i', $mail) || preg_match('/@(weapp|swan|alipay|toutiao|qq)\.app$/i', $mail)){
        return true;
    }
    return false;
}

function wpcom_send_notification($to, $title, $content){
    if(isset($GLOBALS['_notification']) && $GLOBALS['_notification'] && $to && $title && $content){
        return $GLOBALS['_notification']->add_notification($to, $title, $content);
    }
    return false;
}

add_filter( 'wpmx_localize_script', 'wpcom_login_js_lang' );
function wpcom_login_js_lang($scripts){
    $scripts['js_lang'] = isset($scripts['js_lang']) ? $scripts['js_lang'] : array();
    $scripts['js_lang'] += array(
        'login_desc' => __('You are not signed in, please sign in before proceeding with related operations!', WPMX_TD),
        'login_title' => __('Please sign in', WPMX_TD),
        'login_btn' => __('Sign in', WPMX_TD),
        'reg_btn' => __('Sign up', WPMX_TD)
    );
    if(!is_user_logged_in()){
        $scripts['login_url'] = wp_login_url();
        $scripts['register_url'] = wp_registration_url();
    }
    return $scripts;
}

add_action('wp_ajax_wpcom_captcha', 'wpcom_captcha');
add_action('wp_ajax_nopriv_wpcom_captcha', 'wpcom_captcha');
function wpcom_captcha(){
    $res = array('result' => 0);
    $str = isset($_POST['str']) ? sanitize_text_field($_POST['str']) : '';
    if($str && $_str = base64_decode($str)){
        $_str = base64_decode(strrev($_str));
        $data = json_decode(urldecode($_str), true);
        if($data && isset($data['width']) && $data['width'] && isset($data['height']) && $data['height'] && isset($data['sliderL']) && $data['sliderR']){
            $count = Session::get('captcha_count');
            $time = current_time('U');
            if($count && $count = json_decode($count, true)){
                if($count['time'] && $time - 600 < $count['time']){
                    $count['total'] = $count['total'] ? (int) $count['total'] : 0;
                    // 近10分钟调用次数超过20次
                    if($count['total'] > 20) {
                        $res['result'] = -2;
                        $res['msg'] = __('You have sent too many requests', WPMX_TD);
                        wp_send_json($res);
                    }
                }else{
                    $count['time'] = $time;
                    $count['total'] = 0;
                }
            }else{
                $count['time'] = $time;
                $count['total'] = 0;
            }

            $l = $data['sliderL'] + $data['sliderR'] * 2 + 3; // 滑块实际边长
            $data['x'] = wpmx_captcha_random_number($l + 10, $data['width'] - ($l + 10));
            $data['y'] = wpmx_captcha_random_number(10 + $data['sliderR'] * 2, $data['height'] - ($l + 10));

            $data['time'] = $time;
            $data['UA'] = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
            $code = md5(wp_json_encode($data));
            $data['ip'] = wpmx_get_ip();
            $res['nonce'] = wp_create_nonce( 'captcha_' . $code );
            $res['str'] = strrev(base64_encode(strrev($data['x'] . $res['nonce'] . $data['y']).'@'));
            $res['time'] = $time;
            $res = apply_filters('wpcom_captcha_data', $res, $data);
            $count['total'] = $count['total'] + 1;
            Session::set('captcha_count', wp_json_encode($count), 600);
            Session::set('captcha', wp_json_encode($data), 600);
        }
    }

    if(!isset($res['nonce'])){
        $res['result'] = -1;
        $res['msg'] = __('No captcha was found', WPMX_TD);
    }
    wp_send_json($res);
}

function wpmx_captcha_random_number($start, $end) {
    return $end <= $start ? $start : wp_rand($start, $end);
};

function wpmx_captcha_verify($data, $ticket){
    $verify = false;
    if($data && $data = base64_decode(strrev($data))){
        $ip = wpmx_get_ip();
        $time = current_time('U');

        // 检查滑动操作
        $_data = Session::get('captcha');
        if($_data && $_data = json_decode($_data, true) ){
            $ticket = base64_decode(strrev($ticket));
            $_data['UA'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $data = $_data['time'] ? str_replace(strrev($_data['time']), '', $data) : '';
            if($data && $ticket && $data = base64_decode(strrev($data))){
                $ticket = explode(':', $ticket);
                $data = json_decode($data, true);
                if($data && isset($data['time']) && $data['time'] == $_data['time'] && $ticket && $ticket[1] && is_numeric($data['trail']) && $data['trail'] && $data['verified']){
                    $_data['page'] = $data['page'];
                    unset($_data['ip']);
                    $code = md5(wp_json_encode($_data));
                    $moveX = $data['eventX'] - $data['originX'];
                    $moveX = ($_data['width'] - 40 - 20) / ($_data['width'] - 40) * $moveX;
                    if(wp_verify_nonce($ticket[1], 'captcha_' . $code) && isset($_data['offset']) && isset($_data['x']) && $moveX && is_numeric($moveX)){
                        $abs = $moveX - $_data['x'];
                        $abs = $abs < 0 ? -$abs : $abs;
                        if($abs < $_data['offset']){
                            $verify = true;
                        }
                    }
                }
            }
        }

        // 检查验证频率
        if($verify){
            $captcha_verify = Session::get('captcha_verify');
            if($captcha_verify && $captcha_verify = json_decode($captcha_verify, true)){
                $count = isset($captcha_verify['count']) && $captcha_verify['count'] ? (int) $captcha_verify['count'] : 0;
                if($captcha_verify['time'] && $time - 600 < $captcha_verify['time']){
                    // 近10分钟成功次数超过10次
                    if($count > 10) {
                        $verify = false;
                    }
                }
                if($verify){
                    $captcha_verify['count'] = $count + 1;
                    $captcha_verify['last_ip'] = $ip;
                }
            }else{
                $captcha_verify = array(
                    'count' => 1,
                    'last_ip' => $ip,
                    'time' => $time
                );
            }
            Session::set('captcha_verify', wp_json_encode($captcha_verify), 600);
        }

        // 检查IP验证频率
        if($verify){
            $captcha_verify_ip = Session::get('_captcha_verify_'.$ip);
            if($captcha_verify_ip && $captcha_verify_ip = json_decode($captcha_verify_ip, true)){
                $count = isset($captcha_verify_ip['count']) && $captcha_verify_ip['count'] ? (int) $captcha_verify_ip['count'] : 0;
                if($captcha_verify_ip['time'] && $time - 600 < $captcha_verify_ip['time']){
                    // 近10分钟成功次数超过20次
                    if($count > 20) {
                        $verify = false;
                    }
                }
                if($verify){
                    $captcha_verify_ip['count'] = $count + 1;
                }
            }else{
                $captcha_verify_ip = array(
                    'count' => 1,
                    'time' => $time
                );
            }
            Session::set('_captcha_verify_'.$ip, wp_json_encode($captcha_verify_ip), 600);
        }
    }
    return $verify;
}

if (!function_exists('wpcom_setup') && !defined('WPCOM_MP_VERSION')) {
    add_action('wp_ajax_wpcom_send_sms_code', 'wpmx_send_sms_code');
    add_action('wp_ajax_nopriv_wpcom_send_sms_code', 'wpmx_send_sms_code');
}
function wpmx_send_sms_code(){
    $res = array();
    $res['result'] = 1; // 0：发送失败；1：发送成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
    $res['error'] = '';

    $errors = apply_filters( 'wpcom_member_errors', array() );

    $msg = array(
        '0' => __( 'Failed to send', WPMX_TD ),
        '1' => __('Send success', WPMX_TD),
        '-1' => $errors['nonce'],
        '-2' => $errors['captcha_fail'],
        '-3' => $errors['captcha_verify']
    );

    if( (isset($_POST['member_form_accountbind_nonce']) && isset($_POST['user_email'])) ||
        (isset($_POST['member_form_account_change_bind_nonce']) && isset($_POST['type']) && $_POST['type']=='email') ){
        $filter = 'wpcom_email_code_items';
    }else{
        $filter = 'wpcom_sms_code_items';
    }
    $items = apply_filters($filter, array());
    $target = 'user_phone';
    if($items){
        foreach ($items as $item){
            if($item['type']==='smsCode'){
                $target = $item['target'];
                break;
            }
        }
    }

    if( isset($_POST['member_form_smscode_nonce'])){ // 找回密码的验证短信
        $_POST[$target] = Session::get('lost_password_phone');
    }

    if( isset($_POST['member_form_account_change_bind_nonce'])){ // 更换绑定安全验证短信
        $user = wp_get_current_user();
        $_POST[$target] = isset($_POST['type']) && $_POST['type']=='phone' ? $user->mobile_phone : $user->user_email;
    }

    $res = wpcom_form_validate( $res, 'send_sms_code', $filter );

    if ($res['result'] == 1) {
        if(is_email($_POST[$target])){
            if(!wpcom_send_email_code(sanitize_text_field($_POST[$target]))){
                $res['result'] = 0;
                $res['error'] = __('Failed to send email', WPMX_TD);
            }
        }else{
            $send = wpcom_sms_code_sender($_POST[$target], isset($_POST['user_phone_country']) ? sanitize_text_field($_POST['user_phone_country']) : '');
            if($send->result!==0){ // 发送失败
                $res['result'] = 0;
                $res['error'] = $send->errmsg;
            }
        }
        if($res['result'] == 1){
            if(isset($_POST['ticket'])){
                $ticket = sanitize_text_field($_POST['ticket']);
                $randstr = sanitize_text_field($_POST['randstr']);
                $last_ticket = $ticket . '+' . $randstr;
            }else if(isset($_POST['csessionid'])){
                $csessionid = sanitize_text_field($_POST['csessionid']);
                $token = sanitize_text_field($_POST['token']);
                $sig = sanitize_text_field($_POST['sig']);
                $scene = sanitize_text_field($_POST['scene']);
                $last_ticket = $csessionid . '+' . $token . '+' . $sig . '+' . $scene;
            }else if(isset($_POST['h-captcha-response'])){
                $last_ticket = sanitize_text_field($_POST['h-captcha-response']);
            }else if(isset($_POST['g-recaptcha-response'])){
                $last_ticket = sanitize_text_field($_POST['g-recaptcha-response']);
            }else if(isset($_POST['verify-key'])){
                $last_ticket = sanitize_text_field($_POST['verify-key']);
            }
            if(isset($last_ticket)) Session::set('last_ticket', $last_ticket);
        }
    }

    if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

    wp_send_json($res);
}