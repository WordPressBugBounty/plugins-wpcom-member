<?php
$cover_photo = wpcom_get_cover_url( $user->ID );
$display_name = apply_filters('wpcom_user_display_name', '<span class="display-name">' . $user->display_name . '</span>', $user->ID, 'full');
?>
<div class="user-card-header">
    <div class="user-card-cover">
        <?php echo wpmx_image($cover_photo, $user->display_name);?>
    </div>
    <a class="user-card-avatar" href="<?php echo esc_url(get_author_posts_url( $user->ID ));?>" target="_blank">
        <?php echo get_avatar( $user->ID, 60, '', $user->display_name, ['decoding' => 'async', 'loading' => 'lazy'] );?>
    </a>
    <a class="user-card-name" href="<?php echo esc_url(get_author_posts_url( $user->ID ));?>" target="_blank"><?php echo wp_kses_post($display_name);?></a>
    <?php if(wpmx_description_length() > 0) { ?><p class="user-card-desc"><?php echo wp_kses($user->description, 'user_description');?></p><?php } ?>
</div>
<div class="user-card-stats">
    <?php do_action('wpcom_user_data_stats', $user->ID);?>
</div>
<div class="user-card-action">
    <?php do_action('wpcom_user_card_action', $user->ID);?>
</div>