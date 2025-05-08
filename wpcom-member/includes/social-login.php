<?php
namespace WPCOM\Member;
use WPCOM\Themer\Session;

defined( 'ABSPATH' ) || exit;

class Social_Login {
    public $social;
    protected $type;
    protected $page;
    protected $redirect_uri;
    public function __construct() {
        $options = $GLOBALS['wpmx_options'];
        if( isset($options['social_login_on']) && $options['social_login_on']=='1' ) {
            $this->type = '';

            $socials = apply_filters( 'wpcom_socials', array() );
            ksort($socials);

            $this->social = array();
            foreach ( $socials as $social ){
                if( $social['id'] && $social['key'] ) {
                    $social['id'] = trim($social['id']);
                    $social['key'] = trim($social['key']);
                    $this->social[$social['name']] = $social;
                }
            }

            if( isset($this->social['wechat2']) && !isset($this->social['wechat'])){
                $this->social['wechat'] = $this->social['wechat2'];
            }

            if($this->social) {
                add_action( 'init', array($this, 'init'), 5 );
                add_action( 'body_class', array($this, 'body_class') );
                add_action( 'wp_footer', array($this, 'unset_session'));

                add_action('wp_ajax_wpcom_sl_login', array($this, 'login_to_bind'));
                add_action('wp_ajax_nopriv_wpcom_sl_login', array($this, 'login_to_bind'));

                add_action('wp_ajax_wpcom_sl_create', array($this, 'create'));
                add_action('wp_ajax_nopriv_wpcom_sl_create', array($this, 'create'));

                add_action('wp_ajax_wpcom_wechat2_login_check', array($this, 'wechat2_login_check'));
                add_action('wp_ajax_nopriv_wpcom_wechat2_login_check', array($this, 'wechat2_login_check'));

                add_action('wp_ajax_wpcom_wechat2_qrcode', array($this, 'wechat2_qrcode'));
                add_action('wp_ajax_nopriv_wpcom_wechat2_qrcode', array($this, 'wechat2_qrcode'));

                add_action('wp_ajax_wpcom_weapp_qrcode', array($this, 'weapp_qrcode'));
                add_action('wp_ajax_nopriv_wpcom_weapp_qrcode', array($this, 'weapp_qrcode'));

                add_action('wp_ajax_wpcom_wxmp_notify', array($this, 'wechat2_notify'));
                add_action('wp_ajax_nopriv_wpcom_wxmp_notify', array($this, 'wechat2_notify'));
            }

            add_shortcode("wpcom-social-login", array($this, 'wpcom_social_login'));
        }
    }

