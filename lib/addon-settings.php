<?php
/**
 * Callback function for add-on settings
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_addon_settings_callback() {
	// Store Owners should never arrive here. Add a link just in case the do somehow
	?>
	<?php
}


/**
 * Set default settings if empty
 *
 * @since 1.0.0
 *
 * @return
*/
function it_exchange_abandoned_carts_addon_set_default_options() {
	$defaults = it_exchange_abandoned_carts_addon_get_default_settings();
	$current  = it_exchange_get_option( 'abandoned_carts-addon' );

	if ( empty( $current ) ) {
		it_exchange_save_option( 'abandoned_carts-addon', $defaults );
	}
}
