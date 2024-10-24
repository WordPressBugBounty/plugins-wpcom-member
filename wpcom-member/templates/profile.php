<?php defined( 'ABSPATH' ) || exit;
$can_edit = false;
if( get_current_user_id() == $profile->ID || current_user_can( 'edit_users' ) ) $can_edit = true;
$classes = apply_filters('wpcom_profile_head_classes', 'wpcom-profile-head');
?>

<div class="wpcom-profile">
    <div class="<?php echo esc_attr($classes);?>">
        <div class="wpcom-ph-bg"><img src="<?php echo esc_url(wpcom_get_cover_url($profile->ID))?>" alt="<?php echo esc_attr($profile->display_name);?>"></div>
        <div class="wpcom-ph-inner">
            <div class="wpcom-ph-user">
                <div class="wpcom-ph-avatar">
                    <?php echo get_avatar( $profile->ID, 200 );?>
                    <?php if( $can_edit ){ ?><span class="edit-avatar" data-user="<?php echo esc_attr($profile->ID);?>"><?php wpmx_icon('camera');?></span><?php } ?>
                </div>
                <h2 class="wpcom-ph-name"><?php
                    $name = apply_filters('wpcom_user_display_name', $profile->display_name, $profile->ID, 'full');
                    echo wp_kses($name, wpmx_allowed_html());?></h2>
                <?php if($profile->description){ ?><div class="wpcom-ph-desc"><?php echo wp_kses($profile->description, 'user_description');?></div><?php } ?>
                <?php do_action('wpcom_profile_after_description', $profile->ID);?>
            </div>
            <?php if( $can_edit ){ ?>
                <div class="wpcom-profile-action">
                    <span class="wpcom-profile-setcover edit-cover" data-user="<?php echo esc_attr($profile->ID);?>">
                        <?php wpmx_icon('camera');?> <?php esc_html_e('Change cover', WPMX_TD);?>
                    </span>
                    <?php if($can_edit) wp_nonce_field( 'wpcom_cropper', 'wpcom_cropper_nonce', 0 );?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php if($tabs){ ?>
        <ul class="wpcom-profile-nav">
            <?php $default = current($tabs); foreach ( $tabs as $tab ) {
                $tab_url = wpcom_profile_url( $profile, $tab['slug']==$default['slug']?'':$tab['slug'] );
                $tab_html = '<a href="' . $tab_url . '">'.$tab['title'].'</a>'; ?>
                <li<?php echo $tab['slug'] === $subpage ? ' class="active"' : '';?>>
                    <?php echo wp_kses(apply_filters( 'wpcom_profile_tab_url', $tab_html, $tab, $tab_url ), 'post');?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
    <div class="wpcom-profile-main profile-<?php echo esc_attr($subpage);?>">
        <?php do_action( 'wpcom_profile_tabs_' . $subpage );?>
    </div>
</div>