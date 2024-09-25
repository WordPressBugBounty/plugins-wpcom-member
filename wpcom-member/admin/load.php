<?php defined( 'ABSPATH' ) || exit;

if( !class_exists('\WPCOM\Themer\Plugin\Panel_Free') ) {
    define( 'WPCOM_ADMIN_FREE_VERSION', '2.8.8' );
    require WPCOM_ADMIN_FREE_PATH . 'includes/class-plugin-panel.php';
}