<?php
namespace WPCOM\Themer;

defined( 'ABSPATH' ) || exit;

if( !class_exists( Session::class ) ) {
    class Session{
        private static $table = 'wpcom_sessions';
        public static function set($name, $value, $expired=''){
            global $wpcom_wpdb, $wpdb;
            self::init_database();
            $table = $wpdb->prefix . self::$table;
            $session = [];
            if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
            $session['name'] = $name;
            $session['value'] = $value;
            $session['expired'] = $expired && is_numeric($expired) ? $expired : 900;
            $session['time'] = current_time( 'mysql', 1 );
            $query = $wpdb->prepare("SELECT * FROM `$table` WHERE name = %s", $name);
            $option = @$wpcom_wpdb->get_row( $query );
            if($option && isset($option->value)) {
                unset($session['name']);
                $res = $wpcom_wpdb->update($table, $session, ['name' => $name]);
            }else{
                $res = $wpcom_wpdb->insert($table, $session);
            }
            return $res;
        }

        public static function get($name){
            global $wpcom_wpdb, $wpdb;
            self::init_database();
            $table = $wpdb->prefix . self::$table;
            if($name) {
                if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                $query = $wpdb->prepare("SELECT * FROM `$table` WHERE name = %s", $name);
                $row = $wpcom_wpdb->get_row($query);
                if($row && isset($row->value)){
                    if( (get_date_from_gmt($row->time, 'U') + $row->expired) > current_time( 'timestamp', 1 ) ) {
                        return $row->value;
                    } else {
                        self::delete($row->ID);
                    }
                }
            }
        }

        public static function delete($id='', $name=''){
            global $wpcom_wpdb, $wpdb;
            self::init_database();
            $table = esc_sql($wpdb->prefix . self::$table);
            if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
                $array = [];
                if($id) $array['ID'] = absint($id);
                if($name) {
                    $name = sanitize_text_field($name);
                    if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                    $array['name'] = $name;
                }
                @$wpcom_wpdb->delete($table, $array);
            }
        }

        public static function cron(){
            global $wpcom_wpdb, $wpdb;
            self::init_database();
            $table = esc_sql($wpdb->prefix . self::$table);
            if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
                $timestamp = current_time( 'timestamp', 1 );
                $query = $wpdb->prepare("SELECT * FROM `$table` WHERE UNIX_TIMESTAMP(time) + expired < %d", $timestamp);
                $temps = $wpcom_wpdb->get_results($query);
                if ($temps) {
                    foreach ($temps as $temp) {
                        @$wpcom_wpdb->delete($table, ['ID' => $temp->ID]);
                    }
                }
            }
        }

        private static function init_database(){
            global $wpcom_wpdb, $wpdb;
            self::int_wpdb();
            $table = $wpdb->prefix . self::$table;
            if( $wpcom_wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ){
                $charset_collate = $wpcom_wpdb->get_charset_collate();
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                // 缓存表
                $create_sql = "CREATE TABLE $table (".
                    "ID BIGINT(20) NOT NULL auto_increment,".
                    "name text NOT NULL,".
                    "value longtext NOT NULL,".
                    "expired text,".
                    "time datetime,".
                    "PRIMARY KEY (ID)) $charset_collate;";

                dbDelta( $create_sql );
            }
        }

        public static function session_prefix(){
            $session_prefix = isset($_COOKIE['_s_prefix']) ? $_COOKIE['_s_prefix'] : '';
            if($session_prefix === '' && function_exists('WWA_is_rest') && WWA_is_rest()){
                $session_prefix = isset($_SERVER['SessionPrefix']) ? $_SERVER['SessionPrefix'] : (isset($_SERVER['HTTP_SESSIONPREFIX']) ? $_SERVER['HTTP_SESSIONPREFIX'] : '');
            }
            $session_prefix = sanitize_text_field(wp_unslash($session_prefix));
            if( $session_prefix == '' ) {
                $ip = '';
                if(!empty($_SERVER['HTTP_CLIENT_IP'])){
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($_SERVER['REMOTE_ADDR'])){
                    $ip = $_SERVER['REMOTE_ADDR'];
                }
                $ip = filter_var($ip, FILTER_VALIDATE_IP);
                $ip = $ip ?: 'none';
                $agent = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
                $session_prefix = md5(time() . $ip . $agent . '-' . wp_rand(100,999) . '-' . wp_rand(100,999));
                @setcookie('_s_prefix', $session_prefix, time()+315360000, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            }
            return $session_prefix;
        }
        // 防止缓存插件更换过 $wpdb，所以自己重新初始化
        private static function int_wpdb() {
            global $wpcom_wpdb;
            if ( isset( $wpcom_wpdb ) ) return false;
            $dbuser     = defined( 'DB_USER' ) ? DB_USER : '';
            $dbpassword = defined( 'DB_PASSWORD' ) ? DB_PASSWORD : '';
            $dbname     = defined( 'DB_NAME' ) ? DB_NAME : '';
            $dbhost     = defined( 'DB_HOST' ) ? DB_HOST : '';

            $wpcom_wpdb = new \wpdb( $dbuser, $dbpassword, $dbname, $dbhost );
        }
    }

    if( !class_exists('\WPCOM_Session') ) class_alias(Session::class, 'WPCOM_Session');
}