<?php
/**
 * API functions for the Abandoned Cart addon
*/

/**
 * Get IT_Exchange_Abandoned_Carts
 *
 * @since 1.0.0
 * @return array  an array of IT_Exchange_Abandoned_Cart objects
*/
function it_exchange_get_abandoned_carts( $args=array() ) { 
    $defaults = array(
        'cart_status' => 'abandoned',
		'customer'    => false,
    );  
    $args = wp_parse_args( $args, $defaults );

	// Force post type
	$args['post_type'] = 'it_ex_abandoned';

	// Fold in meta_query options
    $args['meta_query'] = empty( $args['meta_query'] ) ? array() : $args['meta_query'];

	// Add cart_status to meta_query if not empty
    if ( ! empty( $args['cart_status'] ) ) { 
        $meta_query = array(
            'key'   => '_it_exchange_abandoned_cart_cart_status',
            'value' => $args['cart_status'],
        );  
        $args['meta_query'][] = $meta_query;
        unset( $args['cart_status'] ); //remove this so it doesn't conflict with the meta query
    }

	// Add cart_id to meta_query if not empty
    if ( ! empty( $args['cart_id'] ) ) { 
        $meta_query = array(
            'key'   => '_it_exchange_abandoned_cart_cart_id',
            'value' => $args['cart_id'],
        );  
        $args['meta_query'][] = $meta_query;
        unset( $args['cart_id'] ); //remove this so it doesn't conflict with the meta query
    }

	// Add customer if not empty
    if ( ! empty( $args['customer'] ) ) { 
        $meta_query = array(
            'key'   => '_it_exchange_abandoned_cart_customer_id',
            'value' => $args['customer'],
            'type'  => 'NUMERIC',
        );
        $args['meta_query'][] = $meta_query;
    }
	unset( $args['customer'] );

    $abandoned_carts = false;
    if ( $abandoned_carts = get_posts( $args ) ) { 
        foreach( $abandoned_carts as $key => $abandoned_cart ) { 
            $abandoned_carts[$key] = it_exchange_get_abandoned_cart( $abandoned_cart );
        }   
    }

    return apply_filters( 'it_exchange_get_abandoned_carts', $abandoned_carts, $args );
}

/**
 * Retreives a abdndoned cart object by passing it the WP post object or post id
 *
 * @since 1.0.0
 * @param  mixed  $post                       post object or post id
 * @return object IT_Exchange_Abandoned_Cart  object for passed post
*/
function it_exchange_get_abandoned_cart( $post ) {
    $abandoned_cart = new IT_Exchange_Abandoned_Cart( $post );
    if ( $abandoned_cart->ID ) {
		$abandoned_cart->customer_id = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_customer_id', true );
		$abandoned_cart->emails_sent = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );
		$abandoned_cart->cart_status = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_cart_status', true );
		$abandoned_cart->cart_id     = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_cart_id', true );
        return apply_filters( 'it_exchange_get_abandoned_cart', $abandoned_cart, $post );
	}
    return apply_filters( 'it_exchange_get_abandoned_cart', false, $post );
}

/**
 * Returns boolean if this is an active cart session
 *
 * @since 1.0.0
*/
function it_exchange_abandoned_carts_is_active_shopper() {
	// We do not start tracking until they log in
	if ( ! it_exchange_get_current_customer() )
		return false;

	// We do not start tracking until they have an item in their cart
	if ( empty( it_exchange_get_cart_products() ) )
		return false;

	// If user is logged in and has an item in their cart, this is an active shopper
	return true;
}

/**
 * Returns the qualified shoppers queue
 *
 * @since 1.0.0
 *
 * @return mixed false or array
*/
function it_exchange_abandoned_carts_get_qualified_shoppers_queue() {
	$plugin_options = it_exchange_get_option( 'addon_abandoned_carts' );
	$qualified_shoppers_queue = empty( $plugin_options['qualified_shoppers'] ) ? array() : $plugin_options['qualified_shoppers'];
	return apply_filters( 'it_exchange_abandoned_carts_get_qualified_shoppers_queue', $qualified_shoppers_queue );
}

/**
 * Updates the qualified shoppers queue
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_update_qualified_shoppers_queue( $new_queue=array() ) {
	$plugin_options = it_exchange_get_option( 'addon_abandoned_carts' );
	$new_queue = (array) $new_queue;
	$new_queue = apply_filters( 'it_exchange_abandoned_carts_update_qualified_shoppers_queue', $new_queue );

	$plugin_options['qualified_shoppers'] = $new_queue;
	it_exchange_save_option( 'addon_abandoned_carts', $plugin_options );
}

/**
 * Removes a user from list of qualified active shoppers
 *
 * @since 1.0.0
 *
 * @param int $customer_id the customer id
 * @return void
*/
function it_exchange_abandoned_carts_delete_last_qualified_activity_for_user( $customer_id ) {
	$queue = it_exchange_abandoned_carts_get_qualified_shoppers_queue();
	if ( isset( $queue[$customer_id] ) ) {
		unset( $queue[$customer_id] );
		it_exchange_abandoned_carts_update_qualified_shoppers_queue( $queue );
	}
}

/**
 * Adds or updates the timestamp for a qualified shopper (a logged in user with items in their cart )
 *
 * @since 1.0.0
 *
 * @param int $customer_id the customer id
 * @return void
*/
function it_exchange_abandoned_carts_update_last_qualified_activity_for_user( $customer_id ) {
	$now   = time();
	$queue = it_exchange_abandoned_carts_get_qualified_shoppers_queue();
	$queue[$customer_id] = $now;
	it_exchange_abandoned_carts_update_qualified_shoppers_queue( $queue );

	// If user has an abandoned cart, mark it as reengaged
	if ( $cart = it_exchange_get_active_abandoned_cart_for_user( $customer_id ) )
		update_post_meta( $cart->ID, '_it_exchange_abandoned_cart_cart_status', 'reengaged' );
}

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
					$cached_cart    = it_exchange_get_cached_customer_cart( $user_id );
					$cached_cart_id = empty( $cached_cart['cart_id'][0] ) ? false : $cached_cart['cart_id'][0];

					$abandoned_cart = it_exchange_add_abondoned_cart( $user_id, array( 'cart_id' => $cached_cart_id ) );
				}

				// Test to make sure abandoned cart hasn't already sent this email
				if ( empty( $abandoned_cart->sent_emails[$email_id] ) ) {
					it_exchange_abandoned_carts_send_email_for_cart( $abandoned_cart, $email_id );
					break 1;
				}
			}
		}
	}
}
add_action( 'wp_footer', 'it_exchange_abandoned_carts_process_qualified_shoppers_queue' );

/**
 * Grabs the active abandoned cart for a customer or returns false if there isn't one.
 *
 * @since 1.0.0
 *
 * @param int customer_id the wp user id for the customer
 * @return object the abandoned cart object
*/
function it_exchange_get_active_abandoned_cart_for_user( $customer_id ) {

	/**
	 * @todo THIS IS TEMP FOR TESTING. DELETE BEFORE RELEASE
	*/
	if ( ! $customer = it_exchange_get_customer( $customer_id ) )
		return false;
	// END TEMP

	// Grab customer's current cached cart id
	$cached_cart    = it_exchange_get_cached_customer_cart( $customer_id );
	$cached_cart_id = empty( $cached_cart['cart_id'][0] ) ? false : $cached_cart['cart_id'][0];

	$args = array(
		'customer'    => $customer_id,
		'cart_status' => 'abandoned',
		'cart_id'     => $cached_cart_id,
	);
	$carts = it_exchange_get_abandoned_carts( $args );

	/** @todo If we have more than one currently abandoned, we have a problem */

	if ( empty( $carts ) )
		return false;

	$cart = reset( $carts );
	return $cart;
}

/**
 * Creates a new abandoned cart for a user
 *
 * @since 1.0.0
 *
 * @param  int $user_id the WP user id for the exchange customer
 * @return int the wp post id
*/
function it_exchange_add_abondoned_cart( $user_id, $args=array() ) {
	// Confirm we have a legit exchagne user
	if ( ! $customer = it_exchange_get_customer( $user_id ) )
		return false;
	
	/**
	 * @todo Will need to do something here to clean up any potential old abandoned carts for user
	 * while not altering past reports (not deleting any completed / converted carts )
	*/

	$defaults = array(
		'post_status' => 'publish', // They will all be publish. We have a separate param for our status
		'cart_status' => 'abandoned',
		'ping_status' => 'closed',
		'cart_id'     => false,
	);

	$args = wp_parse_args( $defaults, $args );

	// Enforce or post type
	$args['post_type'] = 'it_ex_abandoned';

	// Insert the post
	if ( $abandoned_cart_id = wp_insert_post( $args ) ) {
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_customer_id', $user_id );
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_emails_sent', array() );
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_cart_status', $args['cart_status'] );
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_cart_id', $args['cart_id'] );

		do_action( 'it_exchange_add_abandoned_cart_meta', $abandoned_cart_id, $user_id );
		return it_exchange_get_abandoned_cart( $abandoned_cart_id );
	}
}

/**
 * Returns a list of emails to send for an abandoned cart based on time since last activity
 *
 * @since 1.0.0
 * @return array
*/
function it_exchange_abandoned_carts_get_abandonment_emails() {
	$five   = 60 * 60 * 5;
	$twenty = 149500; //60 * 60 * 20;

	$emails = array(
		$twenty => array(
			'title'   => 'Twenty Minute Email',
			'time'    => $twenty,
			'subject' => 'You you forgot to checkout, Coupon',
			'content' => 'This the content for the hard sell',
		),
		$five => array(
			'title'   => 'Five Minute Email',
			'time'    => $five,
			'subject' => 'You you forgot to checkout, soft reminder',
			'content' => 'This the content for the soft sell',
		),
	);
	krsort( $emails );
	return $emails;
}

/**
 * Sends a specific email
 *
 * @since 1.0.0
 *
 * @param mixed $abandoned_cart object or id
 * @parma int   $email_id      the email id 
*/
function it_exchange_abandoned_carts_send_email_for_cart( $abandoned_cart, $email_id ) {
	// Make sure the abandoned_cart is an object
	if ( empty( $abandoned_cart->ID ) )
		$abandoned_cart = it_exchange_get_abandoned_cart( $abandoned_cart );
	if ( ! is_object( $abandoned_cart ) || 'IT_Exchange_Abandoned_Cart' != get_class( $abandoned_cart ) )
		return false;

	$emails = it_exchange_abandoned_carts_get_abandonment_emails();
	$email  = isset( $emails[$email_id] ) ? $emails[$email_id] : false;

	// Make sure we found the email we're looking for.
	if ( empty( $email ) )
		return false;

	// Send the email
	echo "<span style='font-weight:bold;'>" . $abandoned_cart->customer_id . '</span> will receive ' . $email['subject'] . '<br /><br />';
}

/**
 * Returns a status label for an abandoned cart
 *
 * @since 1.0.0
 *
 * @param mixed $abandoned_cart id or cart object
 * @return string the label for the status
*/
function it_exchange_get_abanonded_cart_status_label( $abandoned_cart ) {
	// Make sure the abandoned_cart is an object
	if ( empty( $abandoned_cart->ID ) )
		$abandoned_cart = it_exchange_get_abandoned_cart( $abandoned_cart );
	if ( ! is_object( $abandoned_cart ) || 'IT_Exchange_Abandoned_Cart' != get_class( $abandoned_cart ) )
		return __( 'Unknown', 'LION' );

	switch( $abandoned_cart->cart_status ) {
		case 'abandoned' :
			$label = __( 'Abandoned', 'LION' );
			break;
		case 'reengaged' :
			$label = __( 'Reengaged', 'LION' );
			break;
		case 'reclaimed' :
			$label = __( 'Reclaimed', 'LION' );
			break;
		case 'expired' :
			$label = __( 'Expired', 'LION' );
			break;
		default:
			$label = __( 'Unknown', 'LION' );
	}

	return apply_filters( 'it_exchange_get_abanonded_cart_status_label', $label, $abandoned_cart );
}

function debug_abandoned_carts() {
	if ( is_admin() )
		return;

	$plugin_options = it_exchange_get_option( 'addon_abandoned_carts' );
	ITUtility::print_r($plugin_options);

}
//add_action( 'wp_footer', 'debug_abandoned_cats' );
