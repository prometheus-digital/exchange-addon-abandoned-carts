<?php
/** 
 * Whenever a cart is cached, confirm it has products and add, update, or delete its status
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_bump_active_shopper( $customer, $cart_data ) {
	// If user deleted cart, remove them as a qualified shopper
	if ( empty( $cart_data['products'] ) ) {
		it_exchange_abandoned_carts_delete_last_qualified_activity_for_user( $customer->id );
		return;
	}

	// Update their time stamp as a qualified shopper
	it_exchange_abandoned_carts_update_last_qualified_activity_for_user( $customer->id );
}
add_action( 'it_exchange_cache_customer_cart', 'it_exchange_abandoned_carts_bump_active_shopper', 10, 2 );

/**
 * Register scripts
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_register_scripts( $hook ) {
	$screen = get_current_screen();
	// If we're on the screen, enqueue our scripts. It is registered by Exchange in the admin
	if ( ! empty( $screen->id ) && 'exchange_page_it-exchange-abandoned-carts-dashboard' == $screen->id ) {
		// ChartJS
		wp_enqueue_script( 'ithemes-chartjs' );
		wp_enqueue_script( 'ithems-exchange-abandoned-carts-dashboard', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/admin-dashboard.js' );
	}

	// All emails screen
	if ( ! empty( $screen->id ) && ( 'edit-it_ex_abandond_email' == $screen->id || 'it_ex_abandond_email' == $screen->id ) )
		wp_enqueue_script( 'ithems-exchange-abandoned-carts-dashboard', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/admin-emails.js' );

	// Any of our admin pages
	$valid_ids = array(
		'edit-it_ex_abandoned',
		'edit-it_ex_abandond_email',
		'exchange_page_it-exchange-abandoned-carts-dashboard',
		'exchange_page_it-exchange-abandoned-carts-settings',
	);
	if ( ! empty( $screen->id ) && in_array( $screen->id, $valid_ids ) )
		wp_enqueue_style( 'it-exchange-abandoned-carts-admin', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/css/admin.css' );

}
add_action( 'admin_print_scripts', 'it_exchange_abandoned_carts_register_scripts' );

/**
 * Add primary Menu Item
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_add_admin_menu_item() {
	$admin_menu_capability = apply_filters( 'it_exchange_abandoned_carts_admin_menu_cap', 'activate_plugins' );

	if( ! empty( $_GET['page'] ) && 'it-exchange-abandoned-carts-settings' == $_GET['page'] )
		add_submenu_page( 'it-exchange', __( 'Abandoned Carts Settings', 'LION' ), __( 'Abandoned Carts', 'LION' ), $admin_menu_capability, 'it-exchange-abandoned-carts-settings', 'it_exchange_abandoned_carts_print_settings_page' );
	else
		add_submenu_page( 'it-exchange', __( 'Abandoned Carts Dashboard', 'LION' ), __( 'Abandoned Carts', 'LION' ), $admin_menu_capability, 'it-exchange-abandoned-carts-dashboard', 'it_exchange_abandoned_carts_print_dashboard_page' );
}
add_action( 'admin_menu', 'it_exchange_abandoned_carts_add_admin_menu_item' );

/**
 * Print the dashboard page
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_print_dashboard_page() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Abandoned Carts Dashboad', 'LION' ); ?></h2>
		<?php do_action( 'views_edit-it_ex_abandond_carts_dashboard', false ); ?>

		<div class="overview-chart clear">
			<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
			<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
		</div>
		<div class="overview-chart">
			<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
			<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
		</div>
		<div class="overview-chart">
			<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
			<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
		</div>
		<div class="overview-chart">
			<h3><?php _e( 'Recovered Carts', 'LION' ); ?></h3>
			<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
		</div>
	</div>
	<?php
}


/**
 * Print the settings page
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_print_settings_page() {
	?>
	<div class="wrap">
		<h2><?php _e( 'Abandoned Carts Settings', 'LION' ); ?></h2>
		<?php do_action( 'views_edit-it_ex_abandond_carts_settings', false ); ?>
		<h3>-- Settings will go here --</h3>
	</div>
	<?php
}

/**
 * Add content to top of Abanded Carts Admin Page
 *
 * @since 1.0.0
 *
 * @return object
*/
function it_exchange_abdandoned_carts_insert_custom_dashboard( $incoming_from_wp_filter ) {
	$current_tab = 'dashboard';
	if ( ! empty( $_GET['post_type'] ) && 'it_ex_abandoned' == $_GET['post_type'] )
		$current_tab = 'carts';
	if ( ! empty( $_GET['post_type'] ) && 'it_ex_abandond_email' == $_GET['post_type'] )
		$current_tab = 'emails';
	if ( ! empty( $_GET['page'] ) && 'it-exchange-abandoned-carts-settings' == $_GET['page'] )
		$current_tab = 'settings';
	?>
	<div class="it-exchange-abandoned-carts-dashboard">
		<div class="abandoned-carts-overview">
			<div class="abandoned-carts-overview-items">
				<div class="overview-item overview-item-recovered-carts">
					<div class="overview-item-value">27</div>
					<div class="overview-item-title">Recovered Carts</div>
				</div>
				<div class="overview-item overview-item-recovered-revenue">
					<div class="overview-item-value">$4,360.20</div>
					<div class="overview-item-title">Recovered Revenue</div>
				</div>
				<div class="overview-item overview-item-conversion">
					<div class="overview-item-value">15%</div>
					<div class="overview-item-title">Recovered Revenue</div>
				</div>
				<div class="overview-item overview-item-average-value">
					<div class="overview-item-value">$161.48</div>
					<div class="overview-item-title">Average Recovered Value</div>
				</div>
			</div>
		</div>
		<div class="abdandoned-carts-nav">
			<h3 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo ($current_tab == 'dashboard' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'admin.php?page=it-exchange-abandoned-carts-dashboard' ); ?>"><?php _e( 'Dashboard', 'LION' ); ?></a>
			<a class="nav-tab <?php echo ($current_tab == 'carts' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandoned' ); ?>"><?php _e( 'Carts', 'LION' ); ?></a>
			<a class="nav-tab <?php echo ($current_tab == 'emails' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandond_email' ); ?>"><?php _e( 'Email Templates', 'LION' ); ?></a>
			<a class="nav-tab <?php echo ($current_tab == 'settings' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'admin.php?page=it-exchange-abandoned-carts-settings' ); ?>"><?php _e( 'Settings', 'LION' ); ?></a>
			</h3>
		</div>

	</div>
	<a class="button button-primary it-exchange-abandoned-carts-add-new-email-template hide-if-js" href="<?php echo admin_url('post-new.php?post_type=it_ex_abandond_email'); ?>"><?php _e( 'Add New Email Template', 'LION' ); ?></a>
	<?php
	return $incoming_from_wp_filter;
}
add_filter( 'views_edit-it_ex_abandoned', 'it_exchange_abdandoned_carts_insert_custom_dashboard' );
add_filter( 'views_edit-it_ex_abandond_email', 'it_exchange_abdandoned_carts_insert_custom_dashboard' );
add_filter( 'views_edit-it_ex_abandond_carts_dashboard', 'it_exchange_abdandoned_carts_insert_custom_dashboard' );
add_filter( 'views_edit-it_ex_abandond_carts_settings', 'it_exchange_abdandoned_carts_insert_custom_dashboard' );

