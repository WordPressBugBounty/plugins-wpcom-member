<?php
namespace WPCOM\Member;

use WP_Error;
use WPCOM\Themer\Session;

defined( 'ABSPATH' ) || exit;

class Member {
    function __construct(){
        global $wp_version;
        $options = $GLOBALS['wpmx_options'];

        add_shortcode( 'wpcom-member', array( $this, 'shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
        add_action( 'wp_ajax_wpcom_cropped_upload', array( $this, 'cropped_upload' ) );
        add_action( 'wpcom_options_updated', array( $this, 'flush_rewrite_rules' ) );
        add_action( 'wpcom-member_panel_form', array( $this, 'flush_rewrite_rules' ) );
        add_action( 'save_post_page', array( $this, 'flush_rewrite_rules' ) );
        add_action( 'wpcom_cron_flush_rewrite_rules', 'flush_rewrite_rules' );
        add_action( 'wp_ajax_wpcom_user_posts', array( $this, 'user_posts' ) );
        add_action( 'wp_ajax_nopriv_wpcom_user_posts', array( $this, 'user_posts' ) );
        add_action( 'wp_ajax_wpcom_user_comments', array( $this, 'user_comments' ) );
        add_action( 'wp_ajax_nopriv_wpcom_user_comments', array( $this, 'user_comments' ) );
        add_action( 'wp_ajax_wpcom_login_modal', array( $this, 'login_modal' ) );
        add_action( 'wp_ajax_nopriv_wpcom_login_modal', array( $this, 'login_modal' ) );
        add_action( 'wp_logout', array( $this, 'after_logout' ) );
        add_action( 'template_redirect', array( $this, 'action_before_echo' ) );
        add_action( 'parse_query', array( $this, 'parse_query' ), 100 );
        add_action( 'wpcom_register_form', array( $this, 'register_form' ) );
        add_action( 'wpcom_login_form', array( $this, 'login_form' ) );
        add_action( 'wpcom_lostpassword_form_default', array( $this, 'lostpassword_form_default' ) );
        add_action( 'wpcom_lostpassword_form_send_success', array( $this, 'lostpassword_form_send_success' ) );
        add_action( 'wpcom_lostpassword_form_reset', array( $this, 'lostpassword_form_reset' ) );
        add_action( 'wpcom_lostpassword_form_finished', array( $this, 'lostpassword_form_finished' ) );
        add_action( 'wpcom_social_login', array( $this, 'social_login' ) );
        add_action( 'wpcom_approve_resend_form', array( $this, 'approve_resend_form' ) );
        add_action( 'user_register', array( $this, 'user_register' ) );
        add_action( 'wpcom_social_new_user', array( $this, 'social_new_user' ) );
        add_action( 'login_form_register', array( $this, 'disable_default_register'), 10 );
        add_action( 'login_head', array( $this, 'login_head' ) );
        add_action( 'wpmx_before_member_account', [ $this, 'add_fill_login_notice']);

        add_filter( 'wpmx_localize_script', array($this, 'localize_script') );
        add_filter( 'upload_dir', array($this, 'upload_dir') );
        add_filter( 'get_avatar_url', array($this, 'get_avatar_url'), 10, 3 );
        add_filter( 'pre_get_avatar', array($this, 'pre_get_avatar'), 10, 3 );
        add_filter( 'rewrite_rules_array', array($this, 'rewrite_rules') );
        add_filter( 'query_vars', array($this, 'query_vars'), 10, 1 );
        add_filter( 'register_url', array($this, 'register_url'), 20 );
        add_filter( 'login_url', array($this, 'login_url'), 20, 2 );
        add_filter( 'logout_url', array($this, 'logout_url'), 20, 2 );
        add_filter( 'lostpassword_url', array($this, 'lostpassword_url'), 20, 2 );
        add_filter( 'author_link', array($this, 'author_link'), 20, 3 );
        add_filter( 'show_admin_bar', array($this, 'show_admin_bar') );
        add_filter( 'wp_title_parts', array($this, 'title_parts'), 15 );
        add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 10, 4 );
        add_filter( 'authenticate', array( $this, 'authenticate' ), 50, 3 );
        add_filter( 'views_users', array( $this, 'views_users' ) );
        add_action( 'pre_get_users', array( $this, 'filter_users' ) );
        add_action( 'pre_user_query', array( $this, 'search_by_phone' ) );
        add_filter( 'bulk_actions-users', array( $this, 'bulk_actions_users' ) );
        add_filter( 'handle_bulk_actions-users', array( $this, 'handle_bulk_actions_users' ), 10, 3 );
        add_filter( 'body_class', array( $this, 'body_class' ), 10);
        add_filter( 'user_contactmethods', array( $this, 'user_contactmethods' ), 10);
        add_filter( 'wp_mail', array( $this, 'wp_mail' ), 10);
        add_filter( 'display_post_states', array($this, 'display_page_type'), 10, 2 );
        add_filter( 'manage_users_columns', array( $this, 'users_columns' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'users_column_value' ), 10, 3 );
        add_filter( 'manage_users_sortable_columns', array( $this ,'user_registered_sortable') );
        add_filter( 'users_pre_query', array( $this, 'users_pre_query' ), 10, 2);
        add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'sitemaps_args') );
        add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
        // 小黑屋功能需要 WP 5.8.0+
        add_filter( 'wp_pre_insert_user_data', array( $this, 'pre_insert_user_data'), 10, version_compare($wp_version,'5.8','>=') ? 4 : 3);
        add_filter( 'send_email_change_email', '__return_false' );
        add_filter( 'get_canonical_url', [$this, 'profile_canonical_url'] );

        add_filter( 'pre_comment_approved', [$this, 'comment_fill_login_check'], 20, 2 );
        add_filter( 'qapress_pre_insert_comment', [$this, 'qa_comment_fill_login_check'], 20 );
        add_filter( 'wp_insert_post_data', array( $this, 'pre_insert_post'), 10 ,2 );
        add_filter( 'rest_pre_insert_post', array( $this, 'rest_pre_insert_post') );
        add_filter( 'wpcom_tougao_notice', array( $this, 'tougao_notice'), 10, 2 );
        add_action( 'admin_notices', array($this, 'post_fill_login_error') );
        add_filter( 'qapress_pre_insert_question', [$this, 'qa_post_fill_login_check'], 20 );

        $account_tabs = wpcom_account_default_tabs();
        foreach ($account_tabs as $tab){
            add_action( 'wpcom_account_tabs_' . $tab['slug'], [ $this, 'account_tabs_' . $tab['slug'] ] );
        }

        $profile_tabs = wpcom_profile_default_tabs();
        foreach ($profile_tabs as $tab){
            add_action( 'wpcom_profile_tabs_' . $tab['slug'], [ $this, 'profile_tabs_' . $tab['slug'] ] );
        }

        if( isset($options['social_login_on']) && $options['social_login_on']=='1' ) {
            require_once WPMX_DIR . 'includes/social-login.php';
            new Social_Login();
        }