    function init(){
        if ( isset($_GET['type']) && isset($_GET['action']) ) {
            $options = $GLOBALS['wpmx_options'];
            $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
            $this->page = $page_id ? untrailingslashit(get_permalink($page_id)) : '';

            $this->type = sanitize_text_field(wp_unslash($_GET['type']));
            if(!in_array($this->type, array_keys($this->social)) || !isset($_GET['action'])){
                return false;
            }

            $args = array( 'type'=>$this->type, 'action'=>'callback' );
            $this->redirect_uri = add_query_arg( $args, $this->page );

            if ($_GET['action'] == 'login') {
                Session::set('from', isset($_GET['from']) && $_GET['from'] === 'bind' ? 'bind' : '');
                if(isset($_GET['redirect_to']) && $redirect_to = sanitize_url($_GET['redirect_to'])){
                    // 有跳转回前页，保存到session
                    Session::set('redirect_to', $redirect_to);
                }
                $this->{$this->type . '_login'}();
            } else if ($_GET['action'] === 'callback') {
                if(!isset($_GET['code']) || isset($_GET['error']) || isset($_GET['error_code']) || isset($_GET['error_description'])){
                    wp_die("<h3>错误：</h3>Code获取出错，请重试！");
                    exit();
                }

                if( isset($_GET['uuid']) && $uuid = sanitize_key(wp_unslash($_GET['uuid'])) ){
                    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width"><title>微信登录</title></head><body><p style="font-size: 18px;color:#333;text-align: center;padding-top: 100px;">登录成功，请返回电脑端继续操作！</p></body></html>';
                    $uuid = $this->type === 'weapp' ? substr(md5($uuid),2,26) : $uuid;
                    Session::set('_'.$uuid, sanitize_text_field(wp_unslash($_GET['code'])));
                    if(isset($_GET['redirect_to']) && $redirect_to = sanitize_url($_GET['redirect_to'])){
                        // 有跳转回前页，保存到session
                        Session::set('redirect_to', $redirect_to);
                    }
                    exit;
                }

                $this->{$this->type.'_callback'}(sanitize_text_field(wp_unslash($_GET['code'])));

                $access_token = Session::get('access_token');
                if (!$access_token || strlen($access_token) < 6 || !$this->type){
                    wp_die("<h3>错误：</h3>Token获取出错，请重试！");
                    exit();
                }
                $openid = Session::get('openid');
                $newuser = $this->{$this->type.'_new_user'}();
                $openid = $openid ?: Session::get('openid');
                $unionid = Session::get('unionid');
                $bind_user = $this->is_bind($this->type, $openid, $unionid);
                $from = Session::get('from');
                $from = $from ?: (isset($_GET['from']) ? sanitize_text_field(wp_unslash($_GET['from'])) : '');
                $bind = $from && $from === 'bind' ? true : false;
                if($bind_user && $bind_user->ID){
                    do_action('wpcom_sl_unionid_login', $bind_user->ID, $this->type, $openid, $unionid);
                    if(!$bind) {
                        if (isset($newuser['nickname']))
                            update_user_option($bind_user->ID, 'social_type_' . $newuser['type'] . '_name', $newuser['nickname']);
                        Session::delete('', 'openid');
                        Session::delete('', 'from');
                        Session::delete('', 'access_token');
                        $redirect_to = Session::get('redirect_to') ?: '';
                        if($redirect_to === '' && isset($options['login_redirect']) && $options['login_redirect'] !== ''){
                            $redirect_to = $options['login_redirect'];
                        }else if($redirect_to === ''){
                            $redirect_to = home_url();
                        }
                        Session::delete('', 'redirect_to');
                        $this->login($bind_user->ID);
                        wp_redirect($redirect_to);
                        exit;
                    }else{ // 已绑定其他用户
                        wp_die("<h3>错误：</h3>当前社交账号已绑定本站其他用户！");
                        exit();
                    }
                }

                if(!isset($newuser['openid'])||strlen($newuser['openid'])<6){
                    wp_die("<h3>错误：</h3>OpenId获取出错，请重试！");
                    exit();
                }

                if($newuser){
                    Session::delete('', 'openid');
                    Session::set('user', wp_json_encode($newuser));
                }
                if($this->page){
                    if($bind){
                        $user = wp_get_current_user();
                        if($user && $user->ID){
                            $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];
                            update_user_option($user->ID, 'social_type_'.$newuser['type'], $newuser_id);
                            update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                        }else{
                            wp_die("<h3>错误：</h3>请登录后再进行绑定操作！");
                            exit();
                        }
                        Session::set('is_bind', $bind);
                    }
                    wp_redirect($this->page);
                }else{
                    wp_die("<h3>错误：</h3>请设置社交绑定页面（主题设置>社交登录>社交绑定页面）");
                }
                exit;
            }
        }
    }

    function body_class( $classes ){
        $options = $GLOBALS['wpmx_options'];
        $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
        if( $page_id && is_page($page_id) ){
            $classes[] = 'wpcom-member member-social';
        }
        return $classes;
    }

    function unset_session(){
        $options = $GLOBALS['wpmx_options'];
        $page_id = isset($options['social_login_page']) ? $options['social_login_page'] : '';
        if(is_page($page_id) && Session::get('is_bind')){
            Session::delete('', 'access_token');
            Session::delete('', 'user');
            Session::delete('', 'is_bind');
        }
    }

    function qq_login() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->social['qq']['id'],
            'state' => md5(uniqid(wp_rand(), true)),
            'scope' => 'get_user_info',
            'redirect_uri' => $this->redirect_uri
        );
        wp_redirect('https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params));
        exit();
    }

    function weibo_login() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->social['weibo']['id'],
            'redirect_uri' => $this->redirect_uri
        );
        wp_redirect('https://api.weibo.com/oauth2/authorize?'.http_build_query($params));
        exit();
    }

    function wechat_login() {
        global $options;
        $params = array(
            'appid' => $this->social['wechat']['id'],
            'redirect_uri' => apply_filters('wechat_login_redirect_uri', $this->redirect_uri),
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => md5(uniqid(wp_rand(), true))
        );
        if(isset($_GET['from']) && $_GET['from']==='scan'){
            $params['href'] = 'data:text/css;base64,LmltcG93ZXJCb3ggLnFyY29kZSB7d2lkdGg6IDIxNnB4O2JvcmRlcjowO21heC13aWR0aDogMTAwJTttYXJnaW4tdG9wOjA7dmVydGljYWwtYWxpZ246IHRvcDt9Ci5pbXBvd2VyQm94IC50aXRsZSB7ZGlzcGxheTogbm9uZTt9Ci5pbXBvd2VyQm94IC5pbmZvIHt3aWR0aDogMjE2cHg7bWF4LXdpZHRoOiAxMDAlO2JhY2tncm91bmQ6I2ZmZjt9Ci5zdGF0dXNfaWNvbiB7ZGlzcGxheTpub25lO30KLmltcG93ZXJCb3ggLnN0YXR1cyB7dGV4dC1hbGlnbjogY2VudGVyO21hcmdpbi10b3A6IC0xMHB4O30KLmltcG93ZXJCb3ggLmljb24zOF9tc2d7ZGlzcGxheTogbm9uZTt9';
        }else if($options && isset($options['dark_style']) && $options['dark_style']){
            if($options['dark_style'] == '1'){
                $params['href'] = 'data:text/css;base64,LmltcG93ZXJCb3ggLnRpdGxlIHtjb2xvcjojZmZmO30KLmltcG93ZXJCb3ggLnN0YXR1c3tjb2xvcjojZmZmO30=';
            }else if($options['dark_style'] == '2'){
                $params['href'] = 'data:text/css;base64,QG1lZGlhIChwcmVmZXJzLWNvbG9yLXNjaGVtZTogZGFyaykgewoJLmltcG93ZXJCb3ggLnRpdGxlIHtjb2xvcjojZmZmO30KCS5pbXBvd2VyQm94IC5zdGF0dXN7Y29sb3I6I2ZmZjt9Cn0=';
            }
        }
        wp_redirect('https://open.weixin.qq.com/connect/qrconnect?'.http_build_query($params).'#wechat_redirect');
        exit();
    }

    function wechat2_login() {
        if( isset($_GET['uuid']) ){
            if(!isset($_GET['click'])){
                $url = add_query_arg( array( 'uuid' => sanitize_key(wp_unslash($_GET['uuid'])), 'click' => 1 ), wpcom_social_login_url('wechat2') );
                echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width"><title>微信登录</title></head><body><p style="font-size: 17px;line-height: 26px;color:#444;text-align: center;padding: 100px 20px 0;">当前页面<b style="color:#222;font-weight:500;">申请使用你的账号信息（昵称、头像）</b>，如果同意请点击以下按钮继续或者直接退出当前页面。</p><div class="btn-wrap" style="margin-top: 30px;padding: 0 20px;text-align:center;"><a class="wpcom-btn btn-login" style="display: block;
                margin-bottom: 0;
                text-align: center;
                vertical-align: middle;
                touch-action: manipulation;
                color: #fff;
                background: #44b549;
                border: 1px solid #44b549;
                white-space: nowrap;
                padding: 12px 16px;
                font-size: 16px;
                line-height: 18px;
                font-weight:500;
                border-radius: 4px;
                text-decoration: none;
                box-sizing: border-box;" href="'.esc_url($url).'">授权微信登录</a></div></body></html>';
                exit;
            }else{
                $this->redirect_uri = add_query_arg( array( 'uuid' => sanitize_key(wp_unslash($_GET['uuid'])) ), $this->redirect_uri );
            }
        }
        if( isset($_GET['redirect_to']) ){
            $this->redirect_uri = add_query_arg( array( 'redirect_to' => sanitize_url($_GET['redirect_to']) ), $this->redirect_uri );
        }
        $params = array(
            'appid' => $this->social['wechat2']['id'],
            'redirect_uri' => apply_filters('wechat2_login_redirect_uri', $this->redirect_uri),
            'response_type' => 'code',
            'scope' => 'snsapi_userinfo',
            'state' => md5(uniqid(wp_rand(), true))
        );
        wp_redirect('https://open.weixin.qq.com/connect/oauth2/authorize?'.http_build_query($params).'#wechat_redirect');
        exit();
    }

    function weapp_login() {
        exit();
    }

    function google_login() {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->social['google']['id'],
            'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'redirect_uri' => $this->redirect_uri,
            'access_type' => 'offline',
            'state' => md5(uniqid(wp_rand(), true))
        );
        wp_redirect('https://accounts.google.com/o/oauth2/auth?'.http_build_query($params));
        exit();
    }

    function facebook_login() {
        $params = array(
            'response_type' => 'code',
            'auth_type' => 'reauthenticate',
            'client_id' => $this->social['facebook']['id'],
            'redirect_uri' => $this->redirect_uri,
            'state' => md5(uniqid(wp_rand(), true))
        );
        wp_redirect('https://www.facebook.com/v6.0/dialog/oauth?'.http_build_query($params));
        exit();
    }

    function twitter_login() {
        $str = '';
        $params=array(
            'oauth_callback' => add_query_arg( array('code'=>'twitter', 'state'=>md5(uniqid(wp_rand(), true))), $this->redirect_uri ),
            'oauth_consumer_key' => $this->social['twitter']['id'],
            'oauth_nonce' => md5(microtime().wp_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );
        foreach ($params as $key => $val) { $str .= '&'.$key.'='.rawurlencode($val); }
        $base = 'POST&'.rawurlencode('https://api.twitter.com/oauth/request_token').'&'.rawurlencode(trim($str, '&'));
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, $this->social['twitter']['key'].'&', true));
        $str = '';
        foreach ($params as $key => $val) { $str .= ''.$key.'="'.rawurlencode($val).'", '; }
        $header = array('Authorization'=>'OAuth '.trim($str,', '));
        $token = $this->http_request('https://api.twitter.com/oauth/request_token', '', 'POST', $header);
        if(!(isset($token['oauth_token']) && $token['oauth_token'])){
            wp_die(wp_json_encode($token));
            exit();
        }
        Session::set('oauth_token', $token['oauth_token']);
        Session::set('oauth_token_secret', $token['oauth_token_secret']);
        wp_redirect('https://api.twitter.com/oauth/authenticate?force_login=false&oauth_token='.$token['oauth_token']);
        exit();
    }

    function github_login() {
        $params = array(
            'client_id' => $this->social['github']['id'],
            'redirect_uri' => $this->redirect_uri,
            'state' => md5(uniqid(wp_rand(), true))
        );
        wp_redirect('https://github.com/login/oauth/authorize?'.http_build_query($params));
        exit();
    }

    function qq_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->social['qq']['id'],
                'client_secret' => $this->social['qq']['key'],
                'redirect_uri' => $this->redirect_uri
            );
            $str = $this->http_request('https://graph.qq.com/oauth2.0/token?'.http_build_query($params));
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }
        if($access_token){
            Session::set('access_token', $access_token);
            $str = $this->http_request('https://graph.qq.com/oauth2.0/me?access_token=' . $access_token . '&unionid=1');
            preg_match('/callback\((.*)\);/i', $str, $matches);
            $str_r = json_decode(trim($matches[1]), true);
            if(isset($str_r['error'])){
                wp_die("<h3>错误：</h3>".wp_kses_post($str_r['error'])."<h3>错误信息：</h3>".wp_kses_post($str_r['error_description']));
                exit();
            }
            $openid = isset($str_r['openid']) ? $str_r['openid'] : '';
            Session::set('openid', $openid);
            if( isset($str_r['unionid']) ) Session::set('unionid', $str_r['unionid']);
        }else{
            preg_match('/callback\((.*)\);/i', $str, $matches);
            $str_r = json_decode(trim($matches[1]), true);
            if(isset($str_r['error'])){
                wp_die("<h3>错误：</h3>".wp_kses_post($str_r['error'])."<h3>错误信息：</h3>".wp_kses_post($str_r['error_description']));
                exit();
            }else{
                wp_die(wp_kses_post($str));
                exit();
            }
        }
    }

    function weibo_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->social['weibo']['id'],
                'client_secret' => $this->social['weibo']['key'],
                'redirect_uri' => $this->redirect_uri
            );
            $str = $this->http_request('https://api.weibo.com/oauth2/access_token', http_build_query($params), 'POST');
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            if(!$access_token){
                wp_die(wp_json_encode($str));
                exit();
            }
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }
        $openid = isset($str['uid']) ? $str['uid'] : '';
        Session::set('access_token', $access_token);
        Session::set('openid', $openid);
    }

    function wechat_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'appid' => $this->social['wechat']['id'],
                'secret' => $this->social['wechat']['key'],
                'code' => $code,
                'grant_type' => 'authorization_code'
            );
            $str = $this->http_request('https://api.weixin.qq.com/sns/oauth2/access_token', http_build_query($params), 'POST');
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            if(!$access_token){
                wp_die(wp_json_encode($str));
                exit();
            }
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }
        $openid = isset($str['openid']) ? $str['openid'] : '';
        Session::set('access_token', $access_token);
        Session::set('openid', $openid);
        if( isset($str['unionid']) ) Session::set('unionid', $str['unionid']);
    }

    function wechat2_callback($code) {
        if(isset($this->social['wechat2']['follow']) && $this->social['wechat2']['follow'] && (!$this->is_wechat() || isset($this->social['wechat2']['qrcode']))){
            Session::set('access_token', $code);
            Session::set('openid', '');
        }else if($code){
            $str = Session::get('_code_'.$code);
            $str = $str ? json_decode($str, true) : '';
            if($str && isset($str['access_token'])){
                $access_token = $str['access_token'];
            }else{
                $params = array(
                    'appid' => $this->social['wechat2']['id'],
                    'secret' => $this->social['wechat2']['key'],
                    'code' => $code,
                    'grant_type' => 'authorization_code'
                );
                $str = $this->http_request('https://api.weixin.qq.com/sns/oauth2/access_token', http_build_query($params), 'POST');
                $access_token = isset($str['access_token']) ? $str['access_token'] : '';
                if(!$access_token){
                    wp_die(wp_json_encode($str));
                    exit();
                }
                Session::set('_code_'.$code, wp_json_encode($str), 120);
            }
            $openid = isset($str['openid']) ? $str['openid'] : '';
            Session::set('access_token', $access_token);
            Session::set('openid', $openid);
            if( isset($str['unionid']) ) Session::set('unionid', $str['unionid']);
        }
    }

    function weapp_callback($code){
        Session::set('access_token', $code);
        Session::set('openid', '');
    }

    function google_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->social['google']['id'],
                'client_secret' => $this->social['google']['key'],
                'redirect_uri' => $this->redirect_uri
            );
            $str = $this->http_request('https://accounts.google.com/o/oauth2/token', http_build_query($params), 'POST');
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            if(!$access_token){
                wp_die(wp_json_encode($str));
                exit();
            }
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }
        Session::set('access_token', $access_token);
    }

    function facebook_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'code' => $code,
                'client_id' => $this->social['facebook']['id'],
                'client_secret' => $this->social['facebook']['key'],
                'redirect_uri' => $this->redirect_uri
            );
            $str = $this->http_request('https://graph.facebook.com/v6.0/oauth/access_token?'.http_build_query($params));
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            if(!$access_token){
                wp_die(wp_json_encode($str));
                exit();
            }
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }
        Session::set('access_token', $access_token);
    }

    function twitter_callback($code) {
        $str = '';
        $params = array(
            'oauth_consumer_key' => $this->social['twitter']['id'],
            'oauth_nonce' => md5(microtime().wp_rand()),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => Session::get('oauth_token'),
            'oauth_version' => '1.0'
        );
        foreach ($params as $key => $val) { $str .= '&'.$key.'='.rawurlencode($val); }
        $base = 'POST&'.rawurlencode('https://api.twitter.com/oauth/access_token').'&'.rawurlencode(trim($str, '&'));
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, $this->social['twitter']['key'].'&'.Session::get('oauth_token_secret'), true));
        $params['oauth_verifier'] = isset($_GET['oauth_verifier']) ? wp_unslash($_GET['oauth_verifier']) : '';
        Session::delete('', 'oauth_token');
        Session::delete('', 'oauth_token_secret');
        $str = '';
        foreach ($params as $key => $val) { $str .= ''.$key.'="'.rawurlencode($val).'", '; }
        $headers = array('Authorization'=>'OAuth '.trim($str,', '));
        $token = $this->http_request('https://api.twitter.com/oauth/access_token', '', 'POST', $headers);
        if(!(isset($token['oauth_token']) && $token['oauth_token'] && $token['open_id'])){
            wp_die(wp_json_encode($token));
            exit();
        }
        Session::set('access_token', $token['oauth_token']);
        Session::set('openid', $token['open_id']);
        Session::set('nickname', $token['screen_name']);
        $params['oauth_token'] = $token['oauth_token'];
        $str = '';
        unset($params['oauth_signature'], $params['oauth_verifier']);
        foreach ($params as $key => $val) { $str .= '&'.$key.'='.rawurlencode($val); }
        $base = 'GET&'.rawurlencode('https://api.twitter.com/1.1/account/verify_credentials.json').'&'.rawurlencode('include_email=true&'.trim($str, '&'));
        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $base, $this->social['twitter']['key'].'&'.$token['oauth_token_secret'], true));
        $str = '';
        foreach ($params as $key => $val) { $str .= ' '.$key.'="'.rawurlencode($val).'", '; }
        $headers = array('Authorization'=>'OAuth '.trim($str,', '));
        $user = $this->http_request('https://api.twitter.com/1.1/account/verify_credentials.json?include_email=true', '', '', $headers);
        Session::set('avatar', str_replace('_normal', '_200x200', $user['profile_image_url_https']));
        if(isset($user['name']) && $user['name']) Session::set('nickname', $user['name']);
        if(isset($user['email'])) Session::set('email', $user['email']);
    }

    function github_callback($code) {
        $str = Session::get('_code_'.$code);
        $str = $str ? json_decode($str, true) : '';
        if($str && isset($str['access_token'])){
            $access_token = $str['access_token'];
        }else{
            $params = array(
                'code' => $code,
                'client_id' => $this->social['github']['id'],
                'client_secret' => $this->social['github']['key'],
                'redirect_uri' => $this->redirect_uri
            );
            $str = $this->http_request('https://github.com/login/oauth/access_token?'.http_build_query($params));
            $access_token = isset($str['access_token']) ? $str['access_token'] : '';
            if(!$access_token){
                wp_die(wp_json_encode($str));
                exit();
            }
            Session::set('_code_'.$code, wp_json_encode($str), 120);
        }

        Session::set('access_token', $access_token);

        $user = $this->http_request('https://api.github.com/user', '', 'GET', array('accept' => 'application/json', 'Authorization' => 'token '.$access_token));
        if(!isset($user['id'])){
            wp_die(wp_json_encode($user));
            exit();
        }

        Session::set('openid', $user['id']);
        Session::set('nickname', $user['name']);
        Session::set('email', $user['email']);
        Session::set('avatar', $user['avatar_url']);
    }

    function qq_new_user(){
        $client_id = $this->social['qq']['id'];
        $access_token = Session::get('access_token');
        $openid = Session::get('openid');
        $user = $this->http_request('https://graph.qq.com/user/get_user_info?access_token='.$access_token.'&oauth_consumer_key='.$client_id.'&openid='.$openid);
        $name = isset($user['nickname']) ? $user['nickname'] : 'QQ'.time();
        $return = array(
            'nickname' => $name,
            'display_name' => $name,
            'avatar' => $user['figureurl_qq_2'] ?: $user['figureurl_qq_1'],
            'type' => 'qq',
            'openid' => $openid
        );
        $unionid = Session::get('unionid');
        if( $unionid ) $return['unionid'] = $unionid;
        return $return;
    }

    function weibo_new_user(){
        $access_token = Session::get('access_token');
        $openid = Session::get('openid');
        $user = $this->http_request("https://api.weibo.com/2/users/show.json?access_token=".$access_token."&uid=".$openid);
        return array(
            'nickname' => $user['screen_name'],
            'display_name' => $user['screen_name'],
            'user_url' => 'http://weibo.com/'.$user['profile_url'],
            'avatar' => $user['avatar_large'] ?: $user['profile_image_url'],
            'type' => 'weibo',
            'openid' => $openid
        );
    }

    function wechat_new_user(){
        $access_token = Session::get('access_token');
        $openid = Session::get('openid');
        $user = $this->http_request("https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN");
        $return = array(
            'nickname' => $user['nickname'],
            'display_name' => $user['nickname'],
            'avatar' => $user['headimgurl'],
            'type' => 'wechat',
            'openid' => $openid,
        );
        $unionid = Session::get('unionid');
        if( $unionid ) $return['unionid'] = $unionid;
        return $return;
    }

    function wechat2_new_user(){
        if(isset($this->social['wechat2']['follow']) && $this->social['wechat2']['follow'] && (!$this->is_wechat() || isset($this->social['wechat2']['qrcode']))){
            $uuid = Session::get('access_token');
            $args = Session::get('_'.$uuid);
            $args = json_decode($args);
            if($args){
                $openid = isset($args->FromUserName) ? $args->FromUserName : '';
                if(isset($this->social['wechat2']['qrcode']) && $this->social['wechat2']['keyword']){
                    $req = [];
                }else{
                    $token = wpcom_wxmp_token($this->social['wechat2']['id'], $this->social['wechat2']['key']);
                    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid=' . $openid . '&lang=zh_CN';
                    $req = $this->http_request($url, '', 'get');
                    $req['nickname'] = isset($req['nickname']) && $req['nickname'] !== '' ? $req['nickname'] : '匿名用户';
                }
                $return = array(
                    'nickname' => $req['nickname'] ?? '匿名用户',
                    'display_name' => $req['nickname'] ?? '匿名用户',
                    'avatar' => isset($req['headimgurl']) && $req['headimgurl'] ? $req['headimgurl'] : '',
                    'type' => 'wechat',
                    'openid' => $openid,
                );
                if( isset($req['unionid']) ) $return['unionid'] = $req['unionid'];
                Session::set('openid', $return['openid']);
                if( isset($return['unionid']) ) Session::set('unionid', $return['unionid']);
                return $return;
            }
        }else{
            return $this->wechat_new_user();
        }
    }

    function weapp_new_user(){
        $uuid = Session::get('access_token');
        $args = Session::get('_'.$uuid);
        $args = json_decode($args, true);
        if($args && isset($args['openid']) && $args['openid']){
            Session::set('openid', $args['openid']);
            if( isset($args['unionid']) ) Session::set('unionid', $args['unionid']);
            return $args;
        }
    }

    function google_new_user(){
        $access_token = Session::get('access_token');
        $user = $this->http_request('https://www.googleapis.com/oauth2/v3/userinfo?access_token='.$access_token);
        if(!isset($user['sub'])){
            wp_die(wp_json_encode($user));
            exit();
        }
        $return = array(
            'user_email' => $user['email'],
            'nickname' => $user['name'],
            'display_name' => $user['name'],
            'avatar' => $user['picture'],
            'type' => 'google',
            'openid' => $user['sub']
        );
        Session::set('openid', $user['sub']);
        return $return;
    }

    function facebook_new_user(){
        $access_token = Session::get('access_token');
        $user = $this->http_request('https://graph.facebook.com/v6.0/me?access_token='.$access_token);
        if(!isset($user['id'])){
            wp_die(wp_json_encode($user));
            exit();
        }
        if(isset($user['picture'])){
            $avatar = $user['picture'];
        }else{
            $user_img = $this->http_request('https://graph.facebook.com/v6.0/me/picture?redirect=false&height=100&type=small&width=100&access_token='.$access_token);
            $avatar = isset($user_img['data']) ? $user_img['data']['url'] : '';
        }
        $return = array(
            'nickname' => $user['name'],
            'display_name' => $user['name'],
            'avatar' => $avatar,
            'type' => 'facebook',
            'openid' => $user['id']
        );
        Session::set('openid', $user['id']);
        if(isset($user['email'])) $return['user_email'] = $user['email'];
        return $return;
    }

    function twitter_new_user(){
        $return = array(
            'type' => 'twitter',
            'nickname' => Session::get('nickname'),
            'display_name' => Session::get('nickname'),
            'avatar' => Session::get('avatar'),
            'openid' => Session::get('openid')
        );
        if(Session::get('email')){
            $return['user_email'] = Session::get('email');
        }
        Session::delete('', 'nickname');
        Session::delete('', 'avatar');
        Session::delete('', 'openid');
        Session::delete('', 'email');
        return $return;
    }

    function github_new_user(){
        $return = array(
            'user_email' => Session::get('email'),
            'nickname' => Session::get('nickname'),
            'display_name' => Session::get('nickname'),
            'avatar' => Session::get('avatar'),
            'type' => 'github',
            'openid' => Session::get('openid')
        );
        Session::delete('', 'nickname');
        Session::delete('', 'avatar');
        Session::delete('', 'openid');
        Session::delete('', 'email');
        return $return;
    }

    function wpcom_social_login(){
        $newuser = Session::get('user');
        $access_token = Session::get('access_token');
        if( !$access_token ){
            return '<p style="text-align: center;text-indent: 0;margin: 0;">社交绑定页面仅用于第三方账号登录后账号的绑定，如果直接访问则显示此提醒，请忽略。</p>';
        }else if( !$newuser && $access_token ){
            return '<p style="text-align: center;text-indent: 0;margin: 0;">'.__('Parameter error', WPMX_TD).'</p>';
        }else if( !get_option('users_can_register') ){ // 未开启注册功能
            return '<p style="text-align: center;text-indent: 0;margin: 0;">' . __('User registration is currently not allowed.', WPMX_TD) . '</p>';
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        $args = array(
            'avatar' => $newuser['avatar'] ?: get_avatar_url('a@wpcom.cn'),
            'is_bind' => Session::get('is_bind'),
            'newuser' => $newuser,
            'social' => $this->social
        );
        $html = $GLOBALS['wpcom_member']->load_template('social-login-connect', $args);

        return $html;
    }

    function login_to_bind(){
        check_ajax_referer( 'wpcom_social_login', 'social_login_nonce', false );

        $newuser = Session::get('user');

        if(!$newuser){
            wp_send_json(array('result'=> 3));
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        if( ! (isset($newuser['type']) || $newuser['openid']) ){
            wp_send_json(array('result'=> 3));
        }

        $res = array();

        if(isset($_POST['username'])){
            $username = sanitize_user($_POST['username']);
        }
        if(isset($_POST['password'])){
            $password = $_POST['password'];
        }

        if($username === '' || $password === '') {
            $res['result'] = 1;
        }

        $user = wp_authenticate($username, $password);

        if(is_wp_error( $user )){
            $res['result'] = 2;
        }else{
            $options = $GLOBALS['wpmx_options'];
            $bind_user = $this->is_bind($newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
            if(isset($bind_user->ID) && $bind_user->ID){ // 已绑定用户
                do_action('wpcom_sl_unionid_login', $bind_user->ID, $newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
                if( (is_email($username) && $bind_user->data->user_email === $username) ||
                    (!is_email($username) && $bind_user->data->user_login === $username) ){ // 绑定的就是这个账号
                    $res['result'] = 0;
                    $redirect_to = Session::get('redirect_to') ?: '';
                    if($redirect_to === '' && isset($options['login_redirect']) && $options['login_redirect'] !== ''){
                        $redirect_to = $options['login_redirect'];
                    }else if($redirect_to === ''){
                        $redirect_to = home_url();
                    }
                    Session::delete('', 'redirect_to');
                    $res['redirect'] = $redirect_to;
                    Session::delete('', 'user');
                    update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                    $this->login($user->ID);
                }else{
                    $res['result'] = 4;
                }
            }else{
                $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];
                if($newuser['type'] === 'weapp'){
                    if(isset($newuser['unionid']) && $newuser['unionid']){
                        update_user_option($user->ID, 'social_type_wechat', $newuser_id);
                        update_user_option($user->ID, 'social_type_wechat_name', $newuser['nickname']);
                    }else{
                        $newuser['type'] = 'wxxcx';
                    }
                }
                update_user_option($user->ID, 'social_type_'.$newuser['type'], $newuser_id);
                update_user_option($user->ID, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                $res['result'] = 0;
                $redirect_to = Session::get('redirect_to') ?: '';
                if($redirect_to === '' && isset($options['login_redirect']) && $options['login_redirect'] !== ''){
                    $redirect_to = $options['login_redirect'];
                }else if($redirect_to === ''){
                    $redirect_to = home_url();
                }
                Session::delete('', 'redirect_to');
                $res['redirect'] = $redirect_to;
                Session::delete('', 'user');
                $this->login($user->ID);
                $this->set_avatar($user->ID, $newuser['avatar']);
            }
        }

        wp_send_json($res);
    }

    function create(){
        check_ajax_referer( 'wpcom_social_login2', 'social_login2_nonce', false );

        $newuser = Session::get('user');

        if(!$newuser){
            wp_send_json(array('result'=> 3));
        }else if($newuser && !is_array($newuser)){
            $newuser = json_decode($newuser, true);
        }

        if( ! (isset($newuser['type']) || $newuser['openid']) ){
            wp_send_json(array('result'=> 3));
        }

        $res = array();

        $newuser_id = isset($newuser['unionid']) && $newuser['unionid'] ? $newuser['unionid'] : $newuser['openid'];

        if(isset($_POST['email']) && !empty($_POST['email'])){
            $email = sanitize_email(wp_unslash($_POST['email']));
        }else if(isset($newuser['user_email']) && is_email($newuser['user_email'])){
            $email = $newuser['user_email'];
        }else{
            $email = $newuser_id . '@email.empty';
        }

        if($email=='') $res['result'] = 1;

        if (!get_option('users_can_register')) { // 未开启注册
            $res['result'] = 7;
            $res['msg'] = __('User registration is currently not allowed.', WPMX_TD);
        }else if(is_email($email)){
            $bind_user = $this->is_bind($newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
            if(isset($bind_user->ID) && $bind_user->ID){ // 已绑定用户
                do_action('wpcom_sl_unionid_login', $bind_user->ID, $newuser['type'], $newuser['openid'], isset($newuser['unionid'])?$newuser['unionid']:'');
                $res['result'] = 4;
            }else{
                $user = get_user_by( 'email', $email );
                if($user && isset($user->ID) && $user->ID){ // 用户已存在
                    $res['result'] = 5;
                }else{
                    $options = $GLOBALS['wpmx_options'];
                    $res['result'] = 0;
                    $redirect_to = Session::get('redirect_to') ?: '';
                    if($redirect_to === '' && isset($options['login_redirect']) && $options['login_redirect'] !== ''){
                        $redirect_to = $options['login_redirect'];
                    }else if($redirect_to === ''){
                        $redirect_to = home_url();
                    }
                    Session::delete('', 'redirect_to');
                    $res['redirect'] = $redirect_to;

                    $userdata = array(
                        'user_pass' => wp_generate_password(),
                        'user_login' => strtoupper($newuser['type']).$newuser['openid'],
                        'user_email' => $email,
                        'nickname' => $newuser['nickname'],
                        'display_name' => $newuser['display_name']
                    );
                    if($newuser['type']=='weibo') $userdata['user_url'] = $newuser['user_url'];

                    if(!function_exists('wp_insert_user')){
                        include_once( ABSPATH . WPINC . '/registration.php' );
                    }

                    $userdata = apply_filters('wpmx_social_login_pre_insert_user', $userdata);
                    if (is_wp_error( $userdata ) ) {
                        $res['result'] = 6;
                        $res['msg'] = $userdata->get_error_message();
                        wp_send_json($res);
                    }

                    $user_id = wp_insert_user($userdata);

                    if ( ! is_wp_error( $user_id ) ) {
                        $role = get_option('default_role');
                        wp_update_user( array( 'ID' => $user_id, 'role' => $role ?: 'contributor' ) );
                        do_action('register_new_user', $user_id);
                        do_action('wpcom_social_new_user', $user_id, $_POST);
                        if($newuser['type'] === 'weapp'){
                            if(isset($newuser['unionid']) && $newuser['unionid']){
                                update_user_option($user_id, 'social_type_wechat', $newuser_id);
                                update_user_option($user->ID, 'social_type_wechat_name', $newuser['nickname']);
                            }else{
                                $newuser['type'] = 'wxxcx';
                            }
                        }
                        update_user_option($user_id, 'social_type_'.$newuser['type'], $newuser_id);
                        update_user_option($user_id, 'social_type_'.$newuser['type'].'_name', $newuser['nickname']);
                        Session::delete('', 'user');
                        $this->login($user_id);
                        $this->set_avatar($user_id, $newuser['avatar']);
                    }else{
                        $res['result'] = 6;
                        $res['msg'] = $user_id->get_error_message();
                    }
                }
            }
        }else{
            $res['result'] = 2;
        }

        wp_send_json($res);
    }

    function http_request($url, $body=array(), $method='GET', $headers=array()){
        $result = wp_remote_request($url, array('method' => $method, 'timeout' => 20, 'sslverify' => false, 'httpversion' => '1.1', 'body'=>$body, 'headers' => $headers));
        if(is_wp_error($result)){
            wp_die(wp_json_encode($result->errors));
            exit;
        }else if( is_array($result) ){
            $json_r = json_decode($result['body'], true);
            if( !$json_r ){
                parse_str($result['body'], $json_r);
                if( count($json_r)==1 && current($json_r)==='' ) return $result['body'];
            }
            return $json_r;
        }
    }

    function is_bind($type, $openid, $unionid = '') {
        global $wpdb;
        if(!$openid) return false;
        if( $type == 'wechat2' ) $type = 'wechat';

        if( ($type=='wechat' || $type=='qq') && $unionid!='' ){
            $args = array(
                'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_' . $type,
                'meta_value'   => $unionid,
            );
            $users = get_users($args);

            // unionid找不到用户，则使用openid
            if( !$users ){
                $args['meta_value'] = $openid;
                $users = get_users($args);
                if( $users ){ // 能找到用户，则更新为unionid
                    $user = $users[0];
                    update_user_option($user->ID, 'social_type_'.$type, $unionid);
                    return $user;
                }
            }
        }else if($type == 'weapp' && $unionid!=''){
            $args = array(
                'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_wechat',
                'meta_value'   => $unionid,
            );
            $users = get_users($args);

            // unionid找不到用户，则使用openid
            if( !$users ){
                $args = array(
                    'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_wxxcx',
                    'meta_value'   => $openid,
                );
                $users = get_users($args);
                if( $users ){ // 能找到用户，则更新为unionid
                    $user = $users[0];
                    update_user_option($user->ID, 'social_type_wechat', $unionid);
                    return $user;
                }
            }
        }else{
            $args = array(
                'meta_key'     => $wpdb->get_blog_prefix() . 'social_type_' . ($type == 'weapp' ? 'wxxcx' : $type),
                'meta_value'   => $openid,
            );

            $users = get_users($args);
        }
        if( $users ){
            return $users[0];
        }
    }

    function wechat2_login_check(){
        $res = array();
        $uuid = isset($_POST['uuid']) ? sanitize_key($_POST['uuid']) : '';
        if( $uuid && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ){
            if(isset($_POST['type']) && $_POST['type'] == 3){
                $wxcode = isset($_POST['code']) ? trim(sanitize_key(wp_unslash($_POST['code']))) : '';
                if($wxcode === ''){
                    $res['result'] = 1;
                    $res['msg'] = '请输入验证码';
                }else{
                    $data_str = Session::get('_wxcode_' . $wxcode);
                    if($data_str && $data = json_decode($data_str)){
                        Session::set('_' . $uuid, $data_str);
                        Session::delete('', '_wxcode_' . $wxcode);
                        $res['result'] = 0;
                        $args = array('type' => 'wechat2', 'action' => 'callback', 'code' => $uuid);
                        if (isset($_SERVER['HTTP_REFERER']) && preg_match('/(bind|account)/i', $_SERVER['HTTP_REFERER'])) {
                            $args['from'] = 'bind';
                        }
                        $res['redirect_to'] = add_query_arg($args, $this->page);
                        if (isset($_GET['redirect_to']) && $redirect_to = sanitize_url($_GET['redirect_to'])) {
                            // 有跳转回前页，保存到session
                            Session::set('redirect_to', $redirect_to);
                        }
                    }else{
                        $res['result'] = 1;
                        $res['msg'] = '登录失败，请核对验证码输入是否正确';
                    }
                }
            }else{
                if( isset($_POST['type']) && $_POST['type'] == 2) $uuid = substr(md5($uuid), 2, 26);
                $code = Session::get('_' . $uuid);
                if ($code) {
                    $type = 'wechat2';
                    if (isset($_POST['type']) && $_POST['type'] == 2) {
                        $type = 'weapp';
                        $code = $uuid;
                    } else if (isset($this->social['wechat2']['follow']) && $this->social['wechat2']['follow']) {
                        $data = json_decode($code);
                        $code = preg_replace('/^(login_|qrscene_login_)/i', '', $data->EventKey);
                    }

                    $res['result'] = 0;
                    $args = array('type' => $type, 'action' => 'callback', 'code' => $code);
                    if (isset($_SERVER['HTTP_REFERER']) && preg_match('/(bind|account)/i', $_SERVER['HTTP_REFERER'])) {
                        $args['from'] = 'bind';
                    }
                    $res['redirect_to'] = add_query_arg($args, $this->page);
                    if (isset($_GET['redirect_to']) && $redirect_to = sanitize_url($_GET['redirect_to'])) {
                        // 有跳转回前页，保存到session
                        Session::set('redirect_to', $redirect_to);
                    }
                } else {
                    $res['result'] = 1;
                }
            }
        }else{
            $res['result'] = 2;
        }
        wp_send_json($res);
    }

    function wechat2_qrcode(){
        $res = array('result' => -1);
        if(isset($this->social['wechat2']['follow']) && $this->social['wechat2']['follow'] && isset($_POST['uuid']) && $_POST['uuid']){
            if(isset($this->social['wechat2']['qrcode'])){
                $res['result'] = 0;
                $res['qrcode'] = $this->social['wechat2']['qrcode'];
                $res['keyword'] = $this->social['wechat2']['keyword'];
            }else{
                $token = wpcom_wxmp_token($this->social['wechat2']['id'], $this->social['wechat2']['key']);
                $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $token;
                $params = array(
                    'action_name' => 'QR_STR_SCENE',
                    'expire_seconds' => 24 * 60 * 60,
                    'action_info' => array(
                        'scene' => array(
                            'scene_str' => 'login_' . sanitize_key(wp_unslash($_POST['uuid']))
                        )
                    )
                );
                $body = wp_json_encode($params, JSON_UNESCAPED_UNICODE);
                $result = $this->http_request($url, $body, 'post');
                if (isset($result['ticket'])) {
                    $res['result'] = 0;
                    $res['url'] = $result['url'];
                }
            }
        }
        wp_send_json($res);
    }

    function weapp_qrcode(){
        $res = array('result' => -1);
        if(function_exists('WWA_weapp_get_access_token') && isset($_POST['uuid']) && $_POST['uuid']){
            $uuid = substr(md5(sanitize_key(wp_unslash($_POST['uuid']))), 2, 26);
            if(wp_is_mobile() && $this->social['weapp']['mobile']){
                $res['url'] = WWA_weapp_urlscheme('pages/login/web', $uuid ? 'uuid='.$uuid : '');
            }

            $qrcode = WWA_weapp_wxacode('pages/login/web', '999901'.$uuid);
            $res['qrcode'] = $qrcode ? 'data:image/jpeg;base64,' . base64_encode($qrcode) : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
            if($res['qrcode']){
                $res['result'] = 0;
            }
        }
        wp_send_json($res);
    }

    function wechat2_notify(){
        if(function_exists('wpcom_setup') || defined('WPCOM_MP_DIR')){
            if(function_exists('wpcom_setup') && defined('FRAMEWORK_PATH')){
                include_once FRAMEWORK_PATH . '/member/wechat/wxBizMsgCrypt.php';
            }else{
                include_once WPCOM_MP_DIR . 'sdk/wechat/wxBizMsgCrypt.php';
            }
            $xml = file_get_contents('php://input');
            $data = $this->FromXml($xml);
            if($this->checkSignature() && $data && isset($this->social['wechat2']['aeskey']) && $this->social['wechat2']['aeskey']) {
                $timeStamp    = isset($_GET['timestamp']) ? sanitize_text_field(wp_unslash($_GET['timestamp'])) : '';
                $nonce        = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
                $msg_sign     = isset($_GET['msg_signature']) ? sanitize_text_field(wp_unslash($_GET['msg_signature'])) : '';
                $pc = new \WXBizMsgCrypt( 'wpcom', $this->social['wechat2']['aeskey'], $this->social['wechat2']['id'] );
                $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
                $from_xml = sprintf($format, $data->Encrypt);
                $msg = '';
                $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
                if ($errCode=='0' && $msg){
                    $res = $this->FromXml($msg);
                    do_action('wpcom_wechat_notify', $res);
                    switch ($res->MsgType){
                        case 'event':
                            $type = strtolower($res->Event);
                            if(($type === 'subscribe' || $type === 'scan') && $res->EventKey && preg_match('/^(login_|qrscene_login_)/i', $res->EventKey)){
                                $uuid = preg_replace('/^(login_|qrscene_login_)/i', '', $res->EventKey);
                                Session::set('_'.$uuid, wp_json_encode($res));
                                if($this->social['wechat2']['welcome']){
                                    echo '<xml><ToUserName><![CDATA['.esc_html($res->FromUserName).']]></ToUserName><FromUserName><![CDATA['.esc_html($res->ToUserName).']]></FromUserName><CreateTime>'.esc_html(time()).'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.wp_kses_post($this->social['wechat2']['welcome']).']]></Content></xml>';
                                    exit;
                                }
                            }
                            break;
                        case 'text':
                            if(isset($res->Content) && isset($this->social['wechat2']) && isset($this->social['wechat2']['keyword']) && (trim($res->Content) === $this->social['wechat2']['keyword'] || (trim($res->Content) === '登陆' && $this->social['wechat2']['keyword'] === '登录')) && $this->social['wechat2']['code']){
                                $content = $this->social['wechat2']['code'];
                                $_code = wpcom_generate_sms_code($res->ToUserName);
                                Session::set('_wxcode_' . $_code, wp_json_encode($res), 600);
                                if (preg_match('%CODE%', $content)) $content = str_replace('%CODE%', $_code, $content);
                                if (preg_match('%TIME%', $content)) $content = str_replace('%TIME%', 10, $content);
                                echo '<xml><ToUserName><![CDATA[' . esc_html($res->FromUserName) . ']]></ToUserName><FromUserName><![CDATA[' . esc_html($res->ToUserName) . ']]></FromUserName><CreateTime>' . esc_html(time()) . '</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[' . wp_kses_post($content) . ']]></Content></xml>';
                                exit;
                            }
                            break;
                    }
                }
                echo 'success';
            }else if($this->checkSignature() && isset($_GET['echostr'])){
                echo wp_kses_post($_GET['echostr']);
            }else{
                echo 'fail';
            }
        }
        exit;
    }

    private function checkSignature(){
        $signature = isset($_GET['signature']) ? sanitize_text_field(wp_unslash($_GET['signature'])) : '';
        $timestamp = isset($_GET['timestamp']) ? sanitize_text_field(wp_unslash($_GET['timestamp'])) : '';
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        $token = 'wpcom';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }
        return false;
    }

    private function FromXml($xml){
        if (PHP_VERSION_ID < 80000) libxml_disable_entity_loader(true);
        $values = json_decode(wp_json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)));
        return $values;
    }

    function login($user_id){
        if($user_id){
            $user = get_user_by('ID', $user_id);
            if($user && isset($user->ID) && $user->ID){
                wp_set_auth_cookie($user->ID);
                do_action( 'wp_login', $user->user_login, $user );
                wp_set_current_user($user->ID);
            }
        }
    }

    function set_avatar($user, $img){
        if(!$user || !$img) return false;

        // 判断是否已经上传头像
        $avatar = get_user_meta( $user, 'wpcom_avatar', 1);
        if ( $avatar != '' ){ //已经设置头像
            return false;
        }

        //Fetch and Store the Image
        $http_options = array(
            'timeout' => 20,
            'redirection' => 20,
            'sslverify' => FALSE
        );

        $get = wp_remote_head( $img, $http_options );
        $response_code = wp_remote_retrieve_response_code ( $get );

        if (200 == $response_code) { // 图片状态需为 200
            $type = $get ['headers'] ['content-type'];

            $mime_to_ext = array(
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/tiff' => 'tif'
            );

            $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

            if( $type == 'application/octet-stream' ){
                $parse_url = wp_parse_url($img);
                $file_ext = pathinfo($parse_url['path'], PATHINFO_EXTENSION);
            }

            $allowed_filetype = array('jpg', 'gif', 'png', 'bmp');

            if (in_array($file_ext, $allowed_filetype)) { // 仅保存图片格式 'jpg','gif','png', 'bmp'
                $http = wp_remote_get($img, $http_options);
                if (!is_wp_error($http) && 200 === $http ['response'] ['code']) { // 请求成功

                    $GLOBALS['image_type'] = 0;

                    $filename = substr(md5($user), 5, 16) . '.' . time() . '.' . $file_ext;
                    $mirror = wp_upload_bits( $filename, '', $http ['body'], '1234/06' );

                    if ( !$mirror['error'] ) {
                        $uploads = wp_upload_dir();
                        update_user_meta($user, 'wpcom_avatar', str_replace($uploads['baseurl'], '', $mirror['url']));
                        // 基于wp_generate_attachment_metadata钩子，兼容云储存插件同步
                        $mirror['file'] = str_replace($uploads['basedir']. '/', '', $mirror['file']);
                        apply_filters ( 'wp_generate_attachment_metadata', $mirror, 0, 'create' );
                        return $mirror;
                    }
                }
            }
        }
    }

    function is_wechat(){
        return isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    }
}