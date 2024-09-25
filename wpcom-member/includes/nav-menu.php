<?php
namespace WPCOM\Member {
	class Nav_Menu {
		protected $theme;
		function __construct(){
			if(!class_exists('\WPCOM')){
				add_action( 'admin_init', array( $this, 'admin_init' ) );
				add_filter( 'wp_get_nav_menu_items',array( $this, 'nav_menu_items' ), 50);
			}
			remove_filter('wpcom_is_login', 'wpcom_add_profile_menus');
			add_filter('wpcom_is_login', array($this, 'add_profile_menus_info'));
			add_filter('wpcom_profile_menus', array($this, 'add_profile_menus'));
		}

		public function admin_init(){
			add_meta_box( 'wpcom_member_user_info', '用户登录信息', array( $this, 'user_info' ), 'nav-menus', 'side', 'high' );
		}

		public function user_info() {
			global $_nav_menu_placeholder, $nav_menu_selected_id;
			$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
			?>
			<div id="posttype-user-info" class="posttypediv">
				<div id="tabs-panel-user-info" class="tabs-panel tabs-panel-active">
					<ul id="user-info-checklist" class="categorychecklist form-no-clear">
						<li>
							<label class="menu-item-title">
								<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-object-id]" value="-1"> 用户注册登录信息
							</label>
							<input type="hidden" class="menu-item-type" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
							<input type="hidden" class="menu-item-title" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-title]" value="注册登录">
							<input type="hidden" class="menu-item-url" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-url]" value="#wpcom_user_info">
						</li>
					</ul>
				</div>
				<p class="button-controls">
					<span class="add-to-menu">
						<input type="submit" <?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="添加到菜单" name="add-post-type-menu-item" id="submit-posttype-user-info">
						<span class="spinner"></span>
					</span>
				</p>
				<script>
					jQuery(function( $ ) {$( '#update-nav-menu' ).on('click', function(e){
						if ( e.target && e.target.className && -1 != e.target.className.indexOf( 'item-edit' ) ) {
							$( "input[value='#wpcom_user_info'][type=text]" ).parent().parent().parent().each(function(){
								let $el = $(this).closest('.menu-item-settings');
								if($el.find('p.description-inited').length === 0) {
									$el.find('> p.description').hide();
									$el.prepend('<p class="description description-inited"><b>功能说明：</b></p><p>在菜单里面显示用户中心插件注册的登录入口，已登录后显示账号设置入口</p>');
								}
							})
						}
					})});
				</script>
			</div>
			<?php
		}

		public function nav_menu_items($items){
			global $pagenow;
			if(!empty($items) && $pagenow === 'index.php' && !is_customize_preview()){
				$options = $GLOBALS['wpmx_options'];
				$user_id = get_current_user_id();
				$menus = array();
				foreach($items as $item){
					if($item && isset($item->url) && $item->url === '#wpcom_user_info'){
						if($user_id){
							$current_user = wp_get_current_user();
							$avatar = get_avatar_url( $user_id, 60 );
							$display_name = $current_user->display_name;
							$item->title = ($avatar ? '<img class="user-avatar" src="'.esc_url($avatar).'" alt="'.esc_attr($display_name).'">' : '') . $display_name;
							$item->url = wpcom_account_url();
							$item->classes[] = 'wpcom-user-info';
							$menus[] = $item;

							$user_menus = array();
							$show_profile = apply_filters( 'wpcom_member_show_profile' , true );
							if($show_profile) {
								$user_menus[] = array(
									'url' => get_author_posts_url( $current_user->ID ),
									'title' => __('Profile', 'wpcom')
								);
							}

							if(isset($options['profile_menu_url']) && isset($options['profile_menu_title']) && $options['profile_menu_url']){
								$i=1;
								foreach($options['profile_menu_url'] as $menu){
									if($menu && $options['profile_menu_title'][$i-1]) {
										$user_menus[] = array(
											'url' => esc_url($menu),
											'title' => $options['profile_menu_title'][$i-1]
										);
									}
									$i++;
								}
							}

							$user_menus[] = array(
								'url' => $item->url,
								'title' => __('Account', 'wpcom')
							);
							$user_menus[] = array(
								'url' => wp_logout_url(),
								'title' => __( 'Logout', 'wpcom' )
							);
							$user_menus = apply_filters('wpcom_profile_menus', $user_menus);
							foreach($user_menus as $x => $uitem){
								$uitem['ID'] = $item->ID .'-' . $x;
								$uitem['menu_order'] = $item->menu_order * 999 + $x;
								$uitem['menu_item_parent'] = $item->ID;
								$uitem['classes'] = array();
								$uitem['target'] = $item->target;
								$uitem['db_id'] = $uitem['ID'];
								$uitem['xfn'] = '';
								$uitem['type'] = 'custom';
								$uitem['object'] = 'custom';
								$uitem['object_id'] = $uitem['ID'];
								$uitem['type_label'] = $item->type_label;
								$uitem['post_parent'] = 0;
								$uitem['post_type'] = 'nav_menu_item';
								$menus[] = $uitem;
							}
						}else{
							$menus[] = $item;
							$menus[] = $item;
						}
					}else{
						$menus[] = $item;
					}
				}
				$items = json_decode(json_encode($menus));
				$checked = 0;
				foreach($items as $i => $item){
					if($item && isset($item->url) && $item->url === '#wpcom_user_info'){
						if(!$user_id){
							if($checked){
								$item->url = wp_registration_url();
								$item->title = __('Sign up', 'wpcom');
								$item->ID = $item->ID . '-register';
								$item->menu_order += 1;
								$items[$i] = $item;
							}else{
								$item->url = wp_login_url();
								$item->title = __('Sign in', 'wpcom');
								$item->ID = $item->ID . '-login';
								$items[$i] = $item;
							}
							$checked = 1;
						}
					}else{
						$items[$i] = $item;
					}
				}
			}
			return $items;
		}

		function add_profile_menus($menus){
			if($menus && !empty($menus)){
				$options = $GLOBALS['wpmx_options'];
				$current_user = wp_get_current_user();
				if( isset($options['member_messages']) && $options['member_messages']=='1' &&
					(
						( defined('FRAMEWORK_PATH') && file_exists(FRAMEWORK_PATH . '/includes/messages.php') )
						|| ( defined('WPCOM_MP_DIR') && file_exists(WPCOM_MP_DIR . 'includes/messages.php') )
					)
				) {
					$unread_messages = apply_filters('wpcom_unread_messages_count', 0, $current_user->ID);
					$menu = array(
						'url' => wpcom_subpage_url('messages'),
						'title' => __('Messages', 'wpcom') . ($unread_messages ? '<span class="num-count">'.$unread_messages.'</span>' : '')
					);
					array_splice($menus, -2, 0, array($menu));
				}

				if( isset($options['member_notify']) && $options['member_notify']=='1' &&
					(
						( defined('FRAMEWORK_PATH') && file_exists(FRAMEWORK_PATH . '/includes/notifications.php') )
						|| ( defined('WPCOM_MP_DIR') && file_exists(WPCOM_MP_DIR . 'includes/notifications.php') )
					)
				) {
					$unread_messages = apply_filters('wpcom_unread_notifications_count', 0, $current_user->ID);
					$menu = array(
						'url' => wpcom_subpage_url('notifications'),
						'title' => __('Notifications', 'wpcom') . ($unread_messages ? '<span class="num-count">'.$unread_messages.'</span>' : '')
					);
					array_splice($menus, -2, 0, array($menu));
				}
			}
			return $menus;
		}

		function add_profile_menus_info($res){
			if($res && isset($res['menus']) && !empty($res['menus'])){
				$options = $GLOBALS['wpmx_options'];
				$current_user = wp_get_current_user();
				if( isset($options['member_messages']) && $options['member_messages']=='1' &&
					(
						( defined('FRAMEWORK_PATH') && file_exists(FRAMEWORK_PATH . '/includes/messages.php') )
						|| ( defined('WPCOM_MP_DIR') && file_exists(WPCOM_MP_DIR . 'includes/messages.php') )
					)
				) {
					$res['messages'] = apply_filters('wpcom_unread_messages_count', 0, $current_user->ID);
				}
				if( isset($options['member_notify']) && $options['member_notify']=='1' &&
					(
						( defined('FRAMEWORK_PATH') && file_exists(FRAMEWORK_PATH . '/includes/notifications.php') )
						|| ( defined('WPCOM_MP_DIR') && file_exists(WPCOM_MP_DIR . 'includes/notifications.php') )
					)
				) {
					$res['notifications'] = apply_filters('wpcom_unread_notifications_count', 0, $current_user->ID);
				}
			}
			return $res;
		}
	}

	new Nav_Menu();
}

