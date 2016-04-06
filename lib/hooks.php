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

	// Allow add-ons to opt-out
	$update_activity = apply_filters( 'it_exchange_abandoned_carts_bump_active_shopper', true, $customer, $cart_data );
	if ( ! $update_activity )
		return;

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
 * This function processes the qualified shoppers queue
 *
 * If shopper is still active (based on timeout settings) they are ignored
 * If shopper has abandoned cart (based on timeout settings) we send an abanonded cart email to them
*/
function it_exchange_abandoned_carts_process_qualified_shoppers_queue() {
    $now                      = time();
    $qualified_shoppers_queue = it_exchange_abandoned_carts_get_qualified_shoppers_queue();
    $cart_abandonment_emails  = it_exchange_abandoned_carts_get_abandonment_emails();
	
	IT_Exchange_Abandoned_Cart_Emails::batch();

    // Loop through all of our active carts
    foreach( $qualified_shoppers_queue as $user_id => $last_active ) {
        // If user has unsubscribed, don't send email @todo Maybe remove from qualified shoppers queue
        $unsubscribed = get_user_meta( $user_id, '_it_exchange_unsubscribed_from_abandoned_cart_emails', true );
        if ( ! empty( $unsubscribed ) )
            return;

        // Calculate how log it has been since this use was last active
        $time_since_last_activity = ($now - $last_active );

        // Loop through our possible emails sorted by last email to first
        foreach( $cart_abandonment_emails as $email_id => $props ) {
            // Test to see if last email was beyond the timeframe for this email
            if ( $time_since_last_activity >= $props['time'] ) {
                // Test to make sure the abandoned cart exists and has not received this email
                if ( ! $abandoned_cart = it_exchange_get_active_abandoned_cart_for_user( $user_id ) ) {
                    // Grab customer's current cached cart id
                    $cached_cart       = it_exchange_get_cached_customer_cart( $user_id );
                    $cached_cart_id    = empty( $cached_cart['cart_id'][0] ) ? false : $cached_cart['cart_id'][0];
                    $cached_cart_value = it_exchange_get_cart_total( true, array( 'use_cached_customer_cart' => $user_id ) );

                    $abandoned_cart = it_exchange_add_abandoned_cart( $user_id, array( 'cart_id' => $cached_cart_id, 'cart_value' => $cached_cart_value ) );
                }

                // Test to make sure abandoned cart hasn't already sent this email
                $emails_sent = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );

                // Loop through sent emails and make sure that this email hasn't been sent already.
                $email_already_sent = false;
                foreach( (array) $abandoned_cart->emails_sent as $email ) {
                    if ( empty( $email['email_id'] ) )
                        continue;

                    if ( $email['email_id'] == $email_id )
                        $email_already_sent = true;
                }
                // Send it if it hasn't been sent
                if (  empty( $email_already_sent ) ) {
                    it_exchange_abandoned_carts_send_email_for_cart( $abandoned_cart, $email_id );
                }
                break 1;
            }
        }
    }

	IT_Exchange_Abandoned_Cart_Emails::batch( false );
}
add_action( 'it_exchange_abandoned_carts_hourly_event_hook', 'it_exchange_abandoned_carts_process_qualified_shoppers_queue' );

