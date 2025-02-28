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
        <ul class="member-lp-process">
            <?php $i = 1; $active = 0; foreach ($steps as $key => $step ) {
                if( $key==$subpage ) {
                    $classes = 'active';
                    $active = 1;
                }else if( $key!=$subpage && $active == 1 ){
                    $classes = '';
                }else{
                    $classes = 'processed active';
                }
                if($key=='finished'){ ?>
                    <li class="last <?php echo esc_attr($classes); ?>">
                        <i><?php echo esc_html($i); ?></i>
                        <p><?php echo esc_html($step);?></p>
                    </li>
                <?php } else{ ?>
                    <li class="<?php echo esc_attr($classes); ?>">
                        <div class="process-index">
                            <i><?php echo esc_html($i); ?></i>
                            <p><?php echo esc_html($step);?></p>
                        </div>
                        <div class="process-line"></div>
                    </li>
                <?php } ?>
                <?php $i++; } ?>
        </ul>
    </div>

    <div class="member-lp-main">
        <?php do_action( 'wpcom_lostpassword_form_' . $subpage );?>
    </div>
</div>
