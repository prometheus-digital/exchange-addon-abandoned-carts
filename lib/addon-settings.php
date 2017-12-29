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
	<div class="wrap">
		<?php ITUtility::screen_icon( 'it-exchange' ); ?>
		<h2><?php _e( 'Abandoned Carts License', 'LION' ); ?></h2>
		<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

	</div>
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