/**
 * Add primary Menu Item
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_add_admin_menu_item() {
	$admin_menu_capability = apply_filters( 'it_exchange_abandoned_carts_admin_menu_cap', 'activate_plugins' );

	/**
	 * Don't need settings yet
	if( ! empty( $_GET['page'] ) && 'it-exchange-abandoned-carts-settings' == $_GET['page'] )
		add_submenu_page( 'it-exchange', __( 'Abandoned Carts Settings', 'LION' ), __( 'Abandoned Carts', 'LION' ), $admin_menu_capability, 'it-exchange-abandoned-carts-settings', 'it_exchange_abandoned_carts_print_settings_page' );
	else
	*/
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
			<div class="no-recovered-carts hidden">
				<p>
				<?php
				$num_abandoned_carts = it_exchange_get_number_of_abandoned_carts();
				if ( empty( $num_abandoned_carts ) ) :
					_e( 'You haven\'t had any abandoned carts yet. Congratulations!', 'LION' );
				else :
					printf( __( 'You have %s abandoned carts but haven\'t recovered any yet. Make sure youre %semails%s are setup correctly!', 'LION' ), $num_abandoned_carts, '<a href="' . admin_url( 'edit.php?post_type=it_ex_abandoned' ) . '">', '</a>' );
				endif;
				?>
				</p>

			</div>
			<canvas id="it-exchange-abandoned-cart-overview-chart"></canvas>
		</div>
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
	/**
	 * Don't need settings yet
	if ( ! empty( $_GET['page'] ) && 'it-exchange-abandoned-carts-settings' == $_GET['page'] )
		$current_tab = 'settings';
	*/
	?>
	<div class="it-exchange-abandoned-carts-dashboard">
		<div class="abandoned-carts-overview">
			<div class="abandoned-carts-overview-items">
				<div class="overview-item overview-item-abandoned">
					<div class="overview-item-value"><?php echo it_exchange_get_number_of_abandoned_carts(); ?></div>
					<div class="overview-item-title">Abandoned Carts</div>
				</div>
				<div class="overview-item overview-item-recovered-carts">
					<div class="overview-item-value"><?php echo it_exchange_get_number_of_recovered_abandon_carts(); ?></div>
					<div class="overview-item-title">Recovered Carts</div>
				</div>
				<div class="overview-item overview-item-recovered-revenue">
					<div class="overview-item-value"><?php echo it_exchange_get_value_of_recovered_abandon_carts(); ?></div>
					<div class="overview-item-title">Recovered Revenue</div>
				</div>
				<div class="overview-item overview-item-average-value">
					<div class="overview-item-value"><?php echo it_exchange_get_average_value_of_recovered_abandon_carts(); ?></div>
					<div class="overview-item-title">Average Recovered Cart Value</div>
				</div>
			</div>
		</div>
		<div class="abdandoned-carts-nav">
			<h3 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo ($current_tab == 'dashboard' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'admin.php?page=it-exchange-abandoned-carts-dashboard' ); ?>"><?php _e( 'Dashboard', 'LION' ); ?></a>
			<a class="nav-tab <?php echo ($current_tab == 'carts' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandoned' ); ?>"><?php _e( 'Carts', 'LION' ); ?></a>
			<a class="nav-tab <?php echo ($current_tab == 'emails' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'edit.php?post_type=it_ex_abandond_email' ); ?>"><?php _e( 'Email Templates', 'LION' ); ?></a>
			<?php
			/**
			 * Don't need settings yet
			<a class="nav-tab <?php echo ($current_tab == 'settings' ) ? 'nav-tab-active' : '';?>" href="<?php echo admin_url( 'admin.php?page=it-exchange-abandoned-carts-settings' ); ?>"><?php _e( 'Settings', 'LION' ); ?></a>
			*/ ?>
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

