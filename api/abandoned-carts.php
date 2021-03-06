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
    if ( ! empty( $args['cart_status'] ) && 'any' != $args['cart_status'] ) {
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
		$abandoned_cart->customer_id       = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_customer_id', true );
		$abandoned_cart->emails_sent       = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );
		$abandoned_cart->cart_status       = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_cart_status', true );
		$abandoned_cart->cart_id           = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_cart_id', true );
		$abandoned_cart->conversion_source = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_conversion_source', true );
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
	$cart_products = it_exchange_get_cart_products();
	if ( count( $cart_products ) < 1 )
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

	/**
	 * @todo Introduce this in a later version along with lost / usubscribed and other statuses
	 *
	 * // If user has an abandoned cart, mark it as reengaged
	 * if ( $cart = it_exchange_get_active_abandoned_cart_for_user( $customer_id ) ) {
	 * if ( 'abandoned' == $cart->cart_status )
	 * update_post_meta( $cart->ID, '_it_exchange_abandoned_cart_cart_status', 'reengaged' );
	 * }
	*/
}

/**
 * Grabs the active abandoned cart for a customer or returns false if there isn't one.
 *
 * @since 1.0.0
 *
 * @param int customer_id the wp user id for the customer
 * @return object the abandoned cart object
*/
function it_exchange_get_active_abandoned_cart_for_user( $customer_id ) {

	// Grab customer's current cached cart id
	$cached_cart    = it_exchange_get_cached_customer_cart( $customer_id );
	$cached_cart_id = empty( $cached_cart['cart_id'][0] ) ? false : $cached_cart['cart_id'][0];

	$args = array(
		'customer'    => $customer_id,
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
function it_exchange_add_abandoned_cart( $user_id, $args=array() ) {
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
		'cart_value'  => false,
		'ping_status' => 'closed',
		'cart_id'     => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// Enforce or post type
	$args['post_type'] = 'it_ex_abandoned';

	// Insert the post
	if ( $abandoned_cart_id = wp_insert_post( $args ) ) {
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_customer_id', $user_id );
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_cart_status', $args['cart_status'] );
		update_post_meta( $abandoned_cart_id, '_it_exchange_abandoned_cart_cart_value', $args['cart_value'] );
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
function it_exchange_abandoned_carts_get_abandonment_emails( $args=array() ) {

	$defaults = array(
		'post_type'      => 'it_ex_abandond_email',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);
	$args = wp_parse_args( $args, $defaults );
	$email_templates = get_posts( $args );

	$emails = array();
	foreach( (array) $email_templates as $template ) {
		$subject         = get_the_title( $template->ID );
		$temp            = $GLOBALS['post'];
		$GLOBALS['post'] = $template;
		$message         = $template->post_content;
		$GLOBALS['post'] = $temp;
		$scheduling      = get_post_meta( $template->ID, '_it_exchange_abandoned_cart_emails_scheduling_unix', true );

		if ( ! empty( $subject ) && ! empty( $message ) && ! empty( $scheduling ) )
			$emails[$template->ID] = array( 'title' => $subject, 'subject' => $subject, 'time' => $scheduling, 'content' => $message );
	}

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

	// Grab the email template we want to send
	$emails = it_exchange_abandoned_carts_get_abandonment_emails();
	$email  = isset( $emails[$email_id] ) ? $emails[$email_id] : false;

	// Get the user for the email
	$user = get_userdata( $abandoned_cart->customer_id );

	// Get cached cart and set products if it matches
	$cached_cart = it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id );
	$products    = array();
	$cart_value  = empty( $abandoned_cart->cart_value ) ? false : $abandoned_cart->cart_value;
	$first_name  = get_user_meta( $abandoned_cart->customer_id, 'first_name', true );
	$last_name   = get_user_meta( $abandoned_cart->customer_id, 'last_name', true );

	if ( ! empty( $cached_cart['cart_id'][0] ) && $cached_cart['cart_id'][0] == $abandoned_cart->cart_id ) {
		if ( ! empty( $cached_cart['products'] ) ) {
			foreach( (array) $cached_cart['products'] as $product ) {

				if ( empty( $product['product_id'] ) ) {
					continue;
				}

				$product_title = it_exchange_get_product_feature( $product['product_id'], 'title' );
				$base_price = it_exchange_get_product_feature( $product['product_id'], 'base-price' );
				if ( ! empty( $product_title ) )
					$products[]  = array( 'title' => $product_title, 'price' => it_exchange_format_price( $base_price ) );
			}
		}
	}

	// Setup globals for Shortcodes and apply the_content
	$GLOBALS['it_exchange']['abandoned_carts']['shortcode_data'] = array(
		'customer_name'       => empty( $user->data->display_name ) ?  __( 'Customer', 'LION' ) : $user->data->display_name,
		'customer_first_name' => empty( $first_name ) ?  __( 'Customer', 'LION' ) : $first_name,
		'customer_last_name'  => empty( $last_name ) ?  __( '', 'LION' ) : $last_name,
		'cart_link_href'      => it_exchange_generate_reclaim_link_for_abandoned_email( $email_id, $abandoned_cart->ID ),
		'cart_products'       => $products,
		'cart_value'          => $cart_value,
	);
	$email['content'] = apply_filters( 'the_content', $email['content'] );
	unset( $GLOBALS['it_exchange']['abandoned_carts']['shortcode_data'] );

	// Make sure we found the email we're looking for.
	if ( empty( $email ) )
		return false;

	// Add tracking code
	$email['content'] .= '<img src="' . add_query_arg( array( 'it-exchange-cart-summary' => $email_id . '-'  . $abandoned_cart->ID ), get_home_url() )  . '" width="1" height="1" />';

	// Send the email
	if ( ! empty( $user->data->user_email ) ) {

		$settings = it_exchange_get_option( 'settings_email' );

		$headers = array();
		if ( !empty( $settings['receipt-email-address'] ) ) {
			$headers[] = 'From: ' . $settings['receipt-email-name'] . ' <' . $settings['receipt-email-address'] . '>';
		}
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-Type: text/html';
		$headers[] = 'charset=utf-8';

		wp_mail( $user->data->user_email, $email['subject'], $email['content'], $headers );

		// After sending the email, add this email to the list of emails sent for this abandoned cart
		$meta = array(
			'email_id'     => $email_id,
			'time_sent'    => time(),
			'to'           => $user->data->user_email,
			'subject'      => $email['subject'],
			'message'      => $email['content'],
			'cart_details' => it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id ),
		);
		// Grab existing emails
		$emails_sent = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );
		// Add this email info to the emails_sent array
		$emails_sent[$email_id] = $meta;
		// Update the post meta for the abadoned cart
		update_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', $emails_sent );

		// Also update the number of times this email template has been delivered
		$number_sent = get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', true );
		update_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', ($number_sent + 1) );
	}
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
		case 'recovered' :
			$label = __( 'Recovered', 'LION' );
			break;
		case 'expired' :
			$label = __( 'Expired', 'LION' );
			break;
		case 'lost' :
			$label = __( 'Lost', 'LION' );
			break;
		default:
			$label = __( 'Unknown', 'LION' );
	}

	return apply_filters( 'it_exchange_get_abanonded_cart_status_label', $label, $abandoned_cart );
}

