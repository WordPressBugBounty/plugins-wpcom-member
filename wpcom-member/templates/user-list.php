<?php defined( 'ABSPATH' ) || exit; global $wpcom_member; ?>
<ul class="wpcom-user-list user-cols-<?php echo esc_attr($cols);?>">
    <?php foreach ( $users as $user ){ ?>
        <li class="wpcom-user-item">
            <div class="user-item-inner">
                <?php echo $wpcom_member->load_template('user-card', array('user' => $user));?>
            </div>
        </li>
    <?php } ?>
</ul>