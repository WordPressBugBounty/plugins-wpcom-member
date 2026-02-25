<div class="social-login-wrap">
<?php if($is_bind){ ?>
    <div class="sl-info-notice" style="border-bottom: 0;padding-top: 20px;">
        <div class="sl-info-avatar">
            <?php echo wpmx_image($avatar, $newuser['nickname'], 'j-lazy');?>
        </div>
        <div class="sl-info-text">
            <?php /* translators: %s: nickname */ ?>
            <p><?php printf(__('Hi, <b>%s</b>!', WPMX_TD), $newuser['nickname']) ?></p>
            <?php /* translators: %1$s: social login title, %2$s: social login title */ ?>
            <p><?php printf(__('Your <b>%1$s</b> account has been bound successfully, you can log in directly with your <b>%2$s</b> account in the future.', WPMX_TD), $social[$newuser['type']]['title'], $social[$newuser['type']]['title']) ?></p>
        </div>
    </div>
 <?php } else { ?>
    <div class="sl-info-notice">
        <div class="sl-info-avatar">
            <?php echo wpmx_image($avatar, $newuser['nickname'], 'j-lazy');?>
        </div>
        <div class="sl-info-text">
            <?php /* translators: %s: nickname */ ?>
            <p><?php printf(__('Hi, <b>%s</b>!', WPMX_TD), $newuser['nickname']) ?></p>
            <?php /* translators: %s: social login title */ ?>
            <p><?php printf(__('You are logging in with a <b>%s</b> account, please bind an existing account or register a new account.', WPMX_TD), $social[$newuser['type']]['title']) ?></p>
        </div>
    </div>

    <div class="social-login-form">
        <div class="sl-form-item">
            <form id="sl-form-create" class="sl-info-form" method="post">
                <?php wp_nonce_field('wpcom_social_login', 'social_login_nonce', true, false) ?>
                <h3 class="sl-form-title"><?php esc_html_e('Bind an existing account', WPMX_TD) ?></h3>
                <div class="sl-input-item">
                    <label><?php echo esc_html_x('Username', 'label', WPMX_TD) ?></label>
                    <div class="sl-input">
                        <input class="require" type="text" name="username" value="" placeholder="<?php (is_wpcom_enable_phone() ? esc_attr_e('Phone number / E-mail / Username', WPMX_TD) : esc_attr_e('Username or email address', WPMX_TD))?>">
                    </div>
                </div>
                <div class="sl-input-item">
                    <label><?php echo esc_html_x('Password', 'label', WPMX_TD) ?></label>
                    <div class="sl-input">
                        <input class="require" type="password" name="password" value="" placeholder="<?php echo esc_attr_x('Password', 'placeholder', WPMX_TD) ?>">
                    </div>
                </div>
                <?php do_action('wpmx_social_login_bind_form');?>
                <div class="sl-input-item sl-submit">
                    <div class="sl-result pull-left"></div>
                    <button class="wpcom-btn btn-primary btn-lg btn-block sl-input-submit" type="submit"><?php esc_html_e('Login and bind', WPMX_TD);?></button>
                </div>
            </form>
        </div>

        <div class="sl-form-item">
            <form id="sl-form-bind" class="sl-info-form" method="post">
                <?php wp_nonce_field('wpcom_social_login2', 'social_login2_nonce', true, false)?>
                <h3 class="sl-form-title"><?php esc_html_e('Register a new account', WPMX_TD);?></h3>
                <?php do_action('wpmx_social_login_register_form');?>
                <div class="sl-input-item sl-submit" style="text-align: left">
                    <div class="sl-result pull-left"></div>
                    <button class="wpcom-btn btn-primary btn-block btn-lg sl-input-submit" type="submit"><?php esc_html_e('Register', WPMX_TD);?></button>
                </div>
            </form>
        </div>
    </div>
<?php } ?>
</div>