/**
 * Returns the human readable version of the scheduling for an email
 *
 * @since 1.0.0
 *
 * @param int $email_id the wp post id for the email
 *
 * @return string
*/
function it_exchange_get_abandoned_cart_email_human_readable_schedule( $email_id ) {
	$scheduling = get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_scheduling', true );
	if ( ! empty( $scheduling['int'] ) && ! empty( $scheduling['unit'] ) )
		$value = sprintf( _n( '%d %s', '%d %ss', trim( $scheduling['int'] ), 'LION' ), $scheduling['int'], $scheduling['unit'] );
	else
		$value = __( 'Unknown', 'LION' );

	return $value;
}

/**
 * Returns the number of times a specific email has been sent.
 *
 * @since 1.0.0
 *
 * @param int $email_id the wp post id for the email
 *
 * @return int
*/
function it_exchange_get_abandoned_cart_email_times_sent( $email_id ) {
	$sent = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', true );
	return empty( $sent ) ? 0 : $sent;
}

/**
 * Marks an email as opened
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_mark_email_opened( $email_id, $cart_id ) {
	$cart_emails = get_post_meta( $cart_id, '_it_exchange_abandoned_cart_emails_sent', true );

	// Make sure this email hasn't already been counted.
	foreach( (array) $cart_emails as $key => $email ) {
		if ( ! empty( $email['email_id'] ) && $email['email_id'] == $email_id && ! empty( $email['opened'] ) ) {
			return;
		} else if ( ! empty( $email['email_id'] ) && $email['email_id'] == $email_id ) {
			$cart_emails[$key]['opened'] = time();
			update_post_meta( $cart_id, '_it_exchange_abandoned_cart_emails_sent', $cart_emails );
		}
	}

	// If we made it this far the abadoned cart's sent_email has been flagged as open and we need to increment the opens for the email.
	$opened = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_opened', true );
	update_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_opened', ( $opened + 1 ) );

	// Lets also credit any future purchases to this email
	update_post_meta( $cart_id, '_it_exchange_abandoned_cart_conversion_source', $email_id );

}

/**
 * Returns the number of times a specific email has been opened.
 *
 * @since 1.0.0
 *
 * @param int $email_id the wp post id for the email
 *
 * @return int
*/
function it_exchange_get_abandoned_cart_email_opened_rate( $email_id ) {
	$opened = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_opened', true );
	$sent   = it_exchange_get_abandoned_cart_email_times_sent( $email_id );

	$percentage = empty( $opened ) || empty( $sent ) ? 0 : $opened/$sent*100;
	return empty( $percentage )? 0 . '%' : round( $percentage, 2 ) . '%';
}

