<?php

/**
 * This registers our plugin as an exchange addon
 *
 * To learn how to create your own-addon, visit http://ithemes.com/codex/page/Exchange_Custom_Add-ons:_Overview
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_abandoned_carts_addon() {
	$options = array(
		'name'              => __( 'Abandoned Carts', 'LION' ),
		'description'       => __( 'Tracks abandoned carts and automatically emails customers.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/abandoned-carts/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/abandoned-carts50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'admin',
	);
	it_exchange_register_addon( 'abandoned-carts', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_abandoned_carts_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_abandoned_carts_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_abandoned_carts_set_textdomain' );