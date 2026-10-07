<?php defined('ABSPATH') || exit;

/**
 * 邮件模板
 *
 * 给站点通过 wp_mail 发送的邮件统一套用一个全局模板（页头 LOGO + 正文内容区 + 页脚版权/链接）。
 * - 内置几套布局可选，配色自动跟随插件设置的主色调（member_color）；
 * - 页头 LOGO 可选，未设置时使用注册登录页 LOGO，页脚可设置版权文字与链接；
 * - 自动跳过本身已带完整 HTML 模板的邮件（如 WooCommerce），
 *   也可通过标题关键词或 wpmx_mail_template_skip 过滤器额外排除。
 */

// 注册「邮件模板」设置面板
add_filter('wpmx_admin_options', 'wpmx_mail_template_form_options', 20);
function wpmx_mail_template_form_options( $options ){
    if( !is_array($options) ) $options = array();
    $options = array_merge($options, wpmx_mail_template_admin_options());
    return $options;
}

function wpmx_mail_template_admin_options(){
    $options = array(
        array(
            'title' => '邮件模板',
            'desc'  => '给站点发送的邮件统一套用模板，设置页头 LOGO 与页脚版权信息',
            'type'  => 'tt'
        ),
        array(
            'name'  => 'mail_template_on',
            'title' => '开启邮件模板',
            'desc'  => '开启后，站点通过 wp_mail 发送的邮件会套用下方模板',
            'std'   => 0,
            'type'  => 't'
        ),
        array(
            'type'   => 'w',
            'filter' => 'mail_template_on:1',
            'options' => array(
                array(
                    'name'    => 'mail_template_style',
                    'title'   => '模板样式',
                    'desc'    => '选择内置邮件模板布局，配色自动跟随主题或者插件设置的主色调',
                    'std'     => 'default',
                    'type'    => 'r',
                    'ux'      => wpmx_mail_template_has_img_panel() ? 2 : 1,
                    'options' => wpmx_mail_template_style_options()
                ),
                array(
                    'name'  => 'mail_template_logo',
                    'title' => '页头 LOGO',
                    'desc'  => '可选，不设置则使用「注册登录页LOGO」，都没有则显示站点名称；建议使用透明背景 PNG 图片',
                    'type'  => 'at'
                ),
                array(
                    'name'  => 'mail_template_logo_width',
                    'title' => 'LOGO 宽度',
                    'desc'  => 'LOGO 显示宽度，单位 px，留空则使用原图宽度，高度根据图片比例自适应',
                    'type'  => 'text'
                ),
                array(
                    'name'  => 'mail_template_footer',
                    'title' => '页脚版权',
                    'desc'  => '可用占位符：{site_name} 站点名称、{year} 当前年份、{home_url} 站点首页链接',
                    'std'   => '&copy; {year} {site_name} 版权所有',
                    'type'  => 'e'
                ),
                array(
                    'name'  => 'mail_template_exclude_subject',
                    'title' => '排除标题关键词',
                    'desc'  => '邮件标题包含以下任意关键词时不套用模板，每行一个<br>已带完整 HTML 模板的邮件（如 WooCommerce）会自动跳过；也可通过标题关键词额外排除',
                    'type'  => 'ta'
                )
            )
        )
    );
    return apply_filters('wpmx_mail_template_admin_options', $options);
}

/*
 * 富文本页脚兼容：面板框架保存时会对所有字段做 sanitize_text_field（会剥离 HTML），
 * 这里在面板保存时把页脚富文本单独存到一个 option，并在读取 wpmx_options 时回填，
 * 确保页脚里的链接、加粗等格式不丢失。
 */
add_action('wpcom-member_panel_form', 'wpmx_mail_footer_save');
function wpmx_mail_footer_save(){
    if( !isset($_POST['mail_template_footer']) ) return;
    $footer = wp_kses_post( wp_unslash( $_POST['mail_template_footer'] ) );
    update_option( 'wpmx_mail_footer', $footer );
}

