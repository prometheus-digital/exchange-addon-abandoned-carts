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
