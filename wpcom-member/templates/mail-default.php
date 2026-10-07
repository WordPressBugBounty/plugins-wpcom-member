<?php
/**
 * 邮件模板：简约
 *
 * 布局：浅灰页面背景上的白色卡片（box），页头为主色调淡色底 + 左对齐 LOGO，
 * 正文白色，页脚同为淡色底灰字
 *
 * 可用变量：
 *   $message       邮件正文 HTML
 *   $footer        页脚内容（已替换占位符）
 *   $site_name     站点名称
 *   $home_url      站点首页地址
 *   $logo_url      页头 LOGO 地址（可为空）
 *   $logo_width    LOGO 显示宽度（0 表示原图宽度）
 *   $subject       邮件标题
 *   $color         主色调
 *   $color_dark    主色调加深
 *   $color_light   主色调淡化（页面背景）
 *   $color_text    主色调背景上的可读文字色
 */
defined('ABSPATH') || exit;

$brand = wpmx_mail_template_brand( $logo_url, $logo_width, $site_name, $home_url, '#333333', 'left' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background-color:#ffffff;-webkit-text-size-adjust:none;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:4px;box-shadow:1px 1px 5px 1px #e4e4e4;">
<tr><td align="left" style="background-color:<?php echo $color_light; ?>;padding:26px 28px;border-radius:4px 4px 0 0;"><?php echo $brand; ?></td></tr>
<tr><td style="color:#333333;padding:34px 28px 40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.7;<?php if( $footer === '' ) echo 'border-radius:0 0 4px 4px;'; ?>"><?php echo $message; ?></td></tr>
<?php if( $footer !== '' ){ ?>
<tr><td align="center" style="background-color:#f2f3f5;color:#8a8f99;padding:16px 24px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;border-radius:0 0 4px 4px;"><?php echo $footer; ?></td></tr>
<?php } ?>
</table>
</td></tr></table>
</body>
</html>
