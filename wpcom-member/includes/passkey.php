<?php
/**
 * Passkey (WebAuthn) 登录与账号绑定
 * 作为社交登录的一种类型（name = passkey）接入 Social_Login
 *
 * @package WPCOM\Member
 */
namespace WPCOM\Member;

use WPCOM\Themer\Session;

defined( 'ABSPATH' ) || exit;

class Passkey {

    /** @var Social_Login */
    protected $parent;

    /** 每个用户最多可绑定的通行密钥数量 */
    const MAX_KEYS = 10;

    public function __construct( $parent ) {
        $this->parent = $parent;

        add_action('wp_ajax_wpcom_passkey_register_options', array($this, 'register_options'));
        add_action('wp_ajax_wpcom_passkey_register_verify', array($this, 'register_verify'));
        add_action('wp_ajax_wpcom_passkey_login_options', array($this, 'login_options'));
        add_action('wp_ajax_nopriv_wpcom_passkey_login_options', array($this, 'login_options'));
        add_action('wp_ajax_wpcom_passkey_login_verify', array($this, 'login_verify'));
        add_action('wp_ajax_nopriv_wpcom_passkey_login_verify', array($this, 'login_verify'));
        add_action('wp_ajax_wpcom_passkey_rename', array($this, 'rename'));
        add_action('wp_ajax_wpcom_passkey_delete', array($this, 'delete'));

        add_filter('wpmx_localize_script', array($this, 'localize_script'));
        add_filter('wpcom_account_tabs_bind_metas', array($this, 'bind_metas'), 20);
        add_filter('wpcom_account_field_passkey', array($this, 'render_field'), 10, 2);
    }

    function localize_script( $scripts ){
        $scripts['passkey'] = array(
            'nonce'            => wp_create_nonce('wpcom_passkey'),
            'unsupported'      => __('Your browser does not support Passkey.', WPMX_TD),
            'insecure_context' => __('Passkey requires HTTPS or localhost.', WPMX_TD),
            'cancelled'        => __('Passkey verification was cancelled or failed.', WPMX_TD),
            'add'              => __('Add Passkey', WPMX_TD),
            'rename'           => __('Rename', WPMX_TD),
            'rename_tip'       => __('Press Enter to save, Esc to cancel', WPMX_TD),
            'delete_confirm'   => __('Delete this passkey?', WPMX_TD),
            'name_empty'       => __('Passkey name can not be empty', WPMX_TD),
            'binding'          => __('Passkey binding...', WPMX_TD),
            'logging'          => __('Passkey login...', WPMX_TD),
        );
        $scripts['passkey'] = apply_filters('wpcom_passkey_script_data', $scripts['passkey']);
        return $scripts;
    }

    /* ------------------------------------------------------------------ *
     *  账号绑定面板
     * ------------------------------------------------------------------ */

    function bind_metas( $metas ){
        $key = 1000;
        foreach (array_keys($metas) as $k){
            if (is_numeric($k) && $k >= $key) $key = $k + 10;
        }
        $metas[$key] = array(
            'type'  => 'passkey',
            'label' => _x('Passkey', 'account label', WPMX_TD),
            'name'  => 'passkey',
            'value' => '',
        );
        return $metas;
    }

    function render_field( $html, $args ){
        $user = wp_get_current_user();
        if( !$user || !$user->ID ) return '';
        $panel = $this->panel_html( $user->ID );
        if( !$panel ) return '';
        $label = isset($args['label']) && $args['label'] ? $args['label'] : _x('Passkey', 'account label', WPMX_TD);
        // 遵循账号绑定页现有左右结构：member-account-label + member-account-input
        return '<div class="member-account-item member-text-line item-passkey passkey-field"><label class="member-account-label">' . esc_html($label) . '</label><div class="member-account-input">' . $panel . '</div></div>';
    }

