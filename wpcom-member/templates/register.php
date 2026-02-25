<?php defined( 'ABSPATH' ) || exit;

$options = $GLOBALS['wpmx_options'];
$social_login_on = isset($options['social_login_on']) && $options['social_login_on']=='1' ? 1 : 0;
$classes = apply_filters('wpcom_register_form_classes', 'member-form-wrap member-form-register');
$logo = isset($options['login_logo']) && $options['login_logo'] ? wp_get_attachment_url( $options['login_logo'] ) : '';
?>
<div class="<?php echo esc_attr($classes);?>">
    <div class="member-form-inner">
        <?php if ( !get_option('users_can_register') ) { ?>
        <div class="wpcom-alert alert-warning text-center"><?php esc_html_e('User registration is currently not allowed.', WPMX_TD);?></div>
        <?php } ?>
        <?php if($logo){ ?>
        <div class="member-form-head">
            <a class="member-form-logo" href="<?php bloginfo('url');?>" rel="home">
                <?php echo wpmx_image($logo, get_bloginfo( 'name' ));?>
            </a>
        </div>
        <?php } ?>
        <div class="member-form-title">
            <h3><?php esc_html_e('Sign Up', WPMX_TD);?></h3>
            <span class="member-switch pull-right"><?php esc_html_e('Already have an account?', WPMX_TD);?> <a href="<?php echo esc_url(wp_login_url());?>"><?php echo esc_html_x('Sign in', 'sign', WPMX_TD);?></a></span>
        </div>
        <?php do_action( 'wpcom_register_form' ); ?>
        <?php if( $social_login_on ){ ?>
        <div class="member-form-footer">
            <div class="member-form-social">
                <span><?php esc_html_e('Sign up with', WPMX_TD);?></span>
                <?php do_action( 'wpcom_social_login' );?>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php wpcom_back_home();?>