namespace {
	add_action('init', function() {
		wp_register_script('wpcom-member-login', WPMX_URI . 'js/blocks.js', array('wp-blocks', 'wp-element', 'wp-components'), WPMX_VERSION, true);
		register_block_type('wpcom/login', array(
			'editor_script' => 'wpcom-member-login',
			'uses_context' => [
				"textColor",
				"customTextColor",
				"backgroundColor",
				"customBackgroundColor",
				"overlayTextColor",
				"customOverlayTextColor",
				"overlayBackgroundColor",
				"customOverlayBackgroundColor",
				"fontSize",
				"customFontSize",
				"showSubmenuIcon",
				"maxNestingLevel",
				"openSubmenusOnClick",
				"style"
			],
			'render_callback' => function ($attributes, $content, $block) {
				$current_user = wp_get_current_user();
				$menu = '';
				if($current_user && isset($current_user->ID) && $current_user->ID){
					$avatar = get_avatar_url($current_user->ID, 60 );
					$display_name = $current_user->display_name;
					$label = ($avatar ? '<img class="user-avatar" src="'.esc_url($avatar).'" alt="'.esc_attr($display_name).'">' : '') . $display_name;
					$menu_block = [
						'blockName' => 'core/navigation-submenu',
						'attrs' => [
							'label' => $label,
							'url' => wpcom_account_url(),
							'kind' => 'custom'
						],
						'innerBlocks' => [],
						'innerContent' => []
					];

					$user_menus = array();
					$show_profile = apply_filters('wpcom_member_show_profile', true);
					if ($show_profile) {
						$user_menus[] = array(
							'url' => get_author_posts_url($current_user->ID),
							'title' => __('Profile', 'wpcom')
						);
					}

					if (isset($options['profile_menu_url']) && isset($options['profile_menu_title']) && $options['profile_menu_url']) {
						$i = 1;
						foreach ($options['profile_menu_url'] as $menu) {
							if ($menu && $options['profile_menu_title'][$i - 1]) {
								$user_menus[] = array(
									'url' => esc_url($menu),
									'title' => $options['profile_menu_title'][$i - 1]
								);
							}
							$i++;
						}
					}

					$user_menus[] = array(
						'url' => $menu_block['attrs']['url'],
						'title' => __('Account', 'wpcom')
					);
					$user_menus[] = array(
						'url' => wp_logout_url(),
						'title' => __('Logout', 'wpcom')
					);
					$user_menus = apply_filters('wpcom_profile_menus', $user_menus);
					foreach ($user_menus as $uitem) {
						$menu_block['innerBlocks'][] = new \WP_Block([
								'blockName' => 'core/navigation-link',
								'attrs' => [
									'label' => $uitem['title'],
									'url' => $uitem['url'],
									'kind' => 'custom'
								]
							]);
						$menu_block['innerContent'][] = [];
					}
					$block->inner_blocks = $menu_block['innerBlocks'];
					$registry = \WP_Block_Type_Registry::get_instance();
					$block->block_type = $registry->get_registered($menu_block['blockName']);
					$menu = render_block_core_navigation_submenu($menu_block['attrs'], $content, $block);
				} else {
					$menu .= render_block(array(
						'blockName' => 'core/navigation-link',
						'attrs' => [
							'label' => __('Sign in', 'wpcom'),
							'url' => wp_login_url(),
							'kind' => 'custom'
						],
					));
					$menu .= render_block(array(
						'blockName' => 'core/navigation-link',
						'attrs' => [
							'label' => __('Sign up', 'wpcom'),
							'url' => wp_registration_url(),
							'kind' => 'custom'
						],
					));
				}
				return $menu;
			})
		);
	});
}