    /**
     * 面板 HTML（账号绑定页），添加/删除后由 AJAX 返回同构 HTML 刷新
     */
    function panel_html( $user_id ){
        $passkeys = $this->get_passkeys( $user_id );
        $count = count($passkeys);
        $recent_cid = '';
        if( $count ){
            $newest = 0;
            foreach ( $passkeys as $cid => $pk ){
                $t = isset($pk['created_u']) ? (int)$pk['created_u'] : 0;
                if( $t >= $newest ){
                    $newest = $t;
                    $recent_cid = $cid;
                }
            }
            // 3 天内添加的才显示“最近添加”角标
            if( $newest && (time() - $newest) > 3 * DAY_IN_SECONDS ) $recent_cid = '';
        }

        ob_start();
        ?>
        <div class="passkey-panel">
            <p class="passkey-panel-desc"><?php echo esc_html__('Supports Face ID, Touch ID, PIN or hardware security keys. A safer and faster alternative to QR code login.', WPMX_TD); ?></p>
            <?php if( $count < self::MAX_KEYS ){ ?>
            <button type="button" class="wpcom-btn btn-primary btn-sm j-passkey-add"><i class="wpcom-icon wi passkey-add-icon"><svg aria-hidden="true"><use xlink:href="#wi-add"></use></svg></i><?php echo esc_html__('Add Passkey', WPMX_TD); ?></button>
            <?php } ?>
            <?php if( $count ){ ?>
            <ul class="passkey-list">
                <?php foreach ( $passkeys as $cid => $pk ){
                    $name = isset($pk['name']) && $pk['name'] !== '' ? $pk['name'] : __('Passkey', WPMX_TD);
                    $created = '';
                    if( !empty($pk['created']) ){
                        $created = mysql2date(get_option('date_format'), $pk['created']);
                    }
                    ?>
                    <li class="passkey-item" data-cid="<?php echo esc_attr($cid); ?>">
                        <div class="passkey-item-info">
                            <div class="passkey-item-name">
                                <span class="passkey-item-text"><?php echo esc_html($name); ?></span>
                                <?php if( $cid === $recent_cid ){ ?><span class="passkey-badge"><?php echo esc_html__('Recently added', WPMX_TD); ?></span><?php } ?>
                            </div>
                            <?php if( $created ){ ?><div class="passkey-item-date"><?php /* translators: %s: date */ printf(esc_html__('Added on: %s', WPMX_TD), esc_html($created)); ?></div><?php } ?>
                        </div>
                        <div class="passkey-item-actions">
                            <a href="#" class="j-passkey-rename" data-cid="<?php echo esc_attr($cid); ?>" data-name="<?php echo esc_attr($name); ?>" title="<?php echo esc_attr__('Rename', WPMX_TD); ?>" aria-label="<?php echo esc_attr__('Rename', WPMX_TD); ?>"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-quill-pen"></use></svg></i></a>
                            <a href="#" class="j-passkey-delete" data-cid="<?php echo esc_attr($cid); ?>" title="<?php echo esc_attr__('Delete', WPMX_TD); ?>" aria-label="<?php echo esc_attr__('Delete', WPMX_TD); ?>"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-delete"></use></svg></i></a>
                        </div>
                    </li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ *
     *  注册（绑定）流程
     * ------------------------------------------------------------------ */

    function register_options(){
        if( !$this->verify_nonce() ) return;

        $user = wp_get_current_user();
        if( !$user || !$user->ID ){
            $this->fail(__('You must login first!', WPMX_TD));
        }

        $passkeys = $this->get_passkeys( $user->ID );
        if( count($passkeys) >= self::MAX_KEYS ){
            $this->fail(sprintf(/* translators: %d: max number */ __('You can add up to %d passkeys.', WPMX_TD), self::MAX_KEYS));
        }

        $challenge = random_bytes(32);
        Session::set('passkey_register_challenge', self::b64url_encode($challenge), 300);

        $exclude = array();
        foreach ( array_keys($passkeys) as $cid ){
            $exclude[] = array('type' => 'public-key', 'id' => $cid);
        }

        // 用户名作为 user.name，user.id 使用站点前缀+UID 的哈希
        $this->success(array(
            'options' => array(
                'challenge'        => self::b64url_encode($challenge),
                'rp'               => array(
                    'name' => get_bloginfo('name'),
                    'id'   => $this->rp_id(),
                ),
                'user'             => array(
                    'id'          => self::b64url_encode(hash('sha256', 'wpcom-member-user-' . $user->ID, true)),
                    'name'        => $user->user_login,
                    'displayName' => $user->display_name ?: $user->user_login,
                ),
                'pubKeyCredParams' => array(
                    array('type' => 'public-key', 'alg' => -7),   // ES256
                    array('type' => 'public-key', 'alg' => -257), // RS256
                    array('type' => 'public-key', 'alg' => -8),   // EdDSA (Ed25519)
                ),
                'timeout'              => 60000,
                'attestation'          => 'none',
                'excludeCredentials'   => $exclude,
                'authenticatorSelection' => array(
                    'residentKey'        => 'required',
                    'requireResidentKey' => true,
                    'userVerification'   => 'required',
                ),
            ),
        ));
    }

    function register_verify(){
        if( !$this->verify_nonce() ) return;

        $user = wp_get_current_user();
        if( !$user || !$user->ID ){
            $this->fail(__('You must login first!', WPMX_TD));
        }

        $credential = $this->get_credential_input();
        if( !$credential || !isset($credential['response']['clientDataJSON'], $credential['response']['attestationObject']) ){
            $this->fail(__('Parameter error', WPMX_TD));
        }

        $challenge = Session::get('passkey_register_challenge');
        Session::delete('', 'passkey_register_challenge');
        if( !$challenge ){
            $this->fail(__('Request expired, please try again.', WPMX_TD));
        }

        $client_data_json = self::b64url_decode($credential['response']['clientDataJSON']);
        $client_data = $this->verify_client_data($client_data_json, 'webauthn.create', self::b64url_decode($challenge));
        if( is_wp_error($client_data) ){
            $this->fail($client_data->get_error_message());
        }

        $attestation_object = self::b64url_decode($credential['response']['attestationObject']);
        $offset = 0;
        $attestation = $this->cbor_decode($attestation_object, $offset);
        if( !is_array($attestation) || empty($attestation['authData']) ){
            $this->fail(__('Passkey data is invalid.', WPMX_TD));
        }

        $auth = $this->parse_auth_data($attestation['authData'], true);
        if( is_wp_error($auth) ){
            $this->fail($auth->get_error_message());
        }

        if( !hash_equals(hash('sha256', $this->rp_id(), true), $auth['rp_id_hash']) ){
            $this->fail(__('Passkey domain verification failed.', WPMX_TD));
        }

        if( !($auth['flags'] & 0x01) || !($auth['flags'] & 0x04) ){
            $this->fail(__('Passkey user verification failed.', WPMX_TD));
        }

        $key_info = $this->cose_to_key($auth['credential_public_key']);
        if( is_wp_error($key_info) ){
            $this->fail($key_info->get_error_message());
        }

        $credential_id = self::b64url_encode($auth['credential_id']);
        $bind_user = $this->find_user( $credential_id );
        if( $bind_user && (int)$bind_user->ID !== (int)$user->ID ){
            $this->fail(__('This Passkey is already connected to another account.', WPMX_TD));
        }

        $passkeys = $this->get_passkeys( $user->ID );
        if( isset($passkeys[$credential_id]) ){
            $this->fail(__('This Passkey is already connected.', WPMX_TD));
        }
        if( count($passkeys) >= self::MAX_KEYS ){
            $this->fail(sprintf(/* translators: %d: max number */ __('You can add up to %d passkeys.', WPMX_TD), self::MAX_KEYS));
        }

        $passkeys[$credential_id] = array(
            'name'       => __('Passkey', WPMX_TD),
            'key'        => $key_info,
            'sign_count' => $auth['sign_count'],
            'created'    => current_time('mysql'),
            'created_u'  => time(),
            'last_used'  => '',
        );
        $this->save_passkeys( $user->ID, $passkeys );

        $this->success(array(
            'msg'  => __('Passkey connected successfully.', WPMX_TD),
            'html' => $this->panel_html( $user->ID ),
        ));
    }

    /* ------------------------------------------------------------------ *
     *  登录流程
     * ------------------------------------------------------------------ */

    function login_options(){
        if( !$this->verify_nonce() ) return;

        if( $this->too_many_failures() ){
            $this->fail(__('Too many attempts, please try again later.', WPMX_TD));
        }

        $challenge = random_bytes(32);
        Session::set('passkey_login_challenge', self::b64url_encode($challenge), 300);
        if( isset($_POST['redirect_to']) && $redirect_to = $this->sanitize_redirect( wp_unslash($_POST['redirect_to']) ) ){
            Session::set('redirect_to', $redirect_to);
        }

        $this->success(array(
            'options' => array(
                'challenge'        => self::b64url_encode($challenge),
                'rpId'             => $this->rp_id(),
                'timeout'          => 60000,
                'userVerification' => 'required',
                'allowCredentials' => array(),
            ),
        ));
    }

    function login_verify(){
        if( !$this->verify_nonce() ) return;

        if( $this->too_many_failures() ){
            $this->fail(__('Too many attempts, please try again later.', WPMX_TD));
        }

        $credential = $this->get_credential_input();
        if( !$credential || !isset($credential['response']['clientDataJSON'], $credential['response']['authenticatorData'], $credential['response']['signature']) ){
            $this->fail(__('Parameter error', WPMX_TD));
        }

        $challenge = Session::get('passkey_login_challenge');
        Session::delete('', 'passkey_login_challenge');
        if( !$challenge ){
            $this->fail(__('Request expired, please try again.', WPMX_TD));
        }

        $client_data_json = self::b64url_decode($credential['response']['clientDataJSON']);
        $client_data = $this->verify_client_data($client_data_json, 'webauthn.get', self::b64url_decode($challenge));
        if( is_wp_error($client_data) ){
            $this->count_failure();
            $this->fail($client_data->get_error_message());
        }

        $raw_id = !empty($credential['rawId']) ? $credential['rawId'] : (isset($credential['id']) ? $credential['id'] : '');
        if( !$raw_id ){
            $this->fail(__('Parameter error', WPMX_TD));
        }
        $credential_id = self::b64url_encode(self::b64url_decode($raw_id));
        $user = $this->find_user( $credential_id );
        if( !$user || !$user->ID ){
            $this->count_failure();
            $this->fail(__('This Passkey is not connected to an account.', WPMX_TD));
        }

        $passkeys = $this->get_passkeys( $user->ID );
        if( empty($passkeys[$credential_id]['key']) ){
            $this->count_failure();
            $this->fail(__('This Passkey is not connected to an account.', WPMX_TD));
        }

        $authenticator_data = self::b64url_decode($credential['response']['authenticatorData']);
        $auth = $this->parse_auth_data($authenticator_data, false);
        if( is_wp_error($auth) ){
            $this->count_failure();
            $this->fail($auth->get_error_message());
        }

        if( !hash_equals(hash('sha256', $this->rp_id(), true), $auth['rp_id_hash']) ){
            $this->count_failure();
            $this->fail(__('Passkey domain verification failed.', WPMX_TD));
        }

        if( !($auth['flags'] & 0x01) || !($auth['flags'] & 0x04) ){
            $this->count_failure();
            $this->fail(__('Passkey user verification failed.', WPMX_TD));
        }

        $signed = $authenticator_data . hash('sha256', $client_data_json, true);
        $signature = self::b64url_decode($credential['response']['signature']);
        if( !$this->verify_signature($signed, $signature, $passkeys[$credential_id]['key']) ){
            $this->count_failure();
            $this->fail(__('Passkey signature verification failed.', WPMX_TD));
        }

        // 签名计数器软校验：仅在新计数器大于已存计数器时更新（云通行密钥计数器不一定递增）
        $stored_count = isset($passkeys[$credential_id]['sign_count']) ? (int)$passkeys[$credential_id]['sign_count'] : 0;
        if( $auth['sign_count'] > $stored_count ){
            $passkeys[$credential_id]['sign_count'] = $auth['sign_count'];
        }
        $passkeys[$credential_id]['last_used'] = current_time('mysql');
        $this->save_passkeys( $user->ID, $passkeys );

        Session::delete('', 'passkey_login_fails');

        $options = $GLOBALS['wpmx_options'];
        $redirect_to = Session::get('redirect_to') ?: '';
        if( $redirect_to === '' && isset($options['login_redirect']) && $options['login_redirect'] !== '' ){
            $redirect_to = $options['login_redirect'];
        }else if( $redirect_to === '' ){
            $redirect_to = home_url();
        }
        Session::delete('', 'redirect_to');

        $this->parent->login( $user->ID );
        $this->success(array(
            'redirect_to' => $redirect_to,
        ));
    }

    /* ------------------------------------------------------------------ *
     *  重命名 / 删除
     * ------------------------------------------------------------------ */

    function rename(){
        if( !$this->verify_nonce() ) return;

        $user = wp_get_current_user();
        if( !$user || !$user->ID ){
            $this->fail(__('You must login first!', WPMX_TD));
        }

        $cid = isset($_POST['cid']) ? sanitize_text_field(wp_unslash($_POST['cid'])) : '';
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $name = mb_substr(trim($name), 0, 30);
        if( !$cid || $name === '' ){
            $this->fail(__('Parameter error', WPMX_TD));
        }

        $passkeys = $this->get_passkeys( $user->ID );
        if( !isset($passkeys[$cid]) ){
            $this->fail(__('Parameter error', WPMX_TD));
        }
        $passkeys[$cid]['name'] = $name;
        $this->save_passkeys( $user->ID, $passkeys );

        $this->success(array(
            'msg'  => __('Renamed successfully.', WPMX_TD),
            'html' => $this->panel_html( $user->ID ),
        ));
    }

    function delete(){
        if( !$this->verify_nonce() ) return;

        $user = wp_get_current_user();
        if( !$user || !$user->ID ){
            $this->fail(__('You must login first!', WPMX_TD));
        }

        $cid = isset($_POST['cid']) ? sanitize_text_field(wp_unslash($_POST['cid'])) : '';
        $passkeys = $this->get_passkeys( $user->ID );
        if( !$cid || !isset($passkeys[$cid]) ){
            $this->fail(__('Parameter error', WPMX_TD));
        }

        // 至少保留一种登录方式：邮箱/手机号/其他社交账号/其他通行密钥
        if( count($passkeys) <= 1 && !$this->has_other_login( $user ) ){
            $this->fail(__('The current account only has this login method, please bind other login methods before unbinding!', WPMX_TD));
        }

        unset($passkeys[$cid]);
        $this->save_passkeys( $user->ID, $passkeys );
        delete_user_meta( $user->ID, $this->cid_meta_key(), $cid );

        $this->success(array(
            'msg'  => __('Successfully deleted!', WPMX_TD),
            'html' => $this->panel_html( $user->ID ),
        ));
    }

    /**
     * 解绑全部 Passkey（供 wpcom_social_unbind name=passkey 调用）
     */
    function unbind_all( $user_id ){
        $passkeys = $this->get_passkeys( $user_id );
        foreach ( array_keys($passkeys) as $cid ){
            delete_user_meta( $user_id, $this->cid_meta_key(), $cid );
        }
        delete_user_option( $user_id, 'wpcom_passkeys' );
        update_user_option( $user_id, 'social_type_passkey', '' );
        update_user_option( $user_id, 'social_type_passkey_name', '' );
    }

    /* ------------------------------------------------------------------ *
     *  数据存取
     * ------------------------------------------------------------------ */

    function cid_meta_key(){
        global $wpdb;
        return $wpdb->get_blog_prefix() . 'wpcom_passkey_cid';
    }

    function get_passkeys( $user_id ){
        $passkeys = get_user_option('wpcom_passkeys', $user_id);
        return is_array($passkeys) ? $passkeys : array();
    }

    function save_passkeys( $user_id, $passkeys ){
        update_user_option( $user_id, 'wpcom_passkeys', $passkeys );
        // social_type_passkey 用于社交登录体系的“已绑定”判定
        update_user_option( $user_id, 'social_type_passkey', $passkeys ? '1' : '' );
        update_user_option( $user_id, 'social_type_passkey_name', $passkeys ? __('Passkey', WPMX_TD) : '' );

        // 凭据ID索引（用于登录时按 rawId 查找用户），每个凭据一行 user meta
        $existing = get_user_meta( $user_id, $this->cid_meta_key(), false );
        $existing = array_filter(array_map('strval', (array)$existing));
        $wanted = array_keys($passkeys);
        foreach ( $existing as $cid ){
            if( !in_array($cid, $wanted, true) ) delete_user_meta( $user_id, $this->cid_meta_key(), $cid );
        }
        foreach ( $wanted as $cid ){
            if( !in_array($cid, $existing, true) ) add_user_meta( $user_id, $this->cid_meta_key(), $cid, true );
        }
    }

    function find_user( $credential_id ){
        $users = get_users(array(
            'number'     => 1,
            'meta_key'   => $this->cid_meta_key(),
            'meta_value' => $credential_id,
            'fields'     => 'ID',
        ));
        return $users ? get_user_by('ID', (int)$users[0]) : false;
    }

    function has_other_login( $user ){
        global $wpdb;
        if( $user->mobile_phone ) return true;
        if( $user->user_email && !wpcom_is_empty_mail($user->user_email) ) return true;
        $socials = apply_filters('wpcom_socials', array());
        foreach ( $socials as $social ){
            if( $social['name'] === 'passkey' ) continue;
            if( empty($social['id']) || empty($social['key']) ) continue;
            $type_name = ($social['name'] === 'wechat2' || $social['name'] === 'weapp') ? 'wechat' : $social['name'];
            $openid = get_user_meta( $user->ID, $wpdb->get_blog_prefix() . 'social_type_' . $type_name, true );
            if( !$openid && $social['name'] === 'weapp' ){
                $openid = get_user_meta( $user->ID, $wpdb->get_blog_prefix() . 'social_type_wxxcx', true );
            }
            if( $openid ) return true;
        }
        return false;
    }

    /* ------------------------------------------------------------------ *
     *  WebAuthn 底层工具
     * ------------------------------------------------------------------ */

    function rp_id(){
        return wp_parse_url(home_url(), PHP_URL_HOST);
    }

    function get_credential_input(){
        if( empty($_POST['credential']) ) return null;
        return json_decode( wp_unslash($_POST['credential']), true );
    }

    function verify_client_data( $client_data_json, $type, $challenge ){
        $client_data = json_decode($client_data_json, true);
        if( !$client_data || empty($client_data['type']) || empty($client_data['challenge']) || empty($client_data['origin']) ){
            return new \WP_Error('passkey_client_data', __('Passkey data is invalid.', WPMX_TD));
        }
        if( $client_data['type'] !== $type ){
            return new \WP_Error('passkey_type', __('Passkey verification failed.', WPMX_TD));
        }
        if( !hash_equals($challenge, self::b64url_decode($client_data['challenge'])) ){
            return new \WP_Error('passkey_challenge', __('Passkey request expired, please try again.', WPMX_TD));
        }

        $origin = strtolower( $client_data['origin'] );
        $site_origin = strtolower( wp_parse_url(home_url(), PHP_URL_SCHEME) . '://' . $this->rp_id() );
        $origin_ok = ( $origin === $site_origin );
        if( !$origin_ok ){
            // 本地开发环境允许 localhost / 127.0.0.1（任意端口、http/https）
            $origin_host = wp_parse_url($origin, PHP_URL_HOST);
            $site_host = $this->rp_id();
            if( $origin_host && $site_host && strtolower($origin_host) === strtolower($site_host)
                && in_array(strtolower($site_host), array('localhost', '127.0.0.1'), true) ){
                $origin_ok = true;
            }
        }
        if( !$origin_ok ){
            return new \WP_Error('passkey_origin', __('Passkey domain verification failed.', WPMX_TD));
        }

        return $client_data;
    }

    function parse_auth_data( $auth_data, $require_attested_data = false ){
        if( strlen($auth_data) < 37 ){
            return new \WP_Error('passkey_auth_data', __('Passkey data is invalid.', WPMX_TD));
        }

        $flags = ord($auth_data[32]);
        $counter = unpack('N', substr($auth_data, 33, 4));
        $data = array(
            'rp_id_hash' => substr($auth_data, 0, 32),
            'flags'      => $flags,
            'sign_count' => (int)$counter[1],
        );

        if( $require_attested_data ){
            if( !($flags & 0x40) || strlen($auth_data) < 55 ){
                return new \WP_Error('passkey_attested_data', __('Passkey data is invalid.', WPMX_TD));
            }
            $credential_id_length = unpack('n', substr($auth_data, 53, 2));
            $credential_id_length = (int)$credential_id_length[1];
            $credential_id_offset = 55;
            $public_key_offset = $credential_id_offset + $credential_id_length;
            if( $credential_id_length < 16 || $credential_id_length > 1023 || strlen($auth_data) <= $public_key_offset ){
                return new \WP_Error('passkey_credential_id', __('Passkey data is invalid.', WPMX_TD));
            }
            $data['credential_id'] = substr($auth_data, $credential_id_offset, $credential_id_length);
            $data['credential_public_key'] = substr($auth_data, $public_key_offset);
        }

        return $data;
    }

    /**
     * COSE Key 资源 -> 可验证的密钥结构
     * 支持 ES256/ES384 (EC2)、RS256 (RSA)、EdDSA (OKP/Ed25519)
     */
    function cose_to_key( $cose_key ){
        $offset = 0;
        $key = $this->cbor_decode($cose_key, $offset);
        if( !is_array($key) || !isset($key[1], $key[3]) ){
            return new \WP_Error('passkey_public_key', __('Passkey public key is invalid.', WPMX_TD));
        }

        $kty = (int)$key[1];
        $alg = (int)$key[3];

        if( $kty === 2 ){ // EC2
            $crv = isset($key[-1]) ? (int)$key[-1] : 0;
            $x = isset($key[-2]) ? $key[-2] : '';
            $y = isset($key[-3]) ? $key[-3] : '';
            if( $crv === 1 && $alg === -7 && strlen($x) === 32 && strlen($y) === 32 ){ // P-256 / ES256
                $der = hex2bin('3059301306072A8648CE3D020106082A8648CE3D03010703420004') . $x . $y;
                return array('type' => 'pem', 'alg' => 'sha256', 'pem' => $this->pem($der));
            }
            if( $crv === 2 && $alg === -35 && strlen($x) === 48 && strlen($y) === 48 ){ // P-384 / ES384
                $der = hex2bin('3064300E06072A8648CE3D020106052B8104002203520004') . $x . $y;
                return array('type' => 'pem', 'alg' => 'sha384', 'pem' => $this->pem($der));
            }
            return new \WP_Error('passkey_public_key_alg', __('This Passkey key type is not supported (only ES256, ES384, RS256, Ed25519).', WPMX_TD));
        }

        if( $kty === 3 && $alg === -257 ){ // RSA / RS256
            $n = isset($key[-1]) ? $key[-1] : '';
            $e = isset($key[-2]) ? $key[-2] : '';
            if( strlen($n) < 128 || strlen($n) > 1024 || $e === '' ){
                return new \WP_Error('passkey_public_key_alg', __('This Passkey key type is not supported (only ES256, ES384, RS256, Ed25519).', WPMX_TD));
            }
            return array('type' => 'pem', 'alg' => 'sha256', 'pem' => $this->pem( $this->rsa_spki_der($n, $e) ));
        }

        if( $kty === 1 && $alg === -8 ){ // OKP / Ed25519
            $crv = isset($key[-1]) ? (int)$key[-1] : 0;
            $x = isset($key[-2]) ? $key[-2] : '';
            if( $crv === 6 && strlen($x) === 32 && function_exists('sodium_crypto_sign_verify_detached') ){
                return array('type' => 'sodium', 'key' => $x);
            }
            return new \WP_Error('passkey_public_key_alg', __('This Passkey key type is not supported (only ES256, ES384, RS256, Ed25519).', WPMX_TD));
        }

        return new \WP_Error('passkey_public_key_alg', __('This Passkey key type is not supported (only ES256, ES384, RS256, Ed25519).', WPMX_TD));
    }

    function pem( $der ){
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     * 构造 RSA SubjectPublicKeyInfo DER
     */
    function rsa_spki_der( $n, $e ){
        $rsa_public = $this->der_sequence( $this->der_integer($n) . $this->der_integer($e) );
        // OID rsaEncryption 1.2.840.113549.1.1.1 + NULL
        $alg = $this->der_sequence( hex2bin('06092A864886F70D010101') . hex2bin('0500') );
        // BIT STRING，前缀 0x00（无未使用位）
        $bitstring = hex2bin('03') . $this->der_length( strlen($rsa_public) + 1 ) . "\x00" . $rsa_public;
        return $this->der_sequence( $alg . $bitstring );
    }

    function der_length( $len ){
        if( $len < 128 ) return chr($len);
        $bytes = '';
        while( $len > 0 ){
            $bytes = chr($len & 0xff) . $bytes;
            $len >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    function der_encode( $tag, $contents ){
        return chr($tag) . $this->der_length( strlen($contents) ) . $contents;
    }

    function der_sequence( $contents ){
        return $this->der_encode(0x30, $contents);
    }

    function der_integer( $bytes ){
        // 无符号大端字节 -> 有符号 INTEGER，必要时前补 0x00
        $bytes = ltrim($bytes, "\x00");
        if( $bytes === '' ) $bytes = "\x00";
        if( ord($bytes[0]) & 0x80 ) $bytes = "\x00" . $bytes;
        return $this->der_encode(0x02, $bytes);
    }

    function verify_signature( $signed, $signature, $key ){
        if( empty($key['type']) ) return false;
        if( $key['type'] === 'sodium' ){
            return function_exists('sodium_crypto_sign_verify_detached')
                && strlen($signature) === 64
                && sodium_crypto_sign_verify_detached( $signature, $signed, $key['key'] );
        }
        if( $key['type'] === 'pem' && !empty($key['pem']) ){
            $algo = $key['alg'] === 'sha384' ? OPENSSL_ALGO_SHA384 : OPENSSL_ALGO_SHA256;
            return openssl_verify( $signed, $signature, $key['pem'], $algo ) === 1;
        }
        return false;
    }

    /**
     * 极简 CBOR 解码器（WebAuthn attestation object / COSE key 所需子集）
     * 支持 major 0-7、tag（6）透传、indefinite length
     */
    function cbor_decode( $data, &$offset = 0 ){
        if( $offset >= strlen($data) ) return null;
        $initial = ord($data[$offset++]);
        $major = $initial >> 5;
        $additional = $initial & 0x1f;

        if( $major === 6 ){ // tag：跳过 tag 号，解码内容项
            $this->cbor_length($data, $offset, $additional);
            return $this->cbor_decode($data, $offset);
        }

        if( $additional === 31 && in_array($major, array(4, 5), true) ){ // indefinite length 集合
            if( $major === 4 ){
                $array = array();
                while( $offset < strlen($data) && ord($data[$offset]) !== 0xff ){
                    $array[] = $this->cbor_decode($data, $offset);
                }
                $offset++; // break
                return $array;
            }
            $map = array();
            while( $offset < strlen($data) && ord($data[$offset]) !== 0xff ){
                $k = $this->cbor_decode($data, $offset);
                $map[$k] = $this->cbor_decode($data, $offset);
            }
            $offset++; // break
            return $map;
        }

        $length = $this->cbor_length($data, $offset, $additional);

        switch( $major ){
            case 0:
                return $length;
            case 1:
                return -1 - $length;
            case 2:
            case 3:
                $value = substr($data, $offset, $length);
                $offset += $length;
                return $value;
            case 4:
                $array = array();
                for( $i = 0; $i < $length; $i++ ){
                    $array[] = $this->cbor_decode($data, $offset);
                }
                return $array;
            case 5:
                $map = array();
                for( $i = 0; $i < $length; $i++ ){
                    $key = $this->cbor_decode($data, $offset);
                    $map[$key] = $this->cbor_decode($data, $offset);
                }
                return $map;
            case 7:
                if( $additional === 20 ) return false;
                if( $additional === 21 ) return true;
                if( $additional === 22 ) return null;
                // 简单浮点数：跳过
                if( $additional === 25 ) $offset += 2;
                if( $additional === 26 ) $offset += 4;
                if( $additional === 27 ) $offset += 8;
                return null;
        }

        return null;
    }

    function cbor_length( $data, &$offset, $additional ){
        if( $additional < 24 ) return $additional;
        if( $additional === 24 ) return ord($data[$offset++]);
        if( $additional === 25 ){
            $value = unpack('n', substr($data, $offset, 2));
            $offset += 2;
            return (int)$value[1];
        }
        if( $additional === 26 ){
            $value = unpack('N', substr($data, $offset, 4));
            $offset += 4;
            return (int)$value[1];
        }
        if( $additional === 27 ){
            $value = 0;
            for( $i = 0; $i < 8; $i++ ){
                $value = ($value * 256) + ord($data[$offset++]);
            }
            return $value;
        }
        return 0;
    }

    /* ------------------------------------------------------------------ *
     *  杂项
     * ------------------------------------------------------------------ */

    function verify_nonce(){
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if( !$nonce || !wp_verify_nonce($nonce, 'wpcom_passkey') ){
            $this->fail(__('The nonce check failed', WPMX_TD));
            return false;
        }
        return true;
    }

    function too_many_failures(){
        $fails = (int) Session::get('passkey_login_fails');
        return $fails >= 8;
    }

    function count_failure(){
        $fails = (int) Session::get('passkey_login_fails');
        Session::set('passkey_login_fails', $fails + 1, 600);
    }

    function sanitize_redirect( $url ){
        $url = filter_var($url, FILTER_VALIDATE_URL);
        if( !$url ) return '';
        $home = wp_parse_url(home_url());
        $target = wp_parse_url($url);
        if( empty($target['host']) || strtolower($target['host']) !== strtolower($home['host']) ) return '';
        if( !in_array(strtolower($target['scheme']), array('http', 'https'), true) ) return '';
        return $url;
    }

    function success( $data ){
        wp_send_json( array_merge(array('result' => 1), $data) );
        exit;
    }

    function fail( $msg ){
        wp_send_json( array('result' => 0, 'msg' => $msg) );
        exit;
    }

    static function b64url_encode( $data ){
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    static function b64url_decode( $data ){
        if( !is_string($data) ) return '';
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if( $pad ) $data .= str_repeat('=', 4 - $pad);
        return base64_decode($data);
    }
}