        $show_profile = apply_filters( 'wpcom_member_show_profile' , true );
        if( $show_profile ) {
            add_action( 'admin_init', array( $this, 'block_access_wpadmin' ) );
            add_filter( 'get_user_metadata', array($this, 'user_description'), 10, 4 );

            add_action('save_post_post', array($this, 'posts_count'), 10, 2);
            add_action('save_post_qa_post', array($this, 'qa_posts_count'), 10, 2);
            add_action('transition_comment_status', array($this, 'comments_count_status'), 10, 3);
            add_action('wp_insert_comment', array($this, 'comments_count'), 10, 2);
            add_action('wpcom_user_data_stats', array($this, 'user_data_stats'), 10, 2);
            add_action('wpcom_profile_after_description', array($this, 'add_stats'), 5 );

            add_filter( 'wpcom_posts_count', array($this, 'get_posts_count'), 5, 2);
            add_filter( 'wpcom_comments_count', array($this, 'get_comments_count'), 5, 2);
            if(defined('QAPress_VERSION')) {
                add_filter('wpcom_questions_count', array($this, 'get_questions_count'), 5, 2);
                add_filter('wpcom_answers_count', array($this, 'get_answers_count'), 5, 2);
            }
        }
    }

    function flush_rewrite_rules(){
        $args = [];
        $args[] = mt_rand(1000, 99999) . '_' . time();
        wp_schedule_single_event( time() + 3, 'wpcom_cron_flush_rewrite_rules', $args );
    }

    function rewrite_rules( $rules ) {
        global $permalink_structure;
        $options = $GLOBALS['wpmx_options'];
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        $new_rules = [];
        $pre = preg_match( '/^\/index\.php\//i', $permalink_structure) ? 'index.php/' : '';

        if( isset($options['member_page_account']) && $options['member_page_account'] ) {
            $page_uri = get_page_uri( $options['member_page_account'] );
            $new_rules[ $pre . $page_uri . '/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&subpage=$matches[1]&pageid=$matches[2]';
            $new_rules[ $pre . $page_uri . '/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&subpage=$matches[1]';
        }

        if( isset($options['member_page_profile']) && $options['member_page_profile'] ){
            $page_uri = get_page_uri( $options['member_page_profile'] );
            $new_rules[ $pre . $page_uri . '/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&user=$matches[1]&subpage=$matches[2]';
            $new_rules[ $pre . $page_uri . '/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&user=$matches[1]';
        }

        return array_merge($new_rules, $rules);
    }

    function query_vars($public_query_vars) {
        $public_query_vars[] = 'subpage';
        $public_query_vars[] = 'user';
        $public_query_vars[] = 'pageid';
        return $public_query_vars;
    }

    function upload_dir( $array ){
        if( isset($array['subdir']) && ( $array['subdir'] == '/1234/06' || isset($GLOBALS['image_type']) ) ){
            $type = $GLOBALS['image_type'] ? 'covers' : 'avatars';
            $array['subdir'] = '/member/' . $type;
            $array['path'] = $array['basedir'] . '/member/' . $type;
            $array['url'] = $array['baseurl'] . '/member/' . $type;
        }
        return $array;
    }

    function get_avatar_url( $url, $id_or_email, $args ){
        global $pagenow;
        $options = $GLOBALS['wpmx_options'];
        if( $pagenow == 'options-discussion.php' ) return $url;

        $user_id = 0;
        if ( is_numeric( $id_or_email ) ) {
            $user_id = absint( $id_or_email );
        } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
        } elseif ( $id_or_email instanceof \WP_User ) {
            $user_id = $id_or_email->ID;
        } elseif ( $id_or_email instanceof \WP_Post ) {
            $user_id = $id_or_email->post_author;
        } elseif ( $id_or_email instanceof \WP_Comment ) {
            $user_id = $id_or_email->user_id;
            if( !$user_id ){
                $user = get_user_by( 'email', $id_or_email->comment_author_email );
                if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
            }
        }

        if ( $user_id && $avatar = get_user_meta( $user_id, 'wpcom_avatar', 1) ) {
            if(preg_match('/^(http|https|\/\/)/i', $avatar)){
                $url = $avatar;
            }else{
                $uploads = wp_upload_dir();
                $url = $uploads['baseurl'] . $avatar;
            }
        }else if( isset($options['member_avatar']) && $options['member_avatar'] ){
            $url = is_numeric($options['member_avatar']) ? wp_get_attachment_url( $options['member_avatar'] ) : esc_url($options['member_avatar']);
        }

        $url = $url ? preg_replace('/^(http|https):/i', '', $url) : $url;
        return $url;
    }

    function pre_get_avatar( $avatar, $id_or_email, $args ){
        $url = $this->get_avatar_url( $avatar, $id_or_email, $args );
        if($url){
            if($args['alt']=='' && is_numeric( $id_or_email )){
                $user = get_user_by( 'ID', absint( $id_or_email ) );
                $args['alt'] = $user ? $user->display_name : '';
            }
            $class = [ 'avatar', 'avatar-' . (int) $args['size'], 'photo' ];
            if ( $args['class'] ) {
                if ( is_array( $args['class'] ) ) {
                    $class = array_merge( $class, $args['class'] );
                } else {
                    $class[] = $args['class'];
                }
            }
            $avatar = sprintf(
                    "<img alt='%s' src='%s' class='%s' height='%d' width='%d' %s/>",
                    /* translators: %s: display_name */
                    esc_attr( sprintf(__('%s\'s avatar', WPMX_TD), $args['alt'] )),
                    esc_url( $url ),
                    esc_attr( join( ' ', $class ) ),
                    (int) $args['height'],
                    (int) $args['width'],
                    $args['extra_attr']
            );
        }
        return $avatar;
    }

    function enqueue_scripts(){
        $profile = isset($GLOBALS['profile']) ? $GLOBALS['profile'] : null;
        if( is_wpcom_member_page( 'account' ) ||
            ( is_wpcom_member_page('profile') && ( get_current_user_id() == $profile->ID || current_user_can( 'edit_users' ) ) )
        ){
            wp_enqueue_style( 'crop', WPMX_URI . 'css/cropper.min.css', [], WPMX_VERSION );
            wp_enqueue_script( 'crop', WPMX_URI . 'js/cropper.min.js', [ 'jquery' ], WPMX_VERSION, true );
            wp_enqueue_script( 'login', WPMX_URI . 'js/login.js', [ 'jquery' ], WPMX_VERSION, true );
        }else if( is_wpcom_member_page( 'login' ) || is_wpcom_member_page( 'register' ) || is_wpcom_member_page('lostpassword' ) ){
            wp_enqueue_script( 'login', WPMX_URI . 'js/login.js', [ 'jquery' ], WPMX_VERSION, true );
        }
    }

    function localize_script( $scripts ){
        $options = $GLOBALS['wpmx_options'];
        $captcha = wpcom_member_captcha_type();
        if( $captcha == 'noCaptcha' && isset($options['nc_appkey']) && $options['nc_appkey']!='' && $options['nc_access_id']!=''  && $options['nc_access_secret']!='' ) {
            $nc_scene = 'nc_login';
            if( is_wpcom_member_page('register' ) ) $nc_scene = 'nc_register';

            $nc_scene = apply_filters( 'wpcom_no_captcha_type', $nc_scene );

            if( wp_is_mobile() ){
                $nc_scene = $nc_scene . '_h5';
            }
            $lang = get_locale();
            $lang_nc = ['ja_JP' => 'ja', 'zh_CN' => 'cn', 'zh_HK' => 'tw', 'zh_TW' => 'tw'];
            if(preg_match('/^en_/i', $lang)) $lang_nc[$lang] = 'en';

            $scripts['noCaptcha'] = [
                'scene' => $nc_scene,
                'appkey' => $options['nc_appkey'],
                'language' => isset($lang_nc[$lang]) ? $lang_nc[$lang] : $lang
            ];
        }else if( $captcha == 'TCaptcha' && isset($options['tc_appkey']) && $options['tc_appkey']!='' && $options['tc_appid']!='' ){
            $scripts['TCaptcha'] = [
                'appid' => $options['tc_appid']
            ];
        }else if( $captcha == 'hCaptcha' && isset($options['hc_sitekey']) && $options['hc_sitekey']!='' && $options['hc_secret']!='' ){
            $scripts['hCaptcha'] = [
                'sitekey' => $options['hc_sitekey']
            ];
        }else if( $captcha == 'reCAPTCHA' && isset($options['gc_sitekey']) && $options['gc_sitekey']!='' && $options['gc_secret']!='' ){
            $scripts['reCAPTCHA'] = [
                'sitekey' => $options['gc_sitekey']
            ];
        }else if ($captcha == '_Captcha') {
            $scripts['_Captcha'] = [
                'title' => __('Security Verification', WPMX_TD),
                'barText' => __('Drag to complete the jigsaw', WPMX_TD),
                'loadingText' => __('Jigsaw is loading', WPMX_TD),
                'failedText' => __('Please try again', WPMX_TD),
            ];
        }else if ($captcha ==='aliCaptcha' && isset($options['alic_sceneId']) && $options['alic_sceneId'] !='' && $options['alic_prefix'] !='' && $options['alic_access_id'] !='' && $options['alic_access_secret'] != '') {
            $lang = get_locale();
            $lang_nc = ['ja_JP' => 'ja', 'zh_CN' => 'cn', 'zh_HK' => 'tw', 'zh_TW' => 'tw'];
            if (preg_match('/^en_/i', $lang)) $lang_nc[$lang] = 'en';
            $scripts['aliCaptcha'] = [
                'SceneId' => trim($options['alic_sceneId']),
                'prefix' => trim($options['alic_prefix']),
                'language' => isset($lang_nc[$lang]) ? $lang_nc[$lang] : $lang
            ];
        }

        if($captcha && $captcha !== 'noCaptcha'){
            $scripts['captcha_label'] = __("I'm not a robot", WPMX_TD);
            $scripts['captcha_verified'] = __("You are verified", WPMX_TD);
        }

        $scripts['errors'] = apply_filters( 'wpcom_member_errors', [] );

        if( is_wpcom_member_page( 'account' ) || (is_wpcom_member_page('profile') && get_current_user_id()) ){
            $scripts['cropper'] = [
                'title' => __('Select photo', WPMX_TD),
                'desc_0' => __('Select your profile photo', WPMX_TD),
                'desc_1' => __('Select your cover photo', WPMX_TD),
                'btn' => __('Select photo', WPMX_TD),
                'apply' => __('Apply', WPMX_TD),
                'cancel' => __('Cancel', WPMX_TD),
                'alert_size' => __('This image is too large!', WPMX_TD),
                'alert_filetype' => __('Sorry this is not a valid image.', WPMX_TD),
                'err_nonce' => __('Nonce check failed!', WPMX_TD),
                'err_fail' => __('Image upload failed!', WPMX_TD),
                'err_login' => __('You must login first!', WPMX_TD),
                'err_empty' => __('Please select a photo!', WPMX_TD),
                'ajaxerr' => __('Request failed!', WPMX_TD)
            ];
        }

        return $scripts;
    }

    function title_parts( $part ){
        if( is_wpcom_member_page('profile') ){
            global $wp_query;
            $options = $GLOBALS['wpmx_options'];
            $user_slug = isset($wp_query->query['user']) && $wp_query->query['user'] ? $wp_query->query['user'] : '';
            if( !$user_slug ) return $part;

            if( isset($options['member_user_slug']) && $options['member_user_slug']=='2' ) {
                $profile = get_user_by( 'ID', $user_slug );
            } else {
                $profile = get_user_by( 'slug', $user_slug );
            }
            if( $profile ) {
                $tabs = apply_filters( 'wpcom_profile_tabs', [] );
                ksort($tabs);
                $default = current($tabs);
                $subpage = isset($wp_query->query_vars['subpage']) ? $wp_query->query_vars['subpage'] : $default['slug'];
                $display_name = $profile->display_name;
                foreach ($tabs as $tab){
                    if($tab['slug'] === $subpage){
                        /* translators: %1$s: display_name %2$s: current tab title */
                        $display_name = sprintf(__('%1$s’s %2$s', WPMX_TD), $display_name, $tab['title']);
                        break;
                    }
                }
                $part[] = $display_name;
            }
        }else if( is_wpcom_member_page('account') ){
            global $wp_query;
            $tabs = apply_filters( 'wpcom_account_tabs', [] );
            ksort($tabs);
            $default = current($tabs);
            $subpage = isset($wp_query->query_vars['subpage']) ? $wp_query->query_vars['subpage'] : $default['slug'];
            foreach ($tabs as $tab){
                if($tab['slug'] === $subpage){
                    $title = $tab['title'];
                    break;
                }
            }
            if(isset($title) && $title) $part[] = $title;
        }
        return $part;
    }

    function shortcode( $atts ){
        if( isset( $atts['type'] ) && $atts['type'] != '' && method_exists( $this, 'shortcode_' . $atts ['type'] ) ){
            $atts = array_map('sanitize_text_field', $atts);
            $show_profile = apply_filters( 'wpcom_member_show_profile' , true );
            $excerpt = ['userlist', 'profile'];
            if(!$show_profile && in_array($atts ['type'], $excerpt)) {
                return _e('Error: Unsupported shortcode type', 'WPMX_TD');
            }else{
                return $this->{'shortcode_'.$atts ['type']}( $atts );
            }
        }
    }

    function shortcode_account(){
        global $wp_query;
        if( !get_current_user_id() ) return false;

        if( isset($wp_query->query_vars['subpage']) && $wp_query->query_vars['subpage'] != '' ) {
            $subpage = $wp_query->query_vars['subpage'];
        }else{
            $subpage = 'general';
        }

        $tabs = apply_filters( 'wpcom_account_tabs', [] );
        ksort($tabs);

        $atts = [
            'subpage' => $subpage,
            'user' => wp_get_current_user(),
            'tabs' => $tabs
        ];

        $atts['args'] = apply_filters( 'wpcom_account_args', [] );
        return $this->load_template('account', $atts) ;
    }

    function shortcode_lostpassword(){
        global $wp_query;
        $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : 'default';

        $atts = [
            'subpage' => $subpage
        ];
        return $this->load_template('lostpassword', $atts) ;
    }

    function shortcode_profile(){
        if( isset( $GLOBALS['profile'] ) ){
            global $wp_query;
            $tabs = apply_filters( 'wpcom_profile_tabs', [] );
            ksort($tabs);
            $default = current($tabs);
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : $default['slug'];

            $atts = [
                'profile' => $GLOBALS['profile'],
                'subpage' => $subpage,
                'tabs' => $tabs
            ];

            $tabs_slug = [];
            foreach ( $tabs as $t){
                $tabs_slug[] = $t['slug'];
            }

            if( ! in_array( $subpage, $tabs_slug) ) {
                status_header(404);
            }

            return $this->load_template('profile', $atts) ;
        }
    }

    function profile_canonical_url($url){
        global $wp_query;
        if(is_wpcom_member_page('profile') && isset($GLOBALS['profile']) && $GLOBALS['profile']->ID){
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : '';
            $url = wpcom_profile_url($GLOBALS['profile'], $subpage);
        }
        return $url;
    }

    function shortcode_userlist( $atts ) {
        $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
        $users = null; $user_ids = [];
        $number = isset($atts['per_page']) && $atts['per_page'] ? $atts['per_page'] : 10;
        $offset = ($paged-1) * $number;
        $orderby = isset($atts['orderby']) && $atts['orderby'] ? $atts['orderby'] : 'registered';
        $order = isset($atts['order']) && $atts['order'] ? $atts['order'] : 'DESC';
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : '2';
        if( $cols!='2' && $cols!='3' && $cols!='4' ) $cols = 2;

        $args = ['number' => $number, 'offset' => $offset, 'paged' => $paged, 'orderby' => $orderby, 'order' => $order];

        // 只显示审核通过的用户
        $args['user_status'] = 0;

        if( isset($atts['group']) && $atts['group'] ) {
            $user_ids = get_objects_in_term( explode(',', $atts['group']), 'user-groups' );
        }else if( isset($atts['users']) && $atts['users'] ){
            $user_ids = explode(',', $atts['users']);
        }

        if( $user_ids && !is_wp_error($user_ids) ) $args['include'] = $user_ids;

        $users_query = new \WP_User_Query( $args );
        $users = $users_query->get_results();

        ob_start();
        if( !$users || is_wp_error($users) ){
            global $wp_query;
            echo '<p style="text-align: center;">' . esc_html__( 'No user found.', WPMX_TD ) . '</p>';
            $wp_query->set_404();
            status_header(404);
        }else{
            $atts['users'] = $users;
            $atts['cols'] = $cols;
            echo $this->load_template( 'user-list', $atts ) ;
            $pagi_args = [ 'paged'=> $paged, 'numpages' => ceil($users_query->total_users / $number) ];
            wpcom_pagination( 4, $pagi_args );
        }
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function account_tabs_general(){
        $metas = apply_filters('wpcom_account_tabs_general_metas', [] );
        ksort($metas);
        ?>
        <form class="member-account-form" action="" method="post">
            <?php wp_nonce_field( 'member_form_general', 'member_form_general_nonce' ); ?>
            <?php foreach ($metas as $meta){ $this->account_field_item($meta); } ?>

            <div class="member-account-item">
                <label class="member-account-label"></label>
                <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Save Changes', WPMX_TD ); ?></button>
            </div>
        </form>
    <?php }

    function account_tabs_bind(){
        $user = wp_get_current_user();
        $action = isset($_GET['action']) && $_GET['action'] ? sanitize_text_field($_GET['action']) : '';
        if ($action=='') {
            $metas = apply_filters('wpcom_account_tabs_bind_metas', []);
            ksort($metas);
            ?>
            <div class="member-account-form">
                <?php wp_nonce_field('member_form_bind', 'member_form_bind_nonce'); ?>
                <?php foreach ($metas as $meta) { $this->account_field_item($meta); } ?>
            </div>
        <?php } else if($action=='bind'){
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
            $metas = $type == 'phone' ? apply_filters('wpcom_sms_code_items', []) : apply_filters('wpcom_email_code_items', []);?>
            <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
            <form id="accountbind-form" class="j-member-form member-account-form" action="" method="post">
                <?php wp_nonce_field( 'member_form_accountbind', 'member_form_accountbind_nonce' ); ?>
                <?php foreach ($metas as $meta){ $this->account_field_item($meta); } ?>
                <div class="member-account-item">
                    <label class="member-account-label"></label>
                    <input type="hidden" name="type" value="<?php echo esc_attr($type);?>">
                    <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Save Changes', WPMX_TD ); ?></button>
                </div>
            </form>
        <?php } else if($action=='change'){
            $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
            $by = isset($_GET['by']) && $_GET['by'] ? sanitize_text_field($_GET['by']) : '';
            $token = isset($_GET['token']) && $_GET['token'] ? sanitize_text_field($_GET['token']) : '';
            $steps = [
                0 => _x('STEP 1', '验证方式', WPMX_TD),
                1 => _x('STEP 2', '安全验证', WPMX_TD),
                2 => _x('STEP 3', '绑定账号', WPMX_TD)
            ];
            $current_step = 0;
            if($by) $current_step = 1;
            if($token) $current_step = 2;
            $count = is_array($steps) ? count($steps) : 0;?>
            <div class="account-bind-process-wrap">
            <ul class="member-lp-process account-bind-process" style="--progress-count: <?php echo $count;?>;">
                <?php $i = 1; $active = 0; foreach ($steps as $key => $step ) {
                    if( $key == $current_step ) {
                        $classes = 'active';
                        $active = 1;
                    }else if( $key != $current_step && $active == 1 ){
                        $classes = '';
                    }else{
                        $classes = 'processed active';
                    }
                    $progress = sprintf("%.3f", (1 - $i / $count) * 100); ?>
                        <li class="<?php echo esc_attr($classes); ?>" style="--circle-progress: <?php echo $progress;?>%;">
                            <div class="process-circle">
                                <span><?php echo esc_html($i); ?></span>
                            </div>
                            <div class="process-title"><?php echo esc_html($step);?></div>
                        </li>
                    <?php $i++; } ?>
            </ul>
            </div>
            <?php if($by){
                $metas = $by == 'phone' ? apply_filters('wpcom_sms_code_items', []) : apply_filters('wpcom_email_code_items', []);
                $metas[10]['value'] = $by == 'phone' ? $user->mobile_phone : $user->user_email;
                $metas[10]['disabled'] = true;?>
                <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
                <form id="accountbind-form" class="j-member-form j-no-phone-form member-account-form" action="" method="post">
                    <?php wp_nonce_field( 'member_form_account_change_bind', 'member_form_account_change_bind_nonce' ); ?>
                    <?php foreach ($metas as $meta){ $this->account_field_item($meta); } ?>
                    <div class="member-account-item">
                        <label class="member-account-label"></label>
                        <input type="hidden" name="type" value="<?php echo esc_attr($by);?>">
                        <input type="hidden" name="change" value="<?php echo esc_attr($type);?>">
                        <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Next', WPMX_TD ); ?></button>
                    </div>
                </form>
            <?php } else if($token){
                $uid = check_password_reset_key( $token, $user->user_login );
                if(is_wp_error($uid)){ ?>
                    <div class="wpcom-errmsg wpcom-alert alert-danger" style="display:block;" role="alert">
                        <i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-warning"></use></svg></i><?php esc_html_e( 'Verification failed', WPMX_TD ); ?>
                        <div class="wpcom-close" data-wpcom-dismiss="alert"><?php wpmx_icon('close');?></div>
                    </div>
                <?php }else{
                    $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
                    $metas = $type == 'phone' ? apply_filters('wpcom_sms_code_items', []) : apply_filters('wpcom_email_code_items', []);?>
                    <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
                    <form id="accountbind-form" class="j-member-form member-account-form" action="" method="post">
                        <?php wp_nonce_field( 'member_form_accountbind', 'member_form_accountbind_nonce' ); ?>
                        <?php foreach ($metas as $meta){ $this->account_field_item($meta); } ?>
                        <div class="member-account-item">
                            <label class="member-account-label"></label>
                            <input type="hidden" name="type" value="<?php echo esc_attr($type);?>">
                            <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Save Changes', WPMX_TD ); ?></button>
                        </div>
                    </form>
                <?php }
            }else{
                if(isset($GLOBALS['validation']['error']) && $GLOBALS['validation']['error']){?>
                    <div class="wpcom-errmsg wpcom-alert alert-danger" style="display:block;" role="alert">
                        <i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-warning"></use></svg></i><?php echo wp_kses_post($GLOBALS['validation']['error']); ?>
                        <div class="wpcom-close" data-wpcom-dismiss="alert"><?php wpmx_icon('close');?></div>
                    </div>
                <?php } ?>
                <form class="member-account-form" action="" method="post">
                    <div class="member-account-item">
                        <label class="member-account-label"><?php esc_html_e( 'Verify by', WPMX_TD ); ?></label>
                        <div class="member-account-input">
                            <select name="by">
                                <?php if(is_wpcom_enable_phone()){ ?><option value="phone"<?php echo (isset($_POST['by']) && $_POST['by'] === 'phone' ? ' selected' : '');?>><?php echo esc_html_x( 'Phone number', 'Verify', WPMX_TD ); ?></option><?php } ?>
                                <option value="email"<?php echo (isset($_POST['by']) && $_POST['by'] === 'email' ? ' selected' : '');?>><?php echo esc_html_x( 'Email address', 'Verify', WPMX_TD ); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="member-account-item">
                        <label class="member-account-label"></label>
                        <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Next', WPMX_TD ); ?></button>
                    </div>
                </form>
            <?php }
        }
    }

    function account_tabs_password(){
        $metas = apply_filters('wpcom_account_tabs_password_metas', [] );
        ksort($metas);
        ?>
        <form class="member-account-form" action="" method="post">
            <?php wp_nonce_field( 'member_form_password', 'member_form_password_nonce' ); ?>
            <?php foreach ($metas as $meta){ $this->account_field_item($meta); } ?>

            <div class="member-account-item">
                <label class="member-account-label"></label>
                <button class="wpcom-btn btn-primary" type="submit"><?php esc_html_e( 'Save Changes', WPMX_TD ); ?></button>
            </div>
        </form>
    <?php }

    function account_field_item( $args ){
        $options = $GLOBALS['wpmx_options'];
        $validation = isset($GLOBALS['validation']) ? $GLOBALS['validation'] : null;

        if( isset($validation['error']) && isset($validation['error']['existing_user_email'])){
            $validation['error']['user_email'] = $validation['error']['existing_user_email'];
        }

        $html = '';
        if( $args && isset($args['type']) ){
            $label = isset($args['label']) ? $args['label'] : '';
            $name = isset($args['name']) ? $args['name'] : '';
            $value = isset($args['value']) ? $args['value'] : '';
            $disabled = isset($args['disabled']) ? $args['disabled'] : false;
            $maxlength = isset($args['maxlength']) ? $args['maxlength'] : '';
            $desc = isset($args['desc']) ? $args['desc'] : '';
            $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
            $validate = isset($args['validate']) ? $args['validate'] : '';

            $error = $validation && isset($validation['error'][$name]) ? $validation['error'][$name] : '';
            $value = $validation && isset($validation['value'][$name]) ? $validation['value'][$name] : $value;

            switch ($args['type']) {
                case 'TCaptcha':
                    if( isset($options['tc_appid']) && $options['tc_appid']!='' && $options['tc_appkey']!='' ) {
                        $html = '<div class="member-account-item TCaptcha"><label class="member-account-label"></label><div class="member-account-input captcha-button j-TCaptcha"><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span><input type="hidden" class="j-Tticket" name="ticket"><input type="hidden" class="j-Trandstr" name="randstr"></div></div>';
                    }
                    break;
                case 'noCaptcha':
                    if( isset($options['nc_appkey']) && $options['nc_appkey']!='' && $options['nc_access_id']!=''  && $options['nc_access_secret']!='' ) {
                        $html = '<div class="member-account-item no-captcha"><label class="member-account-label"></label><div class="nc-container j-NC" id="j-NC-'.rand(1000,9999).'"></div><input type="hidden" class="j-Ncsessionid" name="csessionid"><input type="hidden" class="j-Nsig" name="sig"><input type="hidden" class="j-Ntoken" name="token"><input type="hidden" class="j-Nscene" name="scene"></div>';
                    }
                    break;
                case 'aliCaptcha':
                    if(isset($options['alic_sceneId']) && $options['alic_sceneId'] !='' && $options['alic_prefix'] !='' && $options['alic_access_id'] !='' && $options['alic_access_secret'] != ''){
                        $rand_id = rand(1000, 9999);
                        $html = '<div class="member-account-item aliCaptcha"><label class="member-account-label"></label><div class="member-account-input captcha-button" id="ali-captcha-btn-' . $rand_id . '"><div class="captcha-icon"><i></i></div><span>' . __("I'm not a robot", WPMX_TD) . '</span></div><div id="ali-captcha-' . $rand_id . '">'. wp_nonce_field('aliyun_captcha_verify', 'captcha_verify', true, false) . '</div><input type="hidden" name="verify-key"></div>';
                    }
                    break;
                case 'hCaptcha':
                    if( isset($options['hc_sitekey']) && $options['hc_sitekey']!='' && $options['hc_secret']!='' ) {
                        $html = '<div class="member-account-item hCaptcha"><label class="member-account-label"></label><div class="member-account-input captcha-button"><div class="h-captcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span></div></div>';
                    }
                    break;
                case 'reCAPTCHA':
                    if( isset($options['gc_sitekey']) && $options['gc_sitekey']!='' && $options['gc_secret']!='' ) {
                        $html = '<div class="member-account-item reCAPTCHA"><label class="member-account-label"></label><div class="member-account-input captcha-button"><div class="g-recaptcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span></div></div>';
                    }
                    break;
                case '_Captcha':
                    $html = '<div class="member-account-item _Captcha"><label class="member-account-label"></label><div class="member-account-input captcha-button"><div class="w-captcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span><input type="hidden" class="j-ticket" name="ticket"><input type="hidden" class="j-randstr" name="randstr"></div></div>';
                    break;
                case 'smsCode':
                    $html = '<div class="member-account-item'.($error?' error':'').' sms-code"><label class="member-account-label"></label><div class="member-account-input"><input type="text" class="is-input sms-code-input require" id="'.$name.'" name="'.$name.'" placeholder="'.$placeholder.'"'.($validate?' data-rule="'.$validate.'"':'').' data-label="'.$label.'" autocomplete="off"><div class="wpcom-btn btn-lg send-sms-code j-send-sms-code" data-target="'.(isset($args['target']) ? $args['target'] : '').'">'.__('Get Code', WPMX_TD).'</div>'.wp_nonce_field( 'send_sms_code', 'send_sms_code_nonce', true, false ).'</div></div>';
                    break;
                case 'textarea':
                    $rows = isset($args['rows']) ? $args['rows'] : '';
                    $html = '<div class="member-account-item'.($error?' error':'').'"><label class="member-account-label">'.$label.'</label>';
                    $html .= '<div class="member-account-input"><textarea class="is-input" name="'.$name.'"'.($disabled?' disabled':'') . ($maxlength?' maxlength="'.$maxlength.'"':'') . ($rows?' rows="'.$rows.'"':'') . ' placeholder="'.$placeholder.'">'.esc_attr($value).'</textarea>';
                    if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                    if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div></div>';
                    break;
                case 'password':
                    $html = '<div class="member-account-item'.($error?' error':'').'"><label class="member-account-label">'.$label.'</label>';
                    $html .= '<div class="member-account-input"><input class="is-input" type="password" name="'.$name.'" value="'.esc_attr($value).'"'.($disabled?' disabled':'').' placeholder="'.$placeholder.'">';
                    if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                    if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div></div>';
                    break;
                case 'text':
                case 'default':
                    if($disabled){
                        $html = '<div class="member-account-item member-text-line item-'.$name.($error?' error':'').'"><label class="member-account-label">'.$label.'</label><div class="member-account-input">';
                        $html .= '<div class="member-account-text">'.$value.'</div>';
                    }else {
                        $html = '<div class="member-account-item item-'.$name.($error?' error':'').'"><label class="member-account-label">'.$label.'</label><div class="member-account-input">';
                        $html .= '<input class="is-input" type="text" name="'.$name.'" value="'.esc_attr($value).'"'.($disabled?' disabled':'') . ($maxlength?' maxlength="'.$maxlength.'"':'') . ' placeholder="'.$placeholder.'"'.($validate?' data-rule="'.$validate.'"':'').' data-label="'.$label.'">';
                    }
                    if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                    if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div></div>';
                    break;
            }
            $html = apply_filters('wpcom_account_field_'.$args['type'], $html, $args);
        }
        echo wp_kses($html, wpmx_allowed_html());
    }

    function profile_tabs_posts(){
        global $post, $is_author;
        $is_author = 0;
        $current_user = wp_get_current_user();
        $profile = isset($GLOBALS['profile']) ? $GLOBALS['profile'] : null;
        if( $current_user->ID && $profile->ID == $current_user->ID ) {
            $is_author = 1;
        }

        wp_reset_query();
        $per_page = get_option('posts_per_page');
        $args = [
            'posts_per_page' => $per_page,
            'author' => $profile->ID,
            'post_status' => $is_author ? ['draft', 'pending', 'publish'] : ['publish'],
            'no_found_rows' => true
        ];
        $posts = new \WP_Query($args);
        $class = apply_filters( 'wpcom_profile_tabs_posts_class', 'profile-posts-list clearfix' );
        ?>
        <?php if( $posts->have_posts() ) : ?>
            <ul class="<?php echo esc_attr($class); ?>" data-user="<?php echo esc_attr($profile->ID);?>">
                <?php while( $posts->have_posts() ) : $posts->the_post();?>
                    <?php echo $this->load_template('post', [ 'post' => $post ]);?>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
            <div class="load-more-wrap"><div class="wpcom-btn load-more j-user-posts"><?php esc_html_e( 'Load more posts', WPMX_TD );?></div></div>
        <?php else : ?>
            <div class="profile-no-content">
                <?php echo wpcom_empty_icon('post'); if( get_current_user_id()==$profile->ID ){ esc_html_e( 'You have not created any posts.', WPMX_TD ); }else{ esc_html_e( 'This user has not created any posts.', WPMX_TD ); } ?>
            </div>
        <?php endif; ?>
    <?php }

    function user_posts(){
        global $post, $is_author;
        if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
            $is_author = 0;
            $current_user = wp_get_current_user();
            if( $current_user->ID && $user->ID == $current_user->ID ) {
                $is_author = 1;
            }

            $per_page = get_option('posts_per_page');
            $page = sanitize_text_field($_POST['page']);
            $page = $page ? $page : 1;
            $arg = [
                'posts_per_page' => $per_page,
                'paged' => $page,
                'author' => $user->ID,
                'post_status' => $is_author ? [ 'draft', 'pending', 'publish' ] : [ 'publish' ],
                'no_found_rows' => true
            ];
            $posts = new \WP_Query($arg);

            if( $posts->have_posts() ) {
                while ($posts->have_posts()) : $posts->the_post();
                    echo $this->load_template('post', ['post' => $post]);
                endwhile;
                wp_reset_postdata();
            }else{
                echo 0;
            }
        }
        exit;
    }

    function profile_tabs_comments(){
        $profile = isset($GLOBALS['profile']) ? $GLOBALS['profile'] : null;
        $is_user = get_current_user_id() == $profile->ID;
        $number = 10;

        $args = [
            'number' => $number,
            'user_id' => $profile->ID,
            'status' => $is_user ? 'all':'approve',
            'offset' => 0,
            'no_found_rows' => true
        ];

        $comments_query = new \WP_Comment_Query;
        $comments = $comments_query->query($args);
        ?>
        <?php if( $comments ) : ?>
            <ul class="profile-comments-list clearfix" data-user="<?php echo esc_attr($profile->ID);?>">
                <?php foreach($comments as $comment) : ?>
                    <?php echo $this->load_template('comment', [ 'comment' => $comment ]);?>
                <?php endforeach; ?>
            </ul>
            <?php if(count($comments) >= $number){ ?><div class="load-more-wrap"><div class="wpcom-btn load-more j-user-comments"><?php esc_html_e( 'Load more comments', WPMX_TD );?></div></div><?php } ?>
        <?php else : ?>
            <div class="profile-no-content">
                <?php echo wpcom_empty_icon('comment'); if( get_current_user_id()==$profile->ID ){ esc_html_e( 'You have not made any comments.', WPMX_TD ); }else{ esc_html_e( 'This user has not made any comments.', WPMX_TD ); } ?>
            </div>
        <?php endif; ?>
    <?php }

    function user_comments(){
        if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
            $is_user = get_current_user_id() == $user->ID;
            $number = 10;
            $page = sanitize_text_field($_POST['page']);
            $page = $page ?: 1;
            $args = [
                'number' => $number,
                'user_id' => $user->ID,
                'status' => $is_user ? 'all':'approve',
                'offset' => ($page-1) * $number,
                'no_found_rows' => true
            ];

            $comments_query = new \WP_Comment_Query;
            $comments = $comments_query->query($args);

            if( $comments ) {
                foreach($comments as $comment) :
                    echo $this->load_template('comment', [ 'comment' => $comment ]);
                endforeach;
            }else{
                echo 0;
            }
        }
        exit;
    }

    function after_logout(){
        wp_redirect( home_url() );
    }

    function register_form(){
        $options = $GLOBALS['wpmx_options'];
        $items = apply_filters( 'wpcom_register_form_items', [] );
        ksort($items);
        $terms = isset($options['member_page_terms']) && $options['member_page_terms'] ? $options['member_page_terms'] : '';
        if($terms){
            $terms = get_post($terms);
            if(isset($terms->ID)){
                $terms = sprintf('<a href="%s" target="_blank">%s</a>', get_permalink($terms->ID), get_the_title($terms->ID));
            }
        }
        $privacy = get_privacy_policy_url();
        if($privacy){
            $policy_page_id     = (int) get_option( 'wp_page_for_privacy_policy' );
            $page_title         = ( $policy_page_id ) ? get_the_title( $policy_page_id ) : '';
            $privacy = sprintf('<a href="%s" target="_blank">%s</a>', $privacy, $page_title);
        } ?>
        <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
        <form id="register-form" class="member-form j-member-form" method="post">
            <div class="member-form-items">
                <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
            </div>
            <?php wp_nonce_field( 'member_form_register', 'member_form_register_nonce' ); ?>
            <?php if($terms || $privacy){ ?>
            <div class="member-remember form-group checkbox">
                <label>
                    <input type="checkbox" id="privacy" name="privacy" class="is-input" value="true" data-rule="terms">
                    <?php
                    if($privacy && $terms){
                        /* translators: %1$s: Terms of service, %2$s: Privacy policy */
                        echo sprintf(esc_html__('I have read and agree to the %1$s and %2$s', WPMX_TD), $terms, $privacy);
                    }else{
                        /* translators: %s: Terms of service, Or privacy policy */
                        echo sprintf(esc_html__('I have read and agree to the %s', WPMX_TD), $terms?:$privacy);
                    } ?>
                </label>
            </div>
            <?php } ?>
            <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e('Create an account', WPMX_TD);?></button>
        </form>
    <?php }

    function login_form(){
        $options = $GLOBALS['wpmx_options'];
        $sms_login = is_wpcom_enable_phone() && isset($options['sms_login']) && $options['sms_login'] ? $options['sms_login'] : '0';
        $items = apply_filters( 'wpcom_login_form_items', [] );
        if($sms_login=='1'){
            $items2 = apply_filters( 'wpcom_sms_code_items', [] );
        }else if($sms_login=='2'){
            $items2 = $items;
            $items = apply_filters( 'wpcom_sms_code_items', [] );
        }
        ksort($items);
        if($sms_login) ksort($items2);?>
        <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
        <form id="login-form" class="member-form j-member-form" method="post">
            <div class="member-form-items">
                <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
            </div>
            <?php wp_nonce_field( 'member_form_login', 'member_form_login_nonce' ); ?>
            <div class="member-remember checkbox">
                <label><input type="checkbox" id="remember" name="remember" value="true"><?php esc_html_e('Remember me', WPMX_TD);?></label>
                <a class="member-form-forgot" href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot password?', WPMX_TD);?></a>
            </div>
            <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e('Sign In', WPMX_TD);?></button>
            <?php if($sms_login){ ?>
                <script type="text/template" id="j-tpl-login"><?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?></script>
                <script type="text/template" id="j-tpl-login2"><?php foreach ( $items2 as $item ){ $this->login_field_item( $item ); } ?></script>
            <?php } ?>
        </form>
    <?php }

    function login_modal(){
        $type = sanitize_text_field($_POST['type']);
        $type = $type === 'register' ? 'register' : 'login';
        echo do_shortcode('[wpcom-member type="form" action="'.esc_attr($type).'"]');
        exit;
    }

    function lostpassword_form_default(){
        $items = apply_filters( 'wpcom_lostpassword_form_items', [] );
        ksort($items);?>
        <form id="lostpassword-form" class="member-form lostpassword-form j-member-form" method="post">
            <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
            <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_lostpassword', 'member_form_lostpassword_nonce' ); ?>
            <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e( 'Submit', WPMX_TD );?></button>
        </form>
    <?php }

    function lostpassword_form_send_success(){
        $is_phone = isset($_GET['phone']) && $_GET['phone'] ? 1 : 0;
        if($is_phone){
            $phone = Session::get('lost_password_phone');
            $items = apply_filters( 'wpcom_sms_code_items', [] );
            $items[10]['value'] = $phone;
            $items[10]['disabled'] = true;
            if($phone){ ?>
                <form id="smscode-form" class="member-form lostpassword-form j-member-form j-no-phone-form" method="post">
                    <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
                    <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
                    <?php wp_nonce_field( 'member_form_smscode', 'member_form_smscode_nonce' ); ?>
                    <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e( 'Submit', WPMX_TD );?></button>
                </form>
            <?php } else { ?>
                <div class="member-form lostpassword-form lostpassword-form-status">
                    <div class="status-icon status-icon-warning"><?php wpmx_icon('warning');?></div>
                    <h3 class="lostpassword-failed"><?php esc_html_e( 'Your phone number error!', WPMX_TD); ?></h3>
                    <p><?php esc_html_e( 'Unable to get the phone number, please return to the previous step.', WPMX_TD); ?></p>
                </div>
            <?php }
        } else { ?>
            <div class="member-form lostpassword-form lostpassword-form-status">
                <div class="status-icon status-icon-success"><?php wpmx_icon('success');?></div>
                <h3 class="lostpassword-success"><?php esc_html_e( 'Password reset email send successfully!', WPMX_TD); ?></h3>
                <p><?php esc_html_e( 'Check your email for a link to reset your password. If it doesn’t appear within a few minutes, check your spam folder.', WPMX_TD); ?></p>
            </div>
        <?php }
    }

    function lostpassword_form_reset(){
        if( isset($GLOBALS['reset-error']) && $GLOBALS['reset-error']){ ?>
            <div class="member-form lostpassword-form lostpassword-form-status">
                <div class="status-icon status-icon-warning"><?php wpmx_icon('warning');?></div>
                <h3 class="lostpassword-failed"><?php esc_html_e('Password reset link invalid', WPMX_TD);?></h3>
                <p><?php echo wp_kses($GLOBALS['reset-error'], wpmx_allowed_html()); ?></p>
                <a class="wpcom-btn btn-primary" href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Click here to resend password reset email', WPMX_TD);?></a>
            </div>
        <?php }else{
            $items = apply_filters( 'wpcom_resetpassword_form_items', [] );
            ksort($items);?>
            <form id="resetpassword-form" class="member-form resetpassword-form lostpassword-form j-member-form" method="post">
                <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
                <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
                <?php wp_nonce_field( 'member_form_resetpassword', 'member_form_resetpassword_nonce' ); ?>
                <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e( 'Submit', WPMX_TD );?></button>
            </form>
        <?php }
    }

    function lostpassword_form_finished(){ ?>
        <div class="member-form lostpassword-form lostpassword-form-status">
            <div class="status-icon status-icon-success"><?php wpmx_icon('success');?></div>
            <h3 class="lostpassword-success"><?php esc_html_e('Password reset successfully', WPMX_TD);?></h3>
            <p><?php esc_html_e('Your password has been reset successfully! ', WPMX_TD);?></p>
            <a class="wpcom-btn btn-primary" href="<?php echo esc_url(wp_login_url());?>"><?php esc_html_e(' Click here to return to the login page', WPMX_TD);?></a>
        </div>
    <?php }

    function login_field_item( $args ){
        $options = $GLOBALS['wpmx_options'];
        $html = '';
        if( $args && isset($args['type']) ){
            $label = isset($args['label']) ? $args['label'] : '';
            $name = isset($args['name']) ? $args['name'] : '';
            $icon = isset($args['icon']) ? $args['icon'] : '';
            $icon = $icon ? wpmx_icon($icon, false) : '';
            $require = isset($args['require']) ? $args['require'] : false;
            $maxlength = isset($args['maxlength']) ? $args['maxlength'] : '';
            $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
            $validate = isset($args['validate']) ? $args['validate'] : '';
            $value = isset($args['value']) ? $args['value'] : '';
            $disabled = isset($args['disabled']) ? $args['disabled'] : false;

            switch ($args['type']) {
                case 'TCaptcha':
                    if( isset($options['tc_appid']) && $options['tc_appid']!='' && $options['tc_appkey']!='' ) {
                        $html = '<div class="form-group TCaptcha"><div class="captcha-button j-TCaptcha"><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span></div><input type="hidden" class="j-Tticket" name="ticket"><input type="hidden" class="j-Trandstr" name="randstr"></div>';
                    }
                    break;
                case 'noCaptcha':
                    if( isset($options['nc_appkey']) && $options['nc_appkey']!='' && $options['nc_access_id']!=''  && $options['nc_access_secret']!='' ) {
                        $html = '<div class="form-group"><div class="nc-container j-NC" id="j-NC-'.rand(1000,9999).'"></div></div><input type="hidden" class="j-Ncsessionid" name="csessionid"><input type="hidden" class="j-Nsig" name="sig"><input type="hidden" class="j-Ntoken" name="token"><input type="hidden" class="j-Nscene" name="scene">';
                    }
                    break;
                case 'aliCaptcha':
                    if (isset($options['alic_sceneId']) && $options['alic_sceneId'] != '' && $options['alic_prefix'] != '' && $options['alic_access_id'] != '' && $options['alic_access_secret'] != '') {
                        $rand_id = rand(1000, 9999);
                        $html = '<div class="form-group aliCaptcha"><div class="captcha-button" id="ali-captcha-btn-' . $rand_id . '"><div class="ali-captcha"></div><div class="captcha-icon"><i></i></div><span>' . __("I'm not a robot", WPMX_TD) . '</span></div><div id="ali-captcha-' . $rand_id . '">' . wp_nonce_field('aliyun_captcha_verify', 'captcha_verify', true, false) . '</div><input type="hidden" name="verify-key"></div>';
                    }
                    break;
                case 'hCaptcha':
                    if( isset($options['hc_sitekey']) && $options['hc_sitekey']!='' && $options['hc_secret']!='' ) {
                        $html = '<div class="form-group hCaptcha"><div class="captcha-button"><div class="h-captcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span></div></div>';
                    }
                    break;
                case 'reCAPTCHA':
                    if( isset($options['gc_sitekey']) && $options['gc_sitekey']!='' && $options['gc_secret']!='' ) {
                        $html = '<div class="form-group reCAPTCHA"><div class="captcha-button"><div class="g-recaptcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span></div></div>';
                    }
                    break;
                case '_Captcha':
                    $html = '<div class="form-group _Captcha"><div class="captcha-button"><div class="w-captcha"></div><div class="captcha-icon"><i></i></div><span>'.__("I'm not a robot", WPMX_TD).'</span><input type="hidden" class="j-ticket" name="ticket"><input type="hidden" class="j-randstr" name="randstr"></div></div>';
                    break;
                case 'smsCode':
                    $html = '<div class="form-group sms-code">'.wp_nonce_field( 'send_sms_code', 'send_sms_code_nonce', true, false ).'<label>'.$icon.' <input type="text" class="form-input is-input require" id="'.$name.'" name="'.$name.'" placeholder="'.$placeholder.'"'.($validate?' data-rule="'.$validate.'"':'').' data-label="'.$label.'" autocomplete="off"></label><div class="wpcom-btn btn-lg send-sms-code j-send-sms-code" data-target="'.(isset($args['target']) ? $args['target'] : '').'">'.__('Get Code', WPMX_TD).'</div></div>';
                    break;
                case 'hidden':
                    $html = '<input type="hidden" name="' . $name . '" value="' . $value . '">';
                    break;
                case 'password':
                case 'text':
                case 'default':
                    if($disabled && $value){
                        $input = '<div class="form-input">'.$value.'</div>';
                    } else {
                        $input = '<input type="' . $args['type'] . '" class="form-input is-input' . ($require ? ' require' : '') . '" id="' . $name . '" name="' . $name . '" placeholder="' . $placeholder . '"' . ($maxlength ? ' maxlength="' . $maxlength . '"' : '') . ($validate ? ' data-rule="' . $validate . '"' : '') . ' data-label="' . $label . '">';
                        if($args['type']==='password') $input .= '<span class="show-password">'.wpmx_icon('eye-off-fill', false).'</span>';
                    }
                    $html = '<div class="form-group"><label>'.$icon.' '.$input.'</label></div>';
                    break;
            }
            $html = apply_filters('wpcom_login_field_'.$args['type'], $html, $args);
        }
        echo wp_kses($html, wpmx_allowed_html());
    }

    function shortcode_form( $atts ){
        if( isset($atts['action']) && $atts['action'] ){
            $options = $GLOBALS['wpmx_options'];
            $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';

            if( $atts['action'] == 'register' && $member_reg_active=='1' && isset($_REQUEST['approve']) && $_REQUEST['approve'] ){
                if( $_REQUEST['approve'] =='false' ){
                    $atts['notice'] = isset($options['member_reg_notice']) && $options['member_reg_notice'] ? $options['member_reg_notice']: '';
                } else if($_REQUEST['approve'] =='pending' && isset($_REQUEST['login']) && isset($_REQUEST['key']) ) {
                    $login = wp_unslash( $_REQUEST['login'] );
                    $key = wp_unslash( $_REQUEST['key'] );
                    if( $login && $key ){
                        $user = check_password_reset_key( $key, $login );
                        if( !$user || is_wp_error($user) ) {
                            if ( $user && $user->get_error_code() === 'expired_key' )
                                $error = __( 'Your activation link has expired.', WPMX_TD );
                            else
                                $error = __( 'Your activation link is invalid.', WPMX_TD );

                            $resend_url = add_query_arg( ['approve' => 'resend', 'login' => $login], wp_registration_url() );
                            $atts['notice'] = $error . '<p><a href="'.$resend_url.'">'.__( 'Resend activation email', WPMX_TD ).'</a></p>';
                            $atts['icon'] = 'warning';
                        }
                    }
                } else if($_REQUEST['approve'] === 'true') {
                    $atts['notice'] = __( 'Your account has been activated successfully.', WPMX_TD );
                    $atts['notice'] .= '<p><a href="'.wp_login_url().'">'.__( 'Click here to login', WPMX_TD ).'</a></p>';
                    $atts['icon'] = 'success';
                } else if($_REQUEST['approve'] === 'resend') {
                    return $this->load_template('approve-resend', $atts);
                }
                return $this->load_template('approve-notice', $atts);
            }else if( $atts['action'] == 'register' && $member_reg_active == '2' && isset($_REQUEST['approve']) && $_REQUEST['approve'] == 'false' ){
                $atts['notice'] = isset($options['member_reg_notice']) && $options['member_reg_notice'] ? $options['member_reg_notice']: '';
                return $this->load_template('approve-notice', $atts);
            } else{
                return $this->load_template($atts['action'], $atts);
            }
        }
    }

    function social_login(){
        $options = $GLOBALS['wpmx_options'];
        $socials = apply_filters( 'wpcom_socials', [] );
        ksort($socials);
        if( $socials ){ ?>
            <ul class="member-social-list">
                <?php foreach ( $socials as $social ){ if( $social['id'] && $social['key'] ) { ?>
                <li class="social-item social-<?php echo esc_attr($social['name']);?>">
                    <?php /* translators: %s: social login type */ ?>
                    <a href="<?php echo esc_url(wpcom_social_login_url($social['name']));?>"<?php echo isset($options['social_login_target']) && !$options['social_login_target'] ? '' : ' target="_blank"';?> data-toggle="tooltip" data-placement="top" title="<?php echo esc_attr(sprintf( __('Log in with %s', WPMX_TD), $social['title'] ));?>" aria-label="<?php echo esc_attr($social['title']);?>">
                        <?php wpmx_icon($social['icon']);?>
                    </a>
                </li>
                <?php } } ?>
            </ul>
        <?php }
    }

    function approve_resend_form(){
        $items = apply_filters( 'wpcom_approve_resend_form_items', [] );
        ksort($items);?>
        <div class="wpcom-errmsg wpcom-alert alert-danger j-errmsg"></div>
        <form id="approve_resend-form" class="member-form j-member-form" method="post">
            <?php foreach ( $items as $item ){ $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_approve_resend', 'member_form_approve_resend_nonce' ); ?>
            <button class="wpcom-btn btn-primary btn-block btn-lg" type="submit"><?php esc_html_e( 'Resend activation email', WPMX_TD );?></button>
        </form>
    <?php }

    public static function load_template( $template, $atts = [] ) {
        if (file_exists(get_stylesheet_directory() . '/member/' . $template . '.php')) {
            $file = get_stylesheet_directory() . '/member/' . $template . '.php';;
        }else if(file_exists( get_template_directory() . '/member/' . $template . '.php' )){
            $file = get_template_directory() . '/member/' . $template . '.php';
        }else if(function_exists('wpcom_setup') && file_exists( FRAMEWORK_PATH . '/member/templates/' . $template . '.php' )){
            $file = FRAMEWORK_PATH . '/member/templates/' . $template . '.php';
        }else if(defined('WPCOM_MP_DIR') && file_exists( WPCOM_MP_DIR . 'templates/' . $template . '.php' )){
            $file = WPCOM_MP_DIR . 'templates/' . $template . '.php';
        }else{
            $file = apply_filters('wpcom_member_path', untrailingslashit(WPMX_DIR)) . '/templates/' . $template . '.php';
        }

        $file = apply_filters('wpcom_member_load_template', $file, $template, $atts);

        if ( file_exists( $file ) ) {
            extract($atts, EXTR_SKIP);
            ob_start();
            include $file;
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }

    function cropped_upload(){
        $res = [];
        $res['result'] = '';

        if ( ! check_ajax_referer('wpcom_cropper', 'nonce', false) )
            $res['result'] = -1;

        if( $res['result']=='' ) {
            $user = wp_get_current_user();
            if ($user->ID) {
                $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 0;
                $type = $type ?: 0; // 0: 头像； 1: 封面
                $uid = isset($_POST['user']) ? sanitize_text_field($_POST['user']) : 0;
                $corp_user = $user->ID;
                if ($uid && $uid != $user->ID && current_user_can('edit_users')) {
                    $corp_user = $uid;
                }
                $GLOBALS['image_type'] = $type;

                // 1. 检查文件上传
                if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
                    $res['result'] = -2;
                    wp_send_json($res);
                }

                // 2. 获取裁剪参数
                $x = isset($_POST['x']) ? intval($_POST['x']) : 0;
                $y = isset($_POST['y']) ? intval($_POST['y']) : 0;
                $w = isset($_POST['width']) ? intval($_POST['width']) : 0;
                $h = isset($_POST['height']) ? intval($_POST['height']) : 0;

                // 3. 保存原图到临时目录
                $tmp_file = $_FILES['file']['tmp_name'];
                $mime = $_FILES['file']['type'];
                $allowed_types = ['image/jpg', 'image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mime, $allowed_types)) {
                    $res['result'] = -4;
                    wp_send_json($res);
                }

                // 4. 使用 WP_Image_Editor 裁剪
                $editor = wp_get_image_editor($tmp_file);
                if (is_wp_error($editor)) {
                    $res['result'] = -2;
                    wp_send_json($res);
                }
                $editor->crop($x, $y, $w, $h);
                if ($type == 0 && $w > 300) {
                    $editor->resize(300, 300, true);
                }

                $uploads = wp_upload_dir();
                $filename = substr(md5($corp_user), 5, 16) . '.' . time();
                switch ($mime) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $filename .= '.jpg';
                        break;
                    case 'image/png':
                        $filename .= '.png';
                        break;
                    case 'image/gif':
                        $filename .= '.gif';
                        break;
                }
                $subdir = '/member/' . ($type ? 'covers' : 'avatars');
                $save_path = $uploads['basedir'] . $subdir . '/' . $filename;
                if (!file_exists($uploads['basedir'] . $subdir)) {
                    wp_mkdir_p($uploads['basedir'] . $subdir);
                }
                $saved = $editor->save($save_path);

                if (!is_wp_error($saved) && isset($saved['path']) && file_exists($saved['path'])) {
                    $res['result'] = 1;
                    $res['url'] = $uploads['baseurl'] . $subdir . '/' . $filename;

                    $key = $type ? 'wpcom_cover' : 'wpcom_avatar';
                    $pre_img = get_user_meta($corp_user, $key, 1);
                    if ($pre_img) {
                        $pre_img = str_replace($uploads['baseurl'], '', $pre_img);
                        @unlink($uploads['basedir'] . $pre_img);
                    }
                    update_user_meta($corp_user, $key, str_replace($uploads['baseurl'], '', $res['url']));
                    // 兼容云储存插件同步
                    $mirror = [
                        'file'  => 'member/' . ($type ? 'covers' : 'avatars') . '/' . $filename,
                        'url'   => $uploads['baseurl'] . $subdir . '/' . $filename,
                        'type'  => $mime,
                        'error' => false
                    ];
                    apply_filters('wp_generate_attachment_metadata', $mirror, 0, 'create');
                } else {
                    $res['result'] = -2;
                }
            } else {
                $res['result'] = -3;
            }
        }

        wp_send_json($res);
    }

    function parse_query(){
        global $wp_query;
        if(isset($wp_query->query['subpage']) && $wp_query->query['subpage'] && isset($wp_query->query['page'])){
            $wp_query->set('pageid', $wp_query->query['page']);
            $wp_query->set('page', '');
        }
    }

    function action_before_echo(){
        global $wp_query;
        $options = $GLOBALS['wpmx_options'];
        $user = wp_get_current_user();
        if(isset($user->user_status) && $user->user_status == '1'){ // 黑名单，强制退出登录
            wp_logout();
            exit;
        }
        if ( is_wpcom_member_page( 'account' ) ) {
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : 'general';
            // 登录判断
            if(!$user->ID){
                wp_redirect( wp_login_url( wpcom_subpage_url( $subpage ) ) );
                exit;
            }

            if( $_SERVER['REQUEST_METHOD'] == 'POST' ){ //表单提交
                do_action( 'wpcom_account_' . $subpage . '_post' );
            }else if( $subpage == 'logout' ){
                if(isset($_GET['nonce']) && wp_verify_nonce( sanitize_text_field($_GET['nonce']), 'wpcom_logout' )){
                    wp_logout();
                }else{
                    wp_nonce_ays('log-out');
                }
                exit;
            }
        } else if( is_wpcom_member_page('profile') ){
            $user_slug = isset($wp_query->query['user']) && $wp_query->query['user'] ? $wp_query->query['user'] : '';

            if( $user_slug && isset($options['member_user_slug']) && $options['member_user_slug']=='2' ) {
                $profile = get_user_by( 'ID', $user_slug );
            } elseif( $user_slug ) {
                $profile = get_user_by( 'slug', $user_slug );
            }

            if( !$user_slug && $profile = wp_get_current_user() ){
                if( $profile->ID ){
                    wp_redirect( wpcom_author_url( $profile->ID, $profile->user_nicename ) );
                    exit;
                }
            }

            // 检查用户状态
            $approve = '';
            if(isset($profile->user_status)){
                $approve = $profile->user_status == '0' ? 1 : 0;
            }

            $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';

            // 未通过审核的用户，仅管理员可见
            if($approve && is_multisite() && !is_user_member_of_blog( $profile->ID )){ // 多站点检查是否站点注册用户
                $not_approve = !current_user_can( 'edit_users' );
            }else{
                $not_approve = $approve=='0' && ($member_reg_active!='0' || $profile->user_status=='1') && !current_user_can( 'edit_users' );
            }
            $not_approve = $approve=='0' && ($member_reg_active!='0' || $profile->user_status=='1') && !current_user_can( 'edit_users' );
            if( !$user_slug || !isset($profile) || !$profile || $not_approve ) {
                if(!$user_slug){
                    wp_redirect(wp_login_url(get_permalink($options['member_page_profile'])));
                    exit;
                }
                $wp_query->set_404();
                status_header(404);
            } else {
                $GLOBALS['profile'] = $profile;
            }
        } else if( $user->ID && (is_wpcom_member_page( 'login' ) || is_wpcom_member_page( 'register' ) ) ){
            if( !(isset($_GET['from']) && $_GET['from'] == 'bind') ){ // 绑定
                $redirect = wpcom_subpage_url();
                $redirect = $redirect ?: home_url();
                wp_redirect( $redirect );
                exit;
            }
        } else if( is_wpcom_member_page('login') && ( !isset($options['login_redirect']) || $options['login_redirect']=='') ){
            $redirect_url = isset( $_SERVER['HTTP_REFERER'] ) && $_SERVER['HTTP_REFERER'] ? sanitize_url($_SERVER['HTTP_REFERER']) : '';
            if( !isset($_GET['redirect_to']) && $redirect_url ){
                $pu = parse_url($redirect_url);
                if(isset($pu['query']) && $pu['query']){
                    parse_str( $pu['query'],$data );
                    if( isset($data['redirect_to']) && $data['redirect_to'] ){
                        $redirect_url = sanitize_url($data['redirect_to']);
                    }
                }
                $site_domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
                $red_domain = parse_url($redirect_url, PHP_URL_HOST);
                if( $site_domain == $red_domain ) {
                    // 去除域名，改成路径，防止某些服务器安全配置导致无法识别的问题
                    $redirect_url = preg_replace('/^(http|https):\/\/[^\/]+\//i', '/', $redirect_url);
                    wp_redirect(wp_login_url($redirect_url));
                    exit;
                }
            }
        }else if(is_wpcom_member_page('lostpassword' )){
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : 'default';
            if($subpage === 'reset'){
                $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
                if ( isset( $_GET['key'] ) && isset( $_GET['login'] ) ) {
                    $value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
                    setcookie( $rp_cookie, $value, 0, '/', COOKIE_DOMAIN, is_ssl(), true );
                    wp_safe_redirect( remove_query_arg( [ 'key', 'login' ] ) );
                    exit;
                }

                if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
                    list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
                    $user = check_password_reset_key( $rp_key, $rp_login );
                } else {
                    $user = false;
                }

                if( ! $user || is_wp_error( $user ) ){
                    setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
                    if ( $user && $user->get_error_code() === 'expired_key' )
                        $GLOBALS['reset-error'] = __('Your password reset link has expired', WPMX_TD);//'您的密码重置链接已过期，请重新请求新链接。';
                    else
                        $GLOBALS['reset-error'] = __('Your password reset link appears to be invalid', WPMX_TD);//'您的密码重设链接无效，请重新请求新链接。';
                }
            }
        }else if(is_wpcom_member_page( 'register' ) && isset($_REQUEST['approve']) && $_REQUEST['approve']){
            // 激活成功后跳转
            $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';
            if( $member_reg_active=='1' && $_REQUEST['approve'] =='pending' && isset($_REQUEST['login']) && isset($_REQUEST['key'])){
                $login = wp_unslash( $_REQUEST['login'] );
                $key = wp_unslash( $_REQUEST['key'] );
                if( $login && $key ){
                    $user = check_password_reset_key( $key, $login );
                    if( !$user || is_wp_error($user) ) {
                    }else if( $user->ID ) {
                        wp_update_user( [ 'ID' => $user->ID, 'user_status' => 0 ] );
                        $url = wp_registration_url();
                        $url = add_query_arg( 'approve', 'true', $url );
                        wp_redirect( $url );
                        exit;
                    }else{
                        die('Error');
                    }
                }else{
                    die('Error');
                }
            }
        }
    }

    function register_url( $url ){
        if( $register_url = wpcom_register_url() ){
            $url = $register_url;
        }
        return $url;
    }
    function login_url( $url, $redirect ){
        if( $login_url = wpcom_login_url($redirect) ){
            $url = $login_url;
        }
        return $url;
    }

    function login_head(){
        if(!is_user_logged_in() && isset($_GET['redirect_to']) && $_GET['redirect_to'] && $login_url = wp_login_url(sanitize_url($_GET['redirect_to']))){
            wp_redirect($login_url);
            exit;
        }
    }

    function logout_url( $url, $redirect ){
        if( $logout_url = wpcom_logout_url($redirect) ){
            $url = $logout_url;
        }
        return $url;
    }

    function lostpassword_url( $url, $redirect ){
        if( $lostpassword_url = wpcom_lostpassword_url($redirect) ){
            $url = $lostpassword_url;
        }
        return $url;
    }

    function author_link( $link, $author_id, $author_nicename ){
        if( $author_link = wpcom_author_url( $author_id, $author_nicename ) ){
            $link = $author_link;
        }
        return $link;
    }

    function block_access_wpadmin(){
        global $current_user, $pagenow;
        if(current_user_can('manage_options') || !(class_exists('\WPCOM_User_Groups') || class_exists(User_Groups::class) )) return false;
        $can_access = [ 'admin-ajax.php', 'async-upload.php', 'media-upload.php' ];
        if( in_array($pagenow, $can_access) ) return false;
        if($current_user->ID) {
            $group = wpcom_get_user_group($current_user->ID);
            if($group){
                $wpadmin = (int)get_term_meta($group->term_id, 'wpcom_wpadmin', true);
                if ( $wpadmin !== 1) {
                    wp_redirect(home_url());
                    exit;
                }
            }else{
                wp_redirect(home_url());
                exit;
            }
        }
    }

    function body_class( $classes ){
        if( is_wpcom_member_page('account') ){
            $classes[] = 'wpcom-member';
            $classes[] = 'member-account';
        }else if( is_wpcom_member_page('profile') ){
            $classes[] = 'wpcom-member';
            $classes[] = 'member-profile';
        }else if( is_wpcom_member_page('login') ){
            $classes[] = 'wpcom-member';
            $classes[] = 'member-login';
        }else if( is_wpcom_member_page('register') ){
            $classes[] = 'wpcom-member';
            $classes[] = 'member-register';
        }else if( is_wpcom_member_page('lostpassword') ){
            $classes[] = 'wpcom-member';
            $classes[] = 'member-lostpassword';
        }
        return $classes;
    }

    function show_admin_bar( $show ){
        global $current_user;
        if($current_user->ID) {
            $group = wpcom_get_user_group($current_user->ID);
            if($group) {
                $adminbar = get_term_meta($group->term_id, 'wpcom_adminbar', true);
                if ($adminbar != '1') {
                    $show = false;
                }
            }else if( !current_user_can('edit_published_posts') ){
                $show = false;
            }
        }
        return $show;
    }

    function user_has_cap( $allcaps, $caps, $args, $user ){
        global $pagenow, $current_user, $cap_checked;
        $options = $GLOBALS['wpmx_options'];
        if( !isset($cap_checked) ) $cap_checked = [];
        if( $user->ID && in_array($user->ID, $cap_checked) ) return $allcaps;

        if( (class_exists('\WPCOM_User_Groups') || class_exists(User_Groups::class)) && $user->ID && ( $pagenow=='user-edit.php' || $pagenow=='users.php' || is_wpcom_member_page() ) ) {
            $cap_checked[] = $user->ID;
            // 自己是超级管理员的话，不能取消自己的超级管理员权限
            if( $current_user->ID && $current_user->ID==$user->ID && is_super_admin( $user->ID ) ) return $allcaps;

            $group = wpcom_get_user_group($user->ID);
            if($group) {
                $allcaps = $this->set_default_role($user->ID, $group->term_id);
            }else if( isset($options['member_group']) && $options['member_group'] ){
                // 无用户组则分配默认用户组
                wp_set_object_terms( $user->ID, [ (int)$options['member_group'] ], 'user-groups', false );
            }
        }
        return $allcaps;
    }

    function user_register( $user_id ){
        $options = $GLOBALS['wpmx_options'];
        if( (class_exists('\WPCOM_User_Groups') || class_exists(User_Groups::class)) && isset($options['member_group']) && $options['member_group'] ){
            // 分配默认用户组
            wp_set_object_terms( $user_id, [ (int)$options['member_group'] ], 'user-groups', false );

            // 分配默认系统角色
            $this->set_default_role($user_id);
        }
        $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';
        if( !is_wpcom_enable_phone() && $member_reg_active != '0' ){
            // 注册用户需要验证
            wp_update_user( [ 'ID' => $user_id, 'user_status' => -1 ] );
            if( !Session::get('user') ) { // 非社交登录渠道
                if ($member_reg_active == '1') { // 如果是邮件激活方式，则发送激活邮件给用户
                    wpcom_send_active_email($user_id);
                } else if ($member_reg_active == '2') { // 如果是后台审核，则发送审核邮件给管理员
                    wpcom_send_active_to_admin($user_id);
                }
            }
        }
    }

    private function set_default_role($user_id, $term_id=''){
        $options = $GLOBALS['wpmx_options'];
        if( $term_id || ( (class_exists('\WPCOM_User_Groups') || class_exists(User_Groups::class)) && isset($options['member_group']) && $options['member_group']) ) {
            $term_id = $term_id ? $term_id : $options['member_group'];
            $user = get_user_by('ID', $user_id);
            $sys_role = get_term_meta($term_id, 'wpcom_sys_role', true);
            $default_roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
            $roles = $user->roles;
            if (!$roles) $roles = [];
            if (in_array($sys_role, $default_roles) && !in_array($sys_role, $roles)) { // 权限和当前用户组权限不一样
                foreach ($roles as $role) {
                    if (in_array($role, $default_roles)) {
                        $user->remove_role($role);
                    }
                }
                if (in_array($sys_role, $default_roles)) $user->add_role($sys_role);
            }
            return $user->allcaps;
        }
    }

    function social_new_user( $user_id ){
        $options = $GLOBALS['wpmx_options'];
        $this->set_default_role($user_id);
        $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';
        if( $member_reg_active!='0' ){
            // 注册用户需要验证的情况，对社交登录注册的用户默认验证审核通过
            wp_update_user( [ 'ID' => $user_id, 'user_status' => 0 ] );
        }
    }

    function authenticate( $user, $username, $password ){
        if( $user instanceof \WP_User && $username ){
            $get_user = get_user_by( 'login', $username );
            if ( ! $get_user && strpos( $username, '@' ) ) {
                $get_user = get_user_by( 'email', $username );
            }
            if( $get_user->ID ){
                $options = $GLOBALS['wpmx_options'];
                // 查查旧数据，向下兼容，6.8.0+
                if($get_user->user_status == '0'){
                    $_approve = get_user_meta( $get_user->ID, 'wpcom_approve', true );
                    if($_approve == '0'){ // 未激活用户
                        update_user_meta($get_user->ID, 'wpcom_approve', '');
                        wp_update_user( [ 'ID' => $get_user->ID, 'user_status' => -1 ] );
                        $get_user->user_status = '-1';
                    }
                }

                $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: '0';
                if( $get_user->user_status=='-1' && $member_reg_active!='0' ){
                    $err = '';
                    if($member_reg_active=='1'){
                        $resend_url = add_query_arg( ['approve' => 'resend', 'login' => $username], wp_registration_url() );
                        /* translators: %1$s: resend activation email url, %2$s: close tag </a> */
                        $err = sprintf( esc_html__( 'Please activate your account. %1$s Resend activation email %2$s', WPMX_TD ), '<a href="'.$resend_url.'" target="_blank">', '</a>' );
                    }else if($member_reg_active=='2'){
                        $err = __( 'Account awaiting approval.', WPMX_TD );
                    }
                    if($err) $user = new WP_Error( 'not_approve', $err );
                }else if($get_user->user_status=='1'){ // 黑名单用户
                    $err = __( 'Blacklist user.', WPMX_TD );
                    if($err) $user = new WP_Error( 'not_approve', $err );
                }
            }
        }else if( is_wpcom_enable_phone(true) && preg_match("/^1[3-9]{1}\d{9}$/", $username) ){ // 手机登录
            $args = [
                'meta_key'     => 'mobile_phone',
                'meta_value'   => $username,
            ];
            $users = get_users($args);
            if($users && $users[0]->ID && wp_check_password($password, $users[0]->user_pass, $users[0]->ID)) {
                $user = $users[0];
            }
        }

        return $user;
    }

    // 处理 user_status
    function users_pre_query($res, $that){
        if(isset($that->query_vars['user_status'])){
            $that->query_where .= " AND user_status = '".$that->query_vars['user_status']."'";
        }
        return $res;
    }

    function pre_insert_user_data($data, $update, $id, $userdata = []){
        $options = $GLOBALS['wpmx_options'];
        // 人工审核的方式会专门发送邮件给管理员，无需系统再次发送
        if( isset($options['member_reg_active']) && $options['member_reg_active']=='2' ){
            add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
        }
        if(isset($userdata['user_status'])){
            $data['user_status'] = $userdata['user_status'];
        }
        return $data;
    }

    function views_users( $views ){
        if( !current_user_can( 'edit_users' ) ) return $views;

        $current = '';
        if ( isset($_REQUEST['status']) && $_REQUEST['status'] == 'unapproved' ) $current = 'class="current"';

        $users = get_users(['user_status' => '-1']);
        $count = count($users);
        $views[ 'unapproved' ] = '<a href="'.admin_url('users.php').'?status=unapproved" ' . $current . '>'. __( 'Unapproved', WPMX_TD ) . ' <span class="count">（'.$count.'）</span></a>';

        $current2 = '';
        if ( isset($_REQUEST['status']) && $_REQUEST['status'] == 'blacklist' ) $current2 = 'class="current"';

        $users2 = get_users(['user_status' => '1']);
        $count2 = count($users2);
        $views[ 'blacklist' ] = '<a href="'.admin_url('users.php').'?status=blacklist" ' . $current2 . '>'. __( 'Blacklist', WPMX_TD ) . ' <span class="count">（'.$count2.'）</span></a>';

        return $views;
    }

    function filter_users( $query ){
        global $pagenow;
        if ( is_admin() && 'users.php' == $pagenow ) {
            if(!isset($_GET['orderby'])){
                $query->set('orderby', 'registered');
                $query->set('order', 'desc');
            }
            if(!isset($query->query_vars['user_status']) && isset($_REQUEST['status'])){
                if($_REQUEST['status']=='unapproved') {
                    $query->set('user_status', '-1');
                }else if($_REQUEST['status']=='blacklist') {
                    $query->set('user_status', '1');
                }
            }
        }
    }

    function search_by_phone($query){
        global $wpdb, $pagenow;
        $search_term = trim($query->query_vars['search'], '*');
        if( is_admin() && 'users.php' == $pagenow && $search_term && preg_match("/^\d{4,11}$/", $search_term) ) {
            $query->query_from = 'FROM wp_users INNER JOIN wp_usermeta ON ( wp_users.ID = wp_usermeta.user_id )  INNER JOIN wp_usermeta AS mt1 ON ( wp_users.ID = mt1.user_id )';

            // 正则替换
            $pattern = "/wp_usermeta(\.meta_key\s+=\s+['\"]{$wpdb->prefix}capabilities['\"])/";
            $replacement = "mt1$1";

            // 正则替换
            $pattern2 = "/display_name\s+LIKE\s+('{[^\'\"]+}')/";
            $replacement2 = "display_name LIKE $1 OR ( wp_usermeta.meta_key = 'mobile_phone' AND wp_usermeta.meta_value LIKE $1 )";

            // 执行替换
            $query->query_where = preg_replace($pattern, $replacement, $query->query_where);
            $query->query_where = preg_replace($pattern2, $replacement2, $query->query_where);
        }
    }

    function display_page_type($post_states, $post){
        if($post->post_type === 'page'){
            if(is_wpcom_member_page('login', $post->ID)){
                $type = '登录页';
            }else if(is_wpcom_member_page('register', $post->ID)){
                $type = '注册页';
            }else if(is_wpcom_member_page('lostpassword', $post->ID)){
                $type = '重置密码';
            }else if(is_wpcom_member_page('account', $post->ID)){
                $type = '账号设置页';
            }else if(is_wpcom_member_page('profile', $post->ID)){
                $type = '个人中心页';
            }else if(is_wpcom_member_page('social_login', $post->ID)){
                $type = '社交绑定页';
            }
            if(isset($type) && $type) $post_states['member_page'] = '<i class="wpcom wpcom-logo"></i> ' . $type;
        }
        return $post_states;
    }

    function sitemaps_args($args){
        $options = $GLOBALS['wpmx_options'];
        if($args['post_type'] === 'page'){
            $args['post__not_in'] = isset( $args['post__not_in'] ) ? $args['post__not_in'] : [];
            if(isset($options['member_page_login']) && $options['member_page_login']){
                $args['post__not_in'][] = $options['member_page_login'];
            }
            if(isset($options['member_page_register']) && $options['member_page_register']){
                $args['post__not_in'][] = $options['member_page_register'];
            }
            if(isset($options['member_page_lostpassword']) && $options['member_page_lostpassword']){
                $args['post__not_in'][] = $options['member_page_lostpassword'];
            }
            if(isset($options['member_page_account']) && $options['member_page_account']){
                $args['post__not_in'][] = $options['member_page_account'];
            }
            if(isset($options['member_page_profile']) && $options['member_page_profile']){
                $args['post__not_in'][] = $options['member_page_profile'];
            }
            if(isset($options['social_login_page']) && $options['social_login_page']){
                $args['post__not_in'][] = $options['social_login_page'];
            }
        }
        return $args;
    }

    function user_description($val, $user, $meta_key, $single){
        if($meta_key==='description'){
            $options = $GLOBALS['wpmx_options'];
            $meta_cache = wp_cache_get( $user, 'user_meta' );
            if ( ! $meta_cache ) {
                $meta_cache = update_meta_cache( 'user', [ $user ] );
                if ( isset( $meta_cache[ $user ] ) ) {
                    $meta_cache = $meta_cache[ $user ];
                } else {
                    $meta_cache = null;
                }
            }

            if ( isset( $meta_cache[ $meta_key ] ) ) {
                if ( $single ) {
                    $val = maybe_unserialize( $meta_cache[ $meta_key ][0] );
                } else {
                    $val = array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
                }
            }

            if($val==='' && isset($options['member_desc']) && $options['member_desc']){
                $val = $options['member_desc'];
            }
        }
        return $val;
    }

    function bulk_actions_users( $actions ){
        if( current_user_can( 'edit_users' ) ) {
            $actions['approve'] = __('Approve', WPMX_TD);
            $actions['disapprove'] = __('Disapprove', WPMX_TD);
            $actions['blacklist'] = __('Add to blacklist', WPMX_TD);
            $actions['remove-blacklist'] = __('Remove from blacklist', WPMX_TD);
        }
        return $actions;
    }

    function handle_bulk_actions_users( $redirect_to, $doaction, $ids ){
        if( !$ids || !current_user_can( 'edit_users' ) ) return $redirect_to;
        $options = $GLOBALS['wpmx_options'];
        if( $doaction=='approve' ){
            foreach ( $ids as $id ){
                $_user = get_user_by( 'ID', $id );
                if($_user && isset($_user->ID) && $_user->ID){
                    wp_update_user( [ 'ID' => $id, 'user_status' => 0 ] );
                    // 管理员审核方式则发送邮件通知
                    if( isset($options['member_reg_active']) && $options['member_reg_active']=='2' && $_user->user_status != '0'){
                        // 检查之前的状态，有变化则通知
                        wpcom_send_actived_email( $id );
                    }
                }
            }
        }else if( $doaction === 'disapprove' ){
            foreach ( $ids as $id ){
                wp_update_user( [ 'ID' => $id, 'user_status' => -1 ] );
            }
        }else if( $doaction === 'blacklist' ){
            foreach ( $ids as $id ){
                wp_update_user( [ 'ID' => $id, 'user_status' => 1 ] );
            }
        }else if( $doaction === 'remove-blacklist' ){
            foreach ( $ids as $id ){
                wp_update_user( [ 'ID' => $id, 'user_status' => 0 ] );
            }
        }
        return $redirect_to;
    }

    function disable_default_register(){
        $url = wpcom_register_url();
        if($url){
            wp_redirect( $url );
            exit;
        }
    }

    function users_columns( $columns ) {
        $columns['registered'] = __('Registered', WPMX_TD);
        $_columns = [];
        foreach ($columns as $key => $column){
            switch ($key) {
                case 'username':
                    $_columns['user'] = __('User');
                    break;
                case 'name':
                    if(is_wpcom_enable_phone()) {
                        $_columns['phone'] = _x('Phone number', 'label', WPMX_TD);
                    }
                    break;
                case 'email':
                    $_columns['_email'] = __( 'Email' );
                    break;
                default:
                    $_columns[$key] = $column;
                    break;
            }
        }
        return $_columns;
    }

    function users_column_value( $val, $column_name, $user_id ) {
        $user = get_user_by( 'ID', $user_id );
        switch ($column_name) {
            case 'registered' :
                $offset = get_option( 'gmt_offset' );
                $val = date('Y.m.d H:i:s', strtotime( $user->user_registered ) + ($offset * HOUR_IN_SECONDS) );
                break;
            case 'phone' :
                $val = $user->mobile_phone;
                break;
            case '_email' :
                $email = $user->user_email;
                if(wpcom_is_empty_mail($email)) $email = '';
                if($email){
                    $email = "<a href='" . esc_url( "mailto:$email" ) . "'>$email</a>";
                }
                $val = $email;
                break;
            case 'user' :
                $actions = [];
                $super_admin = '';
                if ( is_multisite() && current_user_can( 'manage_network_users' ) ) {
                    if ( in_array( $user->user_login, get_super_admins(), true ) ) {
                        $super_admin = ' &mdash; ' . __( 'Super Admin' );
                    }
                }
                if ( current_user_can( 'list_users' ) ) {
                    // Set up the user editing link
                    $edit_link = sanitize_url( add_query_arg( 'wp_http_referer', urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ), get_edit_user_link( $user->ID ) ) );
                    if($user->user_status == -1){
                        $user->display_name .= sprintf( '<span class="user-badge user-pedding">%s</span>', __( 'Unapproved', WPMX_TD ) );
                    }else if($user->user_status == 1){
                        $user->display_name .= sprintf( '<span class="user-badge user-block">%s</span>', __( 'Blacklist', WPMX_TD ) );
                    }
                    if(defined('WPCOM_MP_VERSION') && version_compare(WPCOM_MP_VERSION, '1.7.0', '>=')){
                        $class = class_exists( VIP::class ) ? VIP::class : \WPCOM_VIP::class;
                        $user->display_name = $class::display_name($user->display_name, $user->ID, 'full');
                    }
                    if ( current_user_can( 'edit_user', $user->ID ) ) {
                        $actions['edit'] = '<a href="' . $edit_link . '">' . __( 'Edit' ) . '</a>';
                        $edit            = "<strong><a href=\"{$edit_link}\">{$user->display_name}</a>{$super_admin}</strong><br />";
                    } else {
                        $edit = "<strong>{$user->display_name}{$super_admin}</strong><br />";
                    }

                    if ( ! is_multisite() && get_current_user_id() != $user->ID && current_user_can( 'delete_user', $user->ID ) ) {
                        $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=delete&amp;user=$user->ID", 'bulk-users' ) . "'>" . __( 'Delete' ) . '</a>';
                    }
                    if ( is_multisite() && get_current_user_id() != $user->ID && current_user_can( 'remove_user', $user->ID ) ) {
                        $actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url( "users.php?action=remove&amp;user=$user->ID", 'bulk-users' ) . "'>" . __( 'Remove' ) . '</a>';
                    }

                    // Add a link to the user's author archive, if not empty.
                    $author_posts_url = get_author_posts_url( $user->ID );
                    if ( $author_posts_url ) {
                        $actions['view'] = sprintf(
                            '<a href="%s" aria-label="%s" target="_blank">%s</a>',
                            esc_url( $author_posts_url ),
                            /* translators: %s: author's display name */
                            esc_attr( sprintf( __( 'View posts by %s' ), $user->display_name ) ),
                            __( 'View' )
                        );
                    }
                    $actions = apply_filters( 'user_row_actions', $actions, $user );
                } else {
                    $edit = "<strong>{$user->display_name}{$super_admin}</strong>";
                }
                $avatar = get_avatar( $user->ID, 32 );
                $val = "$avatar $edit";

                $action_count = count( $actions );
                $i            = 0;

                if ( ! $action_count ) {
                    return '';
                }

                $out = '<div class="row-actions">';
                foreach ( $actions as $action => $link ) {
                    ++$i;
                    ( $i == $action_count ) ? $sep = '' : $sep = ' | ';
                    $out                          .= "<span class='$action'>$link$sep</span>";
                }
                $out .= '</div>';

                $out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';

                $val .= $out;
                break;
            default :
                break;
        }
        return $val;
    }

    function user_registered_sortable( $columns ){
        $columns['registered'] = 'registered';
        return $columns;
    }

    function user_contactmethods($user_contact){
        if(is_wpcom_enable_phone()) {
            $user_contact['mobile_phone'] = __('Mobile Phone', WPMX_TD);
        }
        return $user_contact;
    }

    function wp_mail($atts){
        // 邮件发送过滤系统填错邮箱，即未设置邮箱的用户
        if ( isset( $atts['to'] ) ) {
            if(is_array($atts['to'])){
                foreach ($atts['to'] as $k => $to){
                    if(wpcom_is_empty_mail($to)){
                        unset($atts['to'][$k]);
                    }
                }
            }else if(wpcom_is_empty_mail($atts['to'])){
                $atts['to'] = '';
            }
        }
        return $atts;
    }

    function get_posts_count($count, $user){
        if($count==='') $count = $this->update_post_count($user);
        return $count ?: 0;
    }

    function get_comments_count($count, $user){
        if($count==='') $count = $this->update_comment_count($user);
        return $count ?: 0;
    }

    function get_questions_count($count, $user){
        if($count==='') $count = $this->update_question_count($user);
        return $count ?: 0;
    }

    function get_answers_count($count, $user){
        if($count==='') $count = $this->update_answer_count($user);
        return $count ?: 0;
    }

    function posts_count($postid, $post){
        if($postid) update_user_option($post->post_author, 'posts_count', '');
    }

    function qa_posts_count($postid, $post){
        if($postid) update_user_option($post->post_author, 'questions_count', '');
    }

    function comments_count($comment_ID, $comment){
        if($comment_ID && $comment->user_id) {
            if($comment->comment_type==='' || $comment->comment_type==='comment'){
                $this->update_comment_count($comment->user_id);
            }else if($comment->comment_type==='answer'){
                $this->update_answer_count($comment->user_id);
            }
        }
    }

    function comments_count_status($new_status, $old_status, $comment){
        if($comment->user_id) {
            if($comment->comment_type==='' || $comment->comment_type==='comment'){
                $this->update_comment_count($comment->user_id);
            }else if($comment->comment_type==='answer'){
                $this->update_answer_count($comment->user_id);
            }
        }
    }

    function update_post_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_author = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'posts_count', $count);
            return $count;
        }
    }

    function update_comment_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->comments} WHERE (comment_type = '' OR comment_type = 'comment') AND comment_approved = 1 AND user_id = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'comments_count', $count);
            return $count;
        }
    }

    function update_question_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'qa_post' AND post_status = 'publish' AND post_author = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'questions_count', $count);
            return $count;
        }
    }

    function update_answer_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->comments} WHERE comment_type = 'answer' AND comment_approved = 1 AND user_id = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'answers_count', $count);
            return $count;
        }
    }

    function user_data_stats($user, $link=true){
        global $wpdb;
        $options = $GLOBALS['wpmx_options'];
        $user = isset($user->ID) ? $user : get_user_by('ID', $user);
        $posts = apply_filters('wpcom_posts_count', $user->{$wpdb->get_blog_prefix() . 'posts_count'}, $user->ID);
        if ($posts >= 1000) $posts = sprintf("%.1f", $posts / 1000) . 'K';
        $comments = apply_filters('wpcom_comments_count', $user->{$wpdb->get_blog_prefix() . 'comments_count'}, $user->ID);
        if ($comments >= 1000) $comments = sprintf("%.1f", $comments / 1000) . 'K';
        $posts_url = wpcom_profile_url( $user, 'posts' );
        $comments_url = wpcom_profile_url( $user, 'comments' );
        $this->user_stats_item($posts, _x('posts', 'stats', WPMX_TD), $link ? $posts_url : '');
        if(wpmx_comment_status()) $this->user_stats_item($comments, _x('comments', 'stats', WPMX_TD), $link ? $comments_url : '');

        if(defined('QAPress_VERSION')){
            $questions = apply_filters('wpcom_questions_count', $user->{$wpdb->get_blog_prefix() . 'questions_count'}, $user->ID);
            if ($questions >= 1000) $questions = sprintf("%.1f", $questions / 1000) . 'K';
            $answers = apply_filters('wpcom_answers_count', $user->{$wpdb->get_blog_prefix() . 'answers_count'}, $user->ID);
            if ($answers >= 1000) $answers = sprintf("%.1f", $answers / 1000) . 'K';
            $questions_url = wpcom_profile_url( $user, 'questions' );
            if($questions) $this->user_stats_item($questions, _x('questions', 'stats', WPMX_TD), $link ? $questions_url : '');
            if($answers) $this->user_stats_item($answers, _x('answers', 'stats', WPMX_TD), $link ? $questions_url : '');
        }

        if((class_exists('\WPCOM_Follow') || class_exists(Follow::class)) && isset($options['member_follow']) && $options['member_follow']=='1'){
            $followers = apply_filters('wpcom_followers_count', $user->{$wpdb->get_blog_prefix() . 'followers_count'}, $user->ID);
            if ($followers >= 1000) $followers = sprintf("%.1f", $followers / 1000) . 'K';
            $followers_url = wpcom_profile_url( $user, 'follows' );
            $this->user_stats_item($followers, _x('followers', 'stats', WPMX_TD), $link ? $followers_url : '');
        }
    }

    function user_stats_item($num, $title, $link){
        if($link){?>
            <a class="user-stats-item" href="<?php echo esc_url($link);?>" target="_blank">
                <b><?php echo wp_kses_post($num);?></b>
                <span><?php echo wp_kses_post($title);?></span>
            </a>
        <?php } else { ?>
            <div class="user-stats-item">
                <b><?php echo wp_kses_post($num);?></b>
                <span><?php echo wp_kses_post($title);?></span>
            </div>
        <?php }
    }

    function add_stats($user){ ?>
        <div class="profile-stats">
            <div class="profile-stats-inner">
                <?php do_action('wpcom_user_data_stats', $user, false);?>
            </div>
        </div>
    <?php }

    function comment_fill_login_check($approved, $comment){
        if(!is_wp_error($approved) && isset($comment['user_id']) && $comment['user_id'] && wpcom_need_fill_login($comment['user_id'])){
            return new WP_Error('need_fill_login', $this->fill_login_check_msg(), 400);
        }
        return $approved;
    }

    function qa_comment_fill_login_check($comment){
        if(!is_wp_error($comment) && isset($comment['user_id']) && $comment['user_id'] && wpcom_need_fill_login($comment['user_id'])){
            $comment = new WP_Error('need_fill_login', $this->fill_login_check_msg(), 400);
        }
        return $comment;
    }

    function pre_insert_post($data, $attr, $rest = 0){
        $user_id = isset($data['post_author']) && $data['post_author'] ? (int)$data['post_author'] : get_current_user_id();
        $needed_types = apply_filters('wpmx_need_fill_login_post_types', ['post', 'page', 'kuaixun', 'qa_post']);
        if($user_id && $data['post_type'] && in_array($data['post_type'], $needed_types) && wpcom_need_fill_login($user_id)){
            $data['post_status'] = 'inherit';
            if($rest){
                $err = new WP_Error( 'need_fill_login', $this->fill_login_check_msg(false), 400);
                return $err;
            }else{
                add_filter('redirect_post_location', [ $this, 'redirect_post_location_filter' ], 88);
            }
        }
        return $data;
    }

    function rest_pre_insert_post($post){
        $_post = json_decode(json_encode($post), true);
        $res = $this->pre_insert_post($_post, $_post, 1);
        if(is_wp_error($res)){
            return $res;
        }else{
            return $post;
        }
    }

    function redirect_post_location_filter($location){
        remove_filter('redirect_post_location', __FUNCTION__, 88);
        $location = add_query_arg('message', 7799, $location);
        return $location;
    }

    function post_fill_login_error(){
        if(isset( $_GET['message'] ) && isset( $_GET['post'] ) && $_GET['message'] == '7799'){
            $post_notice = $this->fill_login_check_msg(); ?>
            <div class="notice error is-dismissible" >
                <?php echo wp_kses_post(wpautop($post_notice));?>
            </div>
        <?php }
    }

    function tougao_notice($notice, $post){
        global $post_notice;
        if(!isset($post_notice) && $post->post_status === 'inherit'){
            $post_notice = $this->fill_login_check_msg();
        }
        if($post_notice && $post && isset($post->post_status) && ($post->post_status === 'draft' || $post->post_status === 'inherit')){
            $notice = '<div class="wpcom-alert alert-warning alert-dismissible fade in" role="alert">';
            $notice .= '<div class="wpcom-close" data-wpcom-dismiss="alert" aria-label="Close">' . wpmx_icon('close', 0) . '</div>';
            $notice .= $post_notice;
            $notice .= '</div>';
        }
        return $notice;
    }

    function qa_post_fill_login_check($post){
        if(!is_wp_error($post) && isset($post['post_author']) && $post['post_author'] && wpcom_need_fill_login($post['post_author'])){
            $post = new WP_Error('need_fill_login', $this->fill_login_check_msg(), 400);
        }
        return $post;
    }

    function add_fill_login_notice(){
        $user_id = get_current_user_id();
        if($user_id && wpcom_need_fill_login($user_id)){
            $bind_link = '<a href="' . wpcom_subpage_url('bind') . '">' . __('click here to complete your profile', WPMX_TD) . '</a>';
            $msg = sprintf(__('Your %1$s is missing. Please %2$s.', WPMX_TD), is_wpcom_enable_phone() ? _x('Phone number', 'label', defined('WPCMP_TD') ? WPCMP_TD : 'wpcom') : _x('Email address', 'label', WPMX_TD), $bind_link);
            echo '<div class="wpcom-alert alert-warning text-center" role="alert">' . $msg . '</div>';
        }
    }

    private function fill_login_check_msg($has_url = true){
        $bind_link = sprintf($has_url ? __('click here to update your %s', WPMX_TD) : __('update your %s', WPMX_TD), is_wpcom_enable_phone() ? _x('Phone number', 'label', defined('WPCMP_TD') ? WPCMP_TD : 'wpcom') : _x('Email address', 'label', WPMX_TD));
        if($has_url){
            $bind_link = '<a href="' . wpcom_subpage_url('bind') . '" target="_blank">' . $bind_link . '</a>';
        }
        $msg = sprintf( __('Action failed. Please %s before proceeding with this action.', WPMX_TD), $bind_link);
        return apply_filters('wpmx_fill_login_check_msg', $msg);
    }
}

class_alias(Member::class, 'WPCOM_Member');