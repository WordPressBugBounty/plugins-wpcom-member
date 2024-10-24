<?php defined( 'ABSPATH' ) || exit;

// Pagenavi
if(!function_exists('wpcom_pagination')){
    function wpcom_pagination( $range = 9, $args = array() ) {
        global $paged, $wp_query, $page, $numpages, $multipage;
        if ( ($args && $args['numpages'] > 1) || ( isset($multipage) && $multipage && is_singular() ) ) {
            if($args) {
                $page = isset($args['paged']) ? $args['paged'] : $page;
                $numpages = isset($args['numpages']) ? $args['numpages'] : $numpages;
            }
            echo ' <ul class="pagination">';
            $prev = $page - 1;
            if ( $prev > 0 ) {
                echo '<li class="prev">'. wpcom_link_page( $prev, $args ) . '<span>'.esc_html_x('Previous', 'pagination', WPMX_TD).'</span>' . '</a></li>';
            }

            if($numpages > $range){
                if($page < $range){
                    for($i = 1; $i <= ($range + 1); $i++){
                        echo $i==$page ? '<li class="active">' : '<li>';
                        echo wpcom_link_page($i, $args) . esc_html($i) . "</a></li>";
                    }
                } elseif($page >= ($numpages - ceil(($range/2)))){
                    for($i = $numpages - $range; $i <= $numpages; $i++){
                        echo $i==$page ? '<li class="active">' : '<li>';
                        echo wpcom_link_page($i, $args) . esc_html($i) . "</a></li>";
                    }
                } elseif($page >= $range && $page < ($numpages - ceil(($range/2)))){
                    for($i = ($page - ceil($range/2)); $i <= ($page + ceil(($range/2))); $i++){
                        echo $i==$page ? '<li class="active">' : '<li>';
                        echo wpcom_link_page($i, $args) . esc_html($i) . "</a></li>";
                    }
                }
            }else{
                for ( $i = 1; $i <= $numpages; $i++ ) {
                    echo $i==$page ? '<li class="active">' : '<li>';
                    echo wpcom_link_page($i, $args) . esc_html($i) . "</a></li>";
                }
            }

            $next = $page + 1;
            if ( $next <= $numpages ) {
                echo '<li class="next">'. wpcom_link_page($next, $args) . '<span>'.esc_html_x('Next', 'pagination', WPMX_TD).'</span></a></li>';
            }
            $paged_arg = isset($args['paged_arg']) && $args['paged_arg'] ? $args['paged_arg'] : 'page';
            echo '<li class="pagination-go"><form method="get"><input class="pgo-input" type="text" name="'.esc_attr($paged_arg).'" placeholder="'.esc_attr(_x('GO', '页码', WPMX_TD)).'" /><button class="pgo-btn" type="submit" aria-label="'.esc_html_x('GO', '页码', WPMX_TD).'"></button></form></li>';
            echo '</ul>';
        }else if( ($max_page = $wp_query->max_num_pages) > 1 ){
            echo ' <ul class="pagination">';
            if(!$paged) $paged = 1;
            echo '<li class="disabled"><span>'.esc_html($paged).' / '.esc_html($max_page).'</span></li>';
            $prev = get_previous_posts_link('<span>'._x('Previous', 'pagination', WPMX_TD).'</span>');
            if($prev) echo '<li class="prev">'.wp_kses($prev, wpmx_allowed_html()).'</li>';
            if($max_page > $range){
                if($paged < $range){
                    for($i = 1; $i <= ($range + 1); $i++){
                        echo $i==$paged ? '<li class="active">' : '<li>';
                        echo '<a href="' . get_pagenum_link($i) .'">'.esc_html($i).'</a></li>';
                    }
                } elseif($paged >= ($max_page - ceil(($range/2)))){
                    for($i = $max_page - $range; $i <= $max_page; $i++){
                        echo $i==$paged ? '<li class="active">' : '<li>';
                        echo '<a href="' . get_pagenum_link($i) .'">'.esc_html($i).'</a></li>';
                    }
                } elseif($paged >= $range && $paged < ($max_page - ceil(($range/2)))){
                    for($i = ($paged - ceil($range/2)); $i <= ($paged + ceil(($range/2))); $i++){
                        echo $i==$paged ? '<li class="active">' : '<li>';
                        echo '<a href="' . get_pagenum_link($i) .'">'.esc_html($i).'</a></li>';
                    }
                }
            } else {
                for($i = 1; $i <= $max_page; $i++){
                    echo $i==$paged ? '<li class="active">' : '<li>';
                    echo '<a href="' . get_pagenum_link($i) .'">'.esc_html($i).'</a></li>';
                }
            }
            $next = get_next_posts_link('<span>'._x('Next', 'pagination', WPMX_TD).'</span>');
            if($next) echo '<li class="next">'.wp_kses($next, wpmx_allowed_html()).'</li>';
            echo '<li class="pagination-go"><form method="get">';
            if(is_search()) echo '<input type="hidden" name="s" value="' . esc_attr(get_search_query()) . '">';
            echo '<input class="pgo-input" type="text" name="paged" placeholder="'.esc_attr_x('GO', '页码', WPMX_TD).'" /><button class="pgo-btn" type="submit" aria-label="'.esc_html_x('GO', '页码', WPMX_TD).'"></button></form></li>';
            echo '</ul>';
        }
    }

    function wpcom_link_page( $i, $args ) {
        if(isset($args['url']) && $args['url']){
            if ( (isset($args['paged_type']) && $args['paged_type'] === 'query') || '' == get_option( 'permalink_structure' ) ) {
                $url = add_query_arg( isset($args['paged_arg']) && $args['paged_arg'] ? $args['paged_arg'] : 'page', $i, $args['url'] );
            } else {
                $url = trailingslashit( $args['url'] ) . user_trailingslashit( $i, 'single_paged' );
            }
            $url = '<a href="' . esc_url( $url ) . '" class="post-page-numbers">';
        }else{
            $url = _wp_link_page($i);
        }
        return wp_kses($url, wpmx_allowed_html());
    }

    add_filter('previous_posts_link_attributes', 'wpcom_prev_posts_link_attr');
    function wpcom_prev_posts_link_attr($attr){
        return $attr.' class="prev"';
    }
    add_filter('next_posts_link_attributes', 'wpcom_next_posts_link_attr');
    function wpcom_next_posts_link_attr($attr){
        return $attr.' class="next"';
    }
}

if(!function_exists('wpcom_empty_icon')){
    function wpcom_empty_icon($type='post'){
        return '<img class="empty-icon j-lazy" src="' . esc_url(WPMX_URI . 'images/empty-' . $type . '.svg') . '">';
    }
}

if(!function_exists('wpcom_pre_handle_404')){
    add_action('pre_handle_404', 'wpmx_pre_handle_404');
    function wpmx_pre_handle_404($res){
        global $wp_query, $wp_version;
        if ( $wp_query->posts && version_compare($wp_version, '5.5') >= 0) {
            $content_found = true;
            if ( is_singular() ) {
                $post = isset( $wp_query->post ) ? $wp_query->post : null;
                // Only set X-Pingback for single posts that allow pings.
                if ( $post && pings_open( $post ) && ! headers_sent() ) {
                    header( 'X-Pingback: ' . get_bloginfo( 'pingback_url', 'display' ) );
                }
                $paged = get_query_var( 'page' );
                if ( $post && ! empty( $paged ) ) {
                    $shortcode_tags = array('wpcom_tags', 'wpcom-member');
                    preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $post->post_content, $matches );
                    $tagnames = array_intersect( $shortcode_tags, $matches[1] );

                    if ( empty($tagnames) ) {
                        $content_found = false;
                    }else if(in_array('wpcom_tags', $tagnames)){
                        preg_match( '/\[wpcom_tags[^\]]*\]/i', $post->post_content, $matches2 );
                        if(isset($matches2[0])){
                            $text = ltrim($matches2[0], '[wpcom_tags');
                            $text = rtrim($text, ']');
                            $atts = shortcode_parse_atts($text);
                            if(isset($atts['per_page']) && $atts['per_page']){ // 分页
                                $tax = isset($atts['taxonomy']) && $atts['taxonomy'] ? $atts['taxonomy'] : 'post_tag';
                                $max   = wp_count_terms( $tax, array( 'hide_empty' => true ) );
                                $pages   = ceil( $max / $atts['per_page'] );
                                if($pages<$paged) $content_found = false; // 页数超过
                            }else{ // 未分页，则一页全部显示
                                $content_found = false;
                            }
                        }
                    }
                }
            }

            if ( $content_found ) $res = true;
        }
        return $res;
    }
}