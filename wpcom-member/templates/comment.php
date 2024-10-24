<?php defined( 'ABSPATH' ) || exit;?>
<li class="comment-item">
    <div class="comment-item-meta">
        <?php
        $time = strtotime($comment->comment_date_gmt);
        $t = time() - $time;
        $f = array( '86400', '3600', '60', '1' );
        $human_time = '';
        if($t==0) {
            $human_time = __('1 second ago', WPMX_TD);
        } else if( $t >= 604800 || $t < 0) {
            $human_time = date(get_option('date_format'), strtotime($comment->comment_date));
        } else {
            foreach ( $f as $k ) {
                if ( 0 != $c=floor($t/(int)$k) ) {
                    $is_human_time = true;
                    break;
                }
            }

            if(isset($is_human_time) && $is_human_time) {
                $strs = array(
                    /* translators: %s: days */
                    '86400' => sprintf(_n('%s day ago', '%s days ago', $c, WPMX_TD), $c),
                    /* translators: %s: hours */
                    '3600' => sprintf(_n('%s hour ago', '%s hours ago', $c, WPMX_TD), $c),
                    /* translators: %s: minutes */
                    '60' => sprintf(_n('%s minute ago', '%s minutes ago', $c, WPMX_TD), $c),
                    /* translators: %s: seconds */
                    '1' => sprintf(_n('%s second ago', '%s seconds ago', $c, WPMX_TD), $c)
                );
                $human_time = $strs[$k];
            }
        }
        /* translators: %1$s: post url html tag, %2$s: post title, %3$s: close post url html tag */ ?>
        <span class="comment-item-time"><?php wpmx_icon('comments-fill'); echo esc_html($human_time);?></span> <span><?php printf(esc_html__('On %1$s %2$s %3$s', WPMX_TD), '<a target="_blank" href="'.esc_url(get_permalink($comment->comment_post_ID)).'">', esc_attr(get_the_title($comment->comment_post_ID)), '</a>' ); ?></span>
    </div>
    <div class="comment-item-link">
        <a target="_blank" href="<?php echo esc_url(get_comment_link( $comment->comment_ID )); ?>">
            <?php echo wp_kses_post(get_comment_excerpt( $comment->comment_ID )); ?>
        </a>
    </div>
</li>