/**  
 * Opens the iThemes Exchange Admin Menu when viewing the Add Cart and Email pages
 *
 * @since Changeme
 * @return string
*/
function it_exchange_abandoned_carts_open_exchange_menu_on_post_type_views( $parent_file, $revert=false ) {
	global $submenu_file, $pagenow, $post;

	if ( 'post-new.php' != $pagenow && 'post.php' != $pagenow && 'edit.php' != $pagenow )
		return $parent_file;

	if ( empty( $post->post_type ) || ( 'it_ex_abandoned' != $post->post_type && 'it_ex_abandond_email' != $post->post_type ) )
		return $parent_file;

	$submenu_file = 'it-exchange-abandoned-carts-dashboard';

	// Return it-exchange as the parent (open) menu when on post-new.php and post.php for it_exchange_prod post_types
	return 'it-exchange';
}
add_filter( 'parent_file', 'it_exchange_abandoned_carts_open_exchange_menu_on_post_type_views' );

/**
 * Adds "Back to All Email Templates" link to add/edit email templates
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_add_admin_link_for_all_email_templates() {
	$current_screen = get_current_screen();
	if ( ! empty( $current_screen->id ) && 'it_ex_abandond_email' == $current_screen->id )
		echo '<a class="it-exchange-back-to-all-abandoned-cart-templates h2-add-new hidden" href="' . get_admin_url() . '/edit.php?post_type=it_ex_abandond_email' . '">' . __( '&#8592; Back to all email templates', 'LION' ) . '</a>';
}
add_action( 'admin_footer', 'it_exchange_abandoned_carts_add_admin_link_for_all_email_templates' );

/**
 * Sets content type to HTML for our emails
 *
 * @since 1.0.0
 *
 * @param string $content_type the incoming content type
 * @return string
*/
function it_exchange_abandoned_cart_set_email_content_type( $content_type ) {
	return 'text/html';
}

/**
 * Mark an email as read
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_handle_opened_email_ping() {
	if ( empty( $_GET['it-exchange-cart-summary'] ) )
		return;

	$parts = explode( '-', $_GET['it-exchange-cart-summary'] );
	$email_id = empty( $parts[0] ) ? 0 : $parts[0];
	$cart_id = empty( $parts[1] ) ? 0 : $parts[1];
	if ( ! empty( $email_id ) && ! empty( $cart_id ) )
		it_exchange_abandoned_carts_mark_email_opened( $email_id, $cart_id );
	die();
}
add_action( 'wp', 'it_exchange_abandoned_carts_handle_opened_email_ping' );
