<?php
defined( 'ABSPATH' ) || exit;
$classes = 'status-icon status-icon-';
$classes .= (isset($icon) && $icon === 'warning') ? 'warning' : 'success';
?>
<div class="member-form-wrap member-reg-notice">
    <div class="<?php echo esc_attr($classes);?>"><?php wpmx_icon(isset($icon) ? $icon : 'clock');?></div>
    <?php if(isset($notice)) echo wp_kses(wpautop($notice), wpmx_allowed_html()); ?>
</div>