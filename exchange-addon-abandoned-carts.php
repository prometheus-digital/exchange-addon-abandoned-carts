<?php
/*
 * Plugin Name: iThemes Exchange - Abandoned Carts
 * Version: 1.0.0
 * Description: Tracks abandoned carts and automatically emails customers
 * Plugin URI: http://ithemes.com/exchange/abandoned-carts
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-abandoned-carts
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

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
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/abandoned-carts50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'settings-callback' => 'it_exchange_abandoned_carts_print_settings_page',
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

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_abandoned_carts_updater_register( $updater ) { 
	    $updater->register( 'exchange-addon-abandoned-carts', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_abandoned_carts_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );
