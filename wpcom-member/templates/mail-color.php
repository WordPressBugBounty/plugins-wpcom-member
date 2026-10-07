<?php
/**
 * 邮件模板：炫彩
 *
 * 布局：顶部主色调三色装饰条，渐变页头（内含 LOGO），
 * 白色正文，渐变页脚色带；渐变在不支持的客户端（如 Outlook）回退为纯色
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

$color_mid   = wpmx_mail_color_mix( $color, -0.12 );
$gradient    = 'background-color:' . $color . ';background-image:linear-gradient(135deg,' . $color . ' 0%,' . $color_dark . ' 100%);';
$footer_text = wpmx_mail_color_mix( $color_dark, 0.6 );
$brand       = wpmx_mail_template_brand( $logo_url, $logo_width, $site_name, $home_url, $color_text );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:0;background-color:#ffffff;-webkit-text-size-adjust:none;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;box-shadow:1px 1px 5px 1px #e4e4e4;">
<tr>
<td width="33%" style="width:33%;background-color:<?php echo $color; ?>;height:6px;line-height:6px;font-size:0;border-radius:8px 0 0 0;">&nbsp;</td>
<td width="34%" style="width:34%;background-color:<?php echo $color_mid; ?>;height:6px;line-height:6px;font-size:0;">&nbsp;</td>
<td width="33%" style="width:33%;background-color:<?php echo $color_dark; ?>;height:6px;line-height:6px;font-size:0;border-radius:0 8px 0 0;">&nbsp;</td>
</tr>
<tr><td colspan="3" align="center" style="<?php echo $gradient; ?>padding:36px 24px 32px;">
<?php echo $brand; ?>
</td></tr>
<tr><td colspan="3" style="background-color:#ffffff;color:#333333;padding:32px 28px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.7;"><?php echo $message; ?></td></tr>
<?php if( $footer !== '' ){ ?>
<tr><td colspan="3" align="center" style="<?php echo $gradient; ?>color:<?php echo $footer_text; ?>;padding:16px 20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:12px;line-height:1.6;border-radius:0 0 8px 8px;"><?php echo $footer; ?></td></tr>
<?php } ?>
</table>
</td></tr></table>
</body>
</html>