/**
 * Returns the number of times a specific email has been converted into a sell.
 *
 * @since 1.0.0
 *
 * @param int $email_id the wp post id for the email
 *
 * @return int
*/
function it_exchange_get_abandoned_cart_email_recovered_rate( $email_id ) {
	$recovered = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_recovered', true );
	$sent      = it_exchange_get_abandoned_cart_email_times_sent( $email_id );

	$percentage = empty( $recovered ) || empty( $sent ) ? 0 : $recovered/$sent*100;
	return empty( $percentage )? 0 . '%' : round( $percentage, 2 ) . '%';
}


/**
 * Generate the reclaim link for a specific email
 *
 * @since 1.0.0
 *
 * @param int $email_id the WP post id for the email
 * @param int $cart_id  the abandoned cart id
 * @return string
*/
function it_exchange_generate_reclaim_link_for_abandoned_email( $email_id, $cart_id ) {
	$rmd5             = md5( rand() );
	$obfuscation_ftw  = substr( $rmd5, 0, 1 );
	$obfuscation_ftw .= '-' . substr( $rmd5, 1, 7 ) . $email_id . substr( $rmd5, 8, 5 );
	$obfuscation_ftw .= '-' . substr( $rmd5, 13, 4 );
	$obfuscation_ftw .= '-' . $cart_id . substr( $rmd5, 17, 3 );
	$obfuscation_ftw .= '-' . substr( $rmd5, 20 );
	return add_query_arg( array( 'iterab' => $obfuscation_ftw ), get_home_url() );
}

