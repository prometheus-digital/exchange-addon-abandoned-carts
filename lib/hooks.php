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

        $unsubscribed = get_user_meta( $user_id, '_it_exchange_unsubscribed_from_abandoned_cart_emails', true );

        if ( ! empty( $unsubscribed ) ) {
	        continue;
        }

        // Calculate how log it has been since this use was last active
        $time_since_last_activity = ($now - $last_active );

        // Loop through our possible emails sorted by last email to first
        foreach( $cart_abandonment_emails as $email_id => $props ) {

	        // Test to see if last email was beyond the timeframe for this email
        	if ( $time_since_last_activity < $props['time'] ) {
        		continue;
	        }

            // Test to make sure the abandoned cart exists and has not received this email
            if ( ! $abandoned_cart = it_exchange_get_active_abandoned_cart_for_user( $user_id ) ) {
                // Grab customer's current cached cart id
                $cached_cart       = it_exchange_get_cached_customer_cart( $user_id );
                $cached_cart_id    = empty( $cached_cart['cart_id'][0] ) ? false : $cached_cart['cart_id'][0];
                $cached_cart_value = it_exchange_get_cart_total( true, array( 'use_cached_customer_cart' => $user_id ) );

                $abandoned_cart = it_exchange_add_abandoned_cart( $user_id, array(
                    'cart_id'    => $cached_cart_id,
                    'cart_value' => $cached_cart_value
                ) );
            }

            foreach( (array) $abandoned_cart->emails_sent as $email ) {
                if ( empty( $email['email_id'] ) ) {
	                continue;
                }

                if ( $email['email_id'] == $email_id ) {
	                continue;
                }
            }

            it_exchange_abandoned_carts_send_email_for_cart( $abandoned_cart, $email_id );

            break;
        }
    }

	IT_Exchange_Abandoned_Cart_Emails::batch( false );
}
add_action( 'it_exchange_abandoned_carts_hourly_event_hook', 'it_exchange_abandoned_carts_process_qualified_shoppers_queue' );

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