/**
 * Register a click through for an email.
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_handle_email_clickthrough() {
	if ( empty( $_GET['iterab'] ) )
		return;

	$parts    = explode( '-', $_GET['iterab'] );
	$email_id = substr( $parts[1], 7, -5 );
	$cart_id  = substr( $parts[3], 0, -3 );

	// Mark as clicked through
	it_exchange_abandoned_carts_mark_email_clicked_through( $email_id, $cart_id );

	// Redirect to the cart
	it_exchange_redirect( it_exchange_get_page_url( 'cart' ), 'it-exchange-abandoned-email-clickthrough', array( 'email_id' => $email_id, 'cart_id' => $cart_id ) );
	die();
}
add_action( 'wp', 'it_exchange_abandoned_carts_handle_email_clickthrough' );

/**
 * Mark an abandoned cart as lost if it is emptied
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_mark_abandoned_cart_as_lost( $session_data_before_emptied ) {
	$cart_id = empty( $session_data_before_emptied['cart_id'][0] ) ? false : $session_data_before_emptied['cart_id'][0];
	if ( empty( $cart_id ) )
		return;

	$carts = it_exchange_get_abandoned_carts( array( 'cart_id' => $cart_id, 'cart_status' => 'any' ) );
	foreach( $carts as $cart ) {
		if ( ! empty( $cart->cart_id ) && $cart->cart_id == $cart_id ) {
			update_post_meta( $cart->ID, '_it_exchange_abandoned_cart_cart_status', 'lost' );
			break;
		}
	}
}
/** @todo  Introduce more statuses in later version
add_action( 'it_exchange_before_empty_shopping_cart', 'it_exchange_mark_abandoned_cart_as_lost', 10, 1 );
*/

/**
 * Mark an abandoned cart as recovered on checkout
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_maybe_mark_abandoned_cart_as_recovered( $transaction_id ) {
    $transaction = it_exchange_get_transaction( $transaction_id );
	$cart_id     = empty( $transaction->cart_details->cart_id ) ? false : $transaction->cart_details->cart_id;
	$user_id     = empty( $transaction->customer_id ) ? false : $transaction->customer_id;

	$abandoned_carts = it_exchange_get_abandoned_carts( array( 'cart_id' => $cart_id ) );
	$abandoned_cart  = empty( $abandoned_carts[0] ) ? false : $abandoned_carts[0];
	if ( ! $abandoned_cart )
		return;

	if ( $abandoned_cart->cart_status != 'recovered' ) {
		update_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_cart_status', 'recovered' );

		// If recovered source is an email, bump the number of converted for that email.
		$source = empty( $abandoned_cart->conversion_source ) ? false : $abandoned_cart->conversion_source;
		$current_count = get_post_meta( $source, '_it_exchange_abandoned_cart_emails_recovered', true );
		update_post_meta( $source, '_it_exchange_abandoned_cart_emails_recovered', ( $current_count + 1 ) );
	}
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_maybe_mark_abandoned_cart_as_recovered', 10, 1 );

/**
 * Returns JSON formatted data for wp-ajax
 *
 * @since 1.0.0
 *
 * @return string
*/
function it_exchange_get_abandoned_carts_stats() {
	$stat_type = empty( $_POST['iteac_stat'] ) ? false : $_POST['iteac_stat'];
	if ( empty( $stat_type ) )
		return;

	if ( 'carts' == $stat_type ) {

		$raw_stats = it_exchange_abandoned_carts_get_abandoned_carts_by_day();
		$raw_stats = array_reverse( $raw_stats, true );

		$carts = new stdClass();
		$carts->fillColor = '#d1ebb0';
		$carts->strokeColor = '#89c43d';
		$carts->pointColor = '#89c43d';
		$carts->pointStrokeColor = '#ffffff';
		$carts->data = array_values( $raw_stats );

		$stats = new stdClass();
		$stats->labels   = array_keys( $raw_stats );
		$stats->datasets = array( $carts );

		die( json_encode( $stats ) );
	}
}
add_action( 'wp_ajax_ithemes_exchange_abandoned_carts_data', 'it_exchange_get_abandoned_carts_stats' );

/**
 * Modify the available template paths.
 *
 * @since 1.3
 *
 * @param array $paths
 *
 * @return array
 */
function it_exchange_abandoned_carts_modify_template_paths( $paths = array() ) {

	$paths[] = dirname( __FILE__ ) . '/templates/';

	return $paths;
}

add_filter( 'it_exchange_possible_template_paths', 'it_exchange_abandoned_carts_modify_template_paths' );

/**
 * Globalize context for the theme API.
 * 
 * @since 1.3.0
 * 
 * @param array $context
 */
function it_exchange_abandoned_carts_globalize_context( $context ) {
	
	if ( ! empty( $context['abandoned-cart'] ) ) {
		$GLOBALS['it_exchange']['abandoned_cart'] = $context['abandoned-cart'];
	}
}

add_action( 'it_exchange_email_template_globalize_context', 'it_exchange_abandoned_carts_globalize_context' );

/**
 * Register our shortcodes for the abandoned cart emails
 *
 * @since 1.0.0
 *
 * @param array $atts
 * @return string
*/
function it_exchange_abandoned_carts_do_email_shortcodes( $atts=array() ) {
	$display = empty( $atts['display'] ) ? false : $atts['display'];
	$return = '';
	$subs = $GLOBALS['it_exchange']['abandoned_carts']['shortcode_data'];
	switch ( $display ) {
		case 'customer_name' :
			$return = empty( $subs['customer_name'] ) ? __( 'Valued Customer', 'LION' ) : $subs['customer_name'];
			break;
		case 'customer_first_name' :
			$return = empty( $subs['customer_first_name'] ) ? __( 'Valued Customer', 'LION' ) : $subs['customer_first_name'];
			break;
		case 'customer_last_name' :
			$return = empty( $subs['customer_last_name'] ) ? '' : $subs['customer_last_name'];
			break;
		case 'cart_link_href' :
			$return = empty( $subs['cart_link_href'] ) ? '' : $subs['cart_link_href'];
			break;
		case 'cart_products' :
			if ( empty( $subs['cart_products'] ) )
				break;
			foreach( (array) $subs['cart_products'] as $cart_product ) {
				$return .= $cart_product['title'] . ': ' . $cart_product['price'] . '<br />';
			}
			break;
		case 'cart_value' :
			$return = empty( $subs['cart_value'] ) ? '' : $subs['cart_value'];
			break;
		case 'store_name' :
			$options = it_exchange_get_option( 'settings_general' );
			$return = empty( $options['company-name'] ) ? '' : $options['company-name'];
			break;
	}
	return $return;
}
add_shortcode( 'exchange-abandoned-carts', 'it_exchange_abandoned_carts_do_email_shortcodes' );

/**
 * Add Example email if not already created
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_cart_emails_create_example_email() {
	$emails = it_exchange_abandoned_carts_get_abandonment_emails( array( 'post_status' => 'any' ) );
	if ( ! empty( $emails ) )
		return;

	$r = it_exchange_email_notifications()->get_replacer();

	$args = array(
		'post_type'    => 'it_ex_abandond_email',
		'post_status'  => 'draft',
		'post_title'   => __( 'You Forgot Something Awesome!', 'LION' ),
		'post_content' => "Hi {$r->format_tag( 'first_name' )}
<p>Your shopping cart at {$r->format_tag( 'company_name' )} has been reserved and is waiting for your return!</p>
<p>Is there anything holding you back from making your purchase today? We're here to help. If you have any questions, just reply back to this email.</p>
<p>Your Friends at {$r->format_tag( 'company_name' )}</p>"
	);

	if ( $id = wp_insert_post( $args ) ) {
		update_post_meta( $id, '_it_exchange_abandoned_cart_emails_scheduling_unix', 3600 );
		update_post_meta( $id, '_it_exchange_abandoned_cart_emails_scheduling', array( 'int' => 1, 'unit' => 'hours' ) );
	}
}

/**
 * Creates the demo email on plugin activation
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_maybe_create_demo_abandon_email() {
	if ( false != get_option( 'it-exchange-create-abandoned-cart-demo-email' ) ) {
		it_exchange_abandoned_cart_emails_create_example_email();
		delete_option( 'it-exchange-create-abandoned-cart-demo-email' );
	}
}
add_action( 'admin_init', 'it_exchange_maybe_create_demo_abandon_email' );