/**
 * Marks an email as clicked through
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_mark_email_clicked_through( $email_id, $cart_id ) {
	$cart_emails = get_post_meta( $cart_id, '_it_exchange_abandoned_cart_emails_sent', true );

	// Make sure this email hasn't already been credited for reengaging the custumer.
	foreach( (array) $cart_emails as $key => $email ) {
		if ( ! empty( $email['email_id'] ) && $email['email_id'] == $email_id && ! empty( $email['clickedthrough'] ) ) {
			return;
		} else if ( ! empty( $email['email_id'] ) && $email['email_id'] == $email_id ) {
			$cart_emails[$key]['clickedthrough'] = time();
			update_post_meta( $cart_id, '_it_exchange_abandoned_cart_emails_sent', $cart_emails );
		}
	}

	// If we made it this far the abadoned cart's sent_email has been flagged as reengaged and we need to increment the clickthroughs for the email.
	$clickedthrough = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_clickedthrough', true );
	update_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_clickedthrough', ( $clickedthrough + 1 ) );
}

/**
 * Returns the percentage of clickthroughs for a specific email template
 *
 * @since 1.0.0
 *
 * @param int $email_id the wp post id for the email
 *
 * @return int
*/
function it_exchange_get_abandoned_cart_email_clickthrough_rate( $email_id ) {
	$clickedthrough = (int) get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_clickedthrough', true );
	$sent           = it_exchange_get_abandoned_cart_email_times_sent( $email_id );

	$percentage = empty( $clickedthrough ) || empty( $sent ) ? 0 : $clickedthrough/$sent*100;
	return empty( $percentage )? 0 . '%' : round( $percentage, 2 ) . '%';
}

/**
 * Returns the total number of abandoned carts
 *
 * @since 1.0.0
 *
 * @return int
*/
function it_exchange_get_number_of_abandoned_carts() {
	$carts = it_exchange_get_abandoned_carts( array( 'cart_status' => 'any' ) );
	return count( $carts );
}

/**
 * Returns the total number of recovered carts
 *
 * @since 1.0.0
 *
 * @return int
*/
function it_exchange_get_number_of_recovered_abandon_carts() {
	$carts = it_exchange_get_abandoned_carts( array( 'cart_status' => 'recovered' ) );
	return count( $carts );
}

/**
 * Returns the total value number of recovered carts
 *
 * @since 1.0.0
 *
 * @return int
*/
function it_exchange_get_value_of_recovered_abandon_carts( $format_price=true ) {
	$carts = it_exchange_get_abandoned_carts( array( 'cart_status' => 'recovered' ) );
	$value = 0;
	foreach( $carts as $cart ) {
		$cart_value = it_exchange_convert_to_database_number( $cart->cart_value );
		$value      = $value + $cart_value;
	}

	$value = it_exchange_convert_from_database_number( $value );

	return empty( $format_price ) ? $value : it_exchange_format_price( $value );
}

/**
 * Returns the average total value number of recovered carts
 *
 * @since 1.0.0
 *
 * @return int
*/
function it_exchange_get_average_value_of_recovered_abandon_carts( $format_price=true ) {
	$carts     = it_exchange_get_abandoned_carts( array( 'cart_status' => 'recovered' ) );
	$num_carts = count( $carts );
	$value     = 0;
	foreach( $carts as $cart ) {
		$cart_value = it_exchange_convert_to_database_number( $cart->cart_value );
		$value      = $value + $cart_value;
	}

	$value = it_exchange_convert_from_database_number( $value );

	$average = empty( $value ) || empty( $num_carts ) ? 0 : $value/$num_carts;

	return empty( $format_price ) ? $average : it_exchange_format_price( $average );
}

/**
 * Returns stats grouped by day
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_abandoned_carts_get_abandoned_carts_by_day() {
	$carts = it_exchange_get_abandoned_carts( array( 'cart_status' => 'recovered', 'posts_per_page' => -1 ) );

	$stats = array();
	foreach( (array) $carts as $cart ) {
		$date = get_the_date( '', $cart->ID );
		if ( empty( $stats[$date] ) )
			$stats[$date] = 1;
		else
			$stats[$date] = $stats[$date] + 1;
	}
	return $stats;
}