add_filter('option_wpmx_options', 'wpmx_mail_footer_restore', 20);
function wpmx_mail_footer_restore( $options ){
    if( !is_array($options) ) return $options;
    $footer = get_option( 'wpmx_mail_footer' );
    if( $footer !== false ){
        $options['mail_template_footer'] = $footer;
    }
    return $options;
}

// 应用邮件模板
add_filter('wp_mail', 'wpmx_mail_template_apply', 20);
function wpmx_mail_template_apply( $atts ){
    $options = isset($GLOBALS['wpmx_options']) ? $GLOBALS['wpmx_options'] : array();
    if( empty($options['mail_template_on']) || $options['mail_template_on'] !== '1' ) return $atts;

    $message = isset($atts['message']) ? $atts['message'] : '';
    if( $message === '' ) return $atts;

    if( wpmx_mail_template_skip( $atts ) ) return $atts;

    // 纯文本邮件转成 HTML，避免套模板后 HTML 标签裸露
    $content_type = wpmx_mail_content_type( $atts );
    if( !wpmx_is_html_content_type( $content_type ) && !wpmx_message_looks_html( $message ) ){
        $message = nl2br( esc_html( $message ) );
        $atts = wpmx_set_html_content_type( $atts );
    }

    $atts['message'] = wpmx_mail_template_render( $message, $atts );
    return $atts;
}

// 判断是否需要跳过模板
function wpmx_mail_template_skip( $atts ){
    $message = isset($atts['message']) ? $atts['message'] : '';

    // 本身已带完整 HTML 模板的邮件（如 WooCommerce），跳过
    if( stripos( $message, '<!DOCTYPE' ) !== false
        || stripos( $message, '<html' ) !== false
        || stripos( $message, '<body' ) !== false ){
        return true;
    }

    // multipart 邮件结构特殊，无法安全套用，跳过
    $content_type = wpmx_mail_content_type( $atts );
    if( stripos( $content_type, 'multipart/' ) !== false ) return true;

    // 标题关键词排除
    $subject  = isset($atts['subject']) ? (string)$atts['subject'] : '';
    $keywords = wpmx_mail_template_exclude_keywords();
    if( $subject !== '' && !empty($keywords) ){
        foreach( $keywords as $kw ){
            if( $kw !== '' && stripos( $subject, $kw ) !== false ){
                return true;
            }
        }
    }

    return apply_filters('wpmx_mail_template_skip', false, $atts);
}

function wpmx_mail_template_exclude_keywords(){
    $options  = isset($GLOBALS['wpmx_options']) ? $GLOBALS['wpmx_options'] : array();
    $keywords = array();
    if( !empty($options['mail_template_exclude_subject']) ){
        $lines = preg_split('/\r\n|\r|\n/', (string)$options['mail_template_exclude_subject']);
        foreach( $lines as $line ){
            $line = trim( $line );
            if( $line !== '' ) $keywords[] = $line;
        }
    }
    return apply_filters('wpmx_mail_template_exclude_keywords', $keywords);
}

// 读取邮件 Content-Type
function wpmx_mail_content_type( $atts ){
    $headers = isset($atts['headers']) ? $atts['headers'] : array();
    if( is_string($headers) ){
        $headers = explode("\n", str_replace("\r\n", "\n", $headers));
    }
    if( !is_array($headers) ) return '';
    foreach( $headers as $header ){
        if( is_string($header) && stripos( $header, 'Content-Type:' ) === 0 ){
            return trim( substr( $header, strlen('Content-Type:') ) );
        }
    }
    return '';
}

function wpmx_is_html_content_type( $type ){
    return stripos( $type, 'text/html' ) !== false;
}

// 内容里包含常见 HTML 标签则视为 HTML（兼容部分未正确声明 Content-Type 的插件）
function wpmx_message_looks_html( $message ){
    return (bool) preg_match( '/<(p|a|div|table|span|br|img|h[1-6]|ul|ol|li|strong|b|em|i|u)\b/i', $message );
}

// 将 Content-Type 改为 text/html
function wpmx_set_html_content_type( $atts ){
    $headers   = isset($atts['headers']) ? $atts['headers'] : array();
    $is_string = is_string( $headers );
    if( $is_string ){
        $headers = explode("\n", str_replace("\r\n", "\n", $headers));
    } elseif( !is_array($headers) ){
        $headers = array();
    }

    $replaced = false;
    foreach( $headers as $k => $header ){
        if( is_string($header) && stripos( $header, 'Content-Type:' ) === 0 ){
            $headers[$k] = 'Content-Type: text/html; charset=UTF-8';
            $replaced = true;
        }
    }
    if( !$replaced ){
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    }

    $atts['headers'] = $is_string ? implode("\n", $headers) : $headers;
    return $atts;
}

// 内置邮件模板列表（key => 布局，name => 模板名，label => 显示名称，img => 预览图）
function wpmx_mail_template_files(){
    $files = array(
        'default' => array( 'name' => 'mail-default', 'label' => '简约', 'img' => 'mail-default.svg' ),
        'color'   => array( 'name' => 'mail-color',   'label' => '炫彩', 'img' => 'mail-color.svg' )
    );
    return apply_filters('wpmx_mail_template_files', $files);
}

// 面板环境判断：主题或高级版插件的面板支持图片单选（ux=2），
// 免费版独立面板（PHP 渲染）不支持，降级为普通文字选项
function wpmx_mail_template_has_img_panel(){
    return defined('WPCOM_MP_VERSION') || function_exists('wpcom_setup');
}

// 生成样式选项：支持图片的面板用「名称||图片地址」，否则降级为纯名称
function wpmx_mail_template_style_options(){
    $with_img = wpmx_mail_template_has_img_panel();
    $options = array();
    foreach( wpmx_mail_template_files() as $key => $tpl ){
        $label = isset($tpl['label']) ? $tpl['label'] : $key;
        if( $with_img && !empty($tpl['img']) && file_exists( WPMX_DIR . 'images/' . $tpl['img'] ) ){
            $options[$key] = $label . '||' . WPMX_URI . 'images/' . $tpl['img'];
        } else {
            $options[$key] = $label;
        }
    }
    return $options;
}

// 颜色工具：主色调加深/减淡（$ratio 为负向黑混合、为正向白混合，-1 ~ 1）
function wpmx_mail_color_mix( $hex, $ratio ){
    $hex = ltrim( (string)$hex, '#' );
    if( !preg_match('/^[0-9a-fA-F]{6}$/', $hex) ) return '#206be7';
    $target = $ratio > 0 ? 255 : 0;
    $ratio  = min( 1, abs( $ratio ) );
    $out = '';
    foreach( array(0, 2, 4) as $i ){
        $v = hexdec( substr( $hex, $i, 2 ) );
        $out .= sprintf( '%02x', round( $v + ( $target - $v ) * $ratio ) );
    }
    return '#' . $out;
}

// 颜色工具：返回在主色调背景上可读的文字颜色（白或深灰）
function wpmx_mail_color_contrast( $hex ){
    $hex = ltrim( (string)$hex, '#' );
    if( !preg_match('/^[0-9a-fA-F]{6}$/', $hex) ) return '#ffffff';
    $r = hexdec( substr($hex,0,2) ); $g = hexdec( substr($hex,2,2) ); $b = hexdec( substr($hex,4,2) );
    $luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
    return $luminance > 0.6 ? '#333333' : '#ffffff';
}

// 从插件设置的主色调派生邮件用的一组颜色
function wpmx_mail_template_colors(){
    $options = isset($GLOBALS['wpmx_options']) ? $GLOBALS['wpmx_options'] : array();
    $color   = isset($options['member_color']) ? trim( (string)$options['member_color'] ) : '';
    if( !preg_match('/^#?[0-9a-fA-F]{6}$/', $color) ) $color = '#206be7';
    if( $color[0] !== '#' ) $color = '#' . $color;

    $colors = array(
        'color'      => $color,                                // 主色调
        'color_dark' => wpmx_mail_color_mix( $color, -0.25 ),  // 加深，用于页脚/强调
        'color_light'=> wpmx_mail_color_mix( $color, 0.93 ),   // 淡化，用于页面背景
        'color_text' => wpmx_mail_color_contrast( $color )     // 主色调背景上的可读文字色
    );
    return apply_filters('wpmx_mail_template_colors', $colors);
}

// 页头品牌区：有 LOGO 显示 LOGO，否则显示站点名称
function wpmx_mail_template_brand( $logo_url, $logo_width, $site_name, $home_url, $color, $align = 'center' ){
    $margin = $align === 'left' ? 'margin:0;' : 'margin:0 auto;';
    if( $logo_url ){
        $width_attr = $logo_width ? ' width="' . intval($logo_width) . '"' : '';
        return '<a href="' . esc_url($home_url) . '" style="text-decoration:none;">'
             . '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '"' . $width_attr
             . ' style="border:0;outline:none;text-decoration:none;display:block;max-width:100%;height:auto;' . $margin . '"></a>';
    }
    return '<a href="' . esc_url($home_url) . '" style="text-decoration:none;color:' . esc_attr($color) . ';font-size:22px;font-weight:700;">'
         . esc_html($site_name) . '</a>';
}

// 加载并渲染指定样式的模板，返回 HTML。直接复用插件内置的模板加载方法，
// 自动获得主题（member/ 目录）、框架、WPCOM_MP_DIR 的覆盖能力。
function wpmx_mail_template_load( $style, $vars = array() ){
    $files = wpmx_mail_template_files();
    if( !isset($files[$style]) || empty($files[$style]['name']) ){
        $style = 'default';
    }
    $name = $files[$style]['name'];

    $member = isset($GLOBALS['wpcom_member']) ? $GLOBALS['wpcom_member'] : null;
    if( $member && is_callable( array($member, 'load_template') ) ){
        $html = $member->load_template( $name, $vars );
        return is_string($html) ? $html : '';
    }
    return '';
}

function wpmx_mail_template_render( $message, $atts ){
    $options = isset($GLOBALS['wpmx_options']) ? $GLOBALS['wpmx_options'] : array();
    $style   = !empty($options['mail_template_style']) ? $options['mail_template_style'] : 'default';

    // LOGO（支持附件 ID 或 URL）：未设置页头 LOGO 时回退到注册登录页 LOGO
    $logo_value = !empty($options['mail_template_logo']) ? $options['mail_template_logo'] : '';
    if( !$logo_value && !empty($options['login_logo']) ) $logo_value = $options['login_logo'];
    $logo_url   = '';
    if( $logo_value ){
        $logo_url = is_numeric($logo_value) ? wp_get_attachment_url($logo_value) : $logo_value;
    }
    $logo_width = isset($options['mail_template_logo_width']) ? absint($options['mail_template_logo_width']) : 0;

    $footer = isset($options['mail_template_footer']) ? $options['mail_template_footer'] : '';
    $footer = $footer !== '' ? $footer : '&copy; {year} {site_name} 版权所有';

    $site_name = wp_specialchars_decode( get_bloginfo('name'), ENT_QUOTES );
    $home_url  = home_url('/');

    $footer = str_replace(
        array( '{year}', '{site_name}', '{home_url}' ),
        array( date('Y'), esc_html($site_name), esc_url($home_url) ),
        $footer
    );

    $vars = array_merge( wpmx_mail_template_colors(), array(
        'message'    => $message,
        'footer'     => $footer,
        'site_name'  => $site_name,
        'home_url'   => $home_url,
        'logo_url'   => $logo_url,
        'logo_width' => $logo_width,
        'subject'    => isset($atts['subject']) ? $atts['subject'] : ''
    ) );

    $html = wpmx_mail_template_load( $style, $vars );

    return apply_filters('wpmx_mail_template_html', $html, $message, $atts, $style);
}
