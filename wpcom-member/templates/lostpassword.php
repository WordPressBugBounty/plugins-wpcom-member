<?php
defined( 'ABSPATH' ) || exit;

$steps = array(
    'default' => __('STEP 1', WPMX_TD),
    'send_success' => __('STEP 2', WPMX_TD),
    'reset' => __('STEP 3', WPMX_TD),
    'finished' => __('STEP 4', WPMX_TD)
);
?>

<div class="member-lostpassword">
    <div class="member-lp-head">
        <?php $count = is_array($steps) ? count($steps) : 0; ?>
        <ul class="member-lp-process" style="--progress-count: <?php echo $count;?>;">
            <?php $i = 1; $active = 0; foreach ($steps as $key => $step ) {
                $progress = sprintf("%.3f", (1 - $i / $count) * 100);
                if( $key==$subpage ) {
                    $classes = 'active';
                    $active = 1;
                }else if( $key!=$subpage && $active == 1 ){
                    $classes = '';
                }else{
                    $classes = 'processed active';
                } ?>
                 <li class="<?php echo esc_attr($classes); ?>" style="--circle-progress: <?php echo $progress;?>%;">
                    <div class="process-circle">
                        <span><?php echo esc_html($i); ?></span>
                    </div>
                    <div class="process-title"><?php echo esc_html($step);?></div>
                </li>
            <?php $i++; } ?>
        </ul>
    </div>

    <div class="member-lp-main">
        <?php do_action( 'wpcom_lostpassword_form_' . $subpage );?>
    </div>
</div>
