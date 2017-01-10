<?php
/**
 * Email Hooks.
 *
 * @since 2.0.0
 * @license GPLv2
 */

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
 * @since 2.0.0
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
