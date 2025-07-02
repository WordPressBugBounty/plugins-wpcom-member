<?php
namespace WPCOM\Themer;

defined( 'ABSPATH' ) || exit;

if( !class_exists( Session::class ) ) {
    class Session{
        private static $table = 'wpcom_sessions';
        public static function set($name, $value, $expired=''){
            global $wpdb;
            $table = $wpdb->prefix . self::$table;
            $session = [];
            if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
            $session['name'] = $name;
            $session['value'] = $value;
            $session['expired'] = $expired && is_numeric($expired) ? $expired : 900;
            $session['time'] = current_time( 'mysql', 1 );
            $query = $wpdb->prepare("SELECT * FROM `$table` WHERE name = %s", $name);
            $option = @$wpdb->get_row( $query );
            if($option && isset($option->value)) {
                unset($session['name']);
                $res = $wpdb->update($table, $session, ['name' => $name]);
            }else{
                $res = $wpdb->insert($table, $session);
            }
            return $res;
        }

        public static function get($name){
            global $wpdb;
            $table = $wpdb->prefix . self::$table;
            if($name) {
                if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                $query = $wpdb->prepare("SELECT * FROM `$table` WHERE name = %s", $name);
                $row = $wpdb->get_row($query);
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
            global $wpdb;
            $table = esc_sql($wpdb->prefix . self::$table);
            if( $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table ) {
                $array = [];
                if($id) $array['ID'] = absint($id);
                if($name) {
                    $name = sanitize_text_field($name);
                    if(!preg_match('/^_/i', $name)) $name = self::session_prefix() . '_' . $name;
                    $array['name'] = $name;
                }
                @$wpdb->delete($table, $array);
            }
        }

        public static function cron(){
            global $wpdb;
            $table = esc_sql($wpdb->prefix . self::$table);
            if( $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table ) {
                $wpdb->query( $wpdb->prepare("DELETE FROM `$table` WHERE UNIX_TIMESTAMP(time) + expired < %d", current_time( 'timestamp', 1 ) ) );
            }
        }

        public static function init_database(){
            global $wpdb;
            $table = $wpdb->prefix . self::$table;
            if( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ){
                $charset_collate = $wpdb->get_charset_collate();
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

                // 缓存表
                $create_sql = "CREATE TABLE $table (".
                    "ID BIGINT(20) NOT NULL auto_increment,".
                    "name VARCHAR(128) NOT NULL,".
                    "value longtext NOT NULL,".
                    "expired text,".
                    "time datetime,".
                    "PRIMARY KEY (ID),".
                    "UNIQUE KEY name (name)) $charset_collate;";

                dbDelta( $create_sql );
            }else{
                self::upgrade_database();
            }
        }

        public static function upgrade_database(){
            global $wpdb;
            $table = $wpdb->prefix . self::$table;

            // 检查是否存在 name 字段为 TEXT 类型
            $column = $wpdb->get_row("SHOW COLUMNS FROM `$table` LIKE 'name'");
            if( $column && strtolower($column->Type) === 'text' ){
                // 检查是否已有重复的 name 值
                $duplicates = $wpdb->get_results("SELECT name, COUNT(*) as count FROM `$table` GROUP BY name HAVING count > 1");

                if( $duplicates && count($duplicates) ){
                    // 可以选择保留最新一条记录
                    foreach( $duplicates as $dup ){
                        $rows = $wpdb->get_results( $wpdb->prepare("SELECT ID FROM `$table` WHERE name = %s ORDER BY time DESC", $dup->name) );
                        $ids_to_keep = array_shift($rows); // 最新的
                        $ids_to_delete = array_map(function($r){ return $r->ID; }, $rows);
                        if( $ids_to_delete ){
                            $in = implode(',', array_map('intval', $ids_to_delete));
                            $wpdb->query("DELETE FROM `$table` WHERE ID IN ($in)");
                        }
                    }
                }

                // 修改字段类型 + 添加唯一索引
                $wpdb->query("ALTER TABLE `$table` MODIFY `name` VARCHAR(128) NOT NULL");
                $indexes = $wpdb->get_results("SHOW INDEX FROM `$table` WHERE Key_name = 'name'");
                if( !$indexes || count($indexes) == 0 ){
                    $wpdb->query("ALTER TABLE `$table` ADD UNIQUE KEY `name` (`name`)");
                }
            }
        }

        public static function session_prefix(){
            $session_prefix = isset($_COOKIE['_s_prefix']) ? $_COOKIE['_s_prefix'] : '';
            if($session_prefix === '' && function_exists('WWA_is_rest') && WWA_is_rest()){
                $session_prefix = isset($_SERVER['SessionPrefix']) ? $_SERVER['SessionPrefix'] : (isset($_SERVER['HTTP_SESSIONPREFIX']) ? $_SERVER['HTTP_SESSIONPREFIX'] : '');
            }
            $session_prefix = sanitize_text_field(wp_unslash($session_prefix));
            if( $session_prefix === '' ) {
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
    }

    if( !class_exists('\WPCOM_Session') ) class_alias(Session::class, 'WPCOM_Session');

    add_action('wpcom_themer_maybe_updated', function(){
        Session::init_database();
    });
    add_action('after_setup_theme', function(){
        Session::session_prefix();
    });
}