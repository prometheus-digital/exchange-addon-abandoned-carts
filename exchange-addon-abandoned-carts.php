<?php
/*
 * Plugin Name: ExchangeWP - Abandoned Carts
 * Version: 1.2.3
 * Description: Tracks abandoned carts and automatically emails customers
 * Plugin URI: https://exchangewp.com/downloads/abandoned-carts/
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com
 * ExchangeWP Package: exchange-addon-abandoned-carts

 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 * 5. Add License key to the plugin settings page.
 *
*/

/**
 * This registers our plugin as an exchange addon
 *
 * To learn how to create your own-addon, visit todo: provide new link for a tutorial.
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_abandoned_carts_addon() {
	$options = array(
		'name'              => __( 'Abandoned Carts', 'LION' ),
		'description'       => __( 'Tracks abandoned carts and automatically emails customers.', 'LION' ),
		'author'            => 'ExchangeWP',
		'author_url'        => 'https://exchangewp.com/downloads/abandoned-carts/',
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

/**
 * Activation hook. Runs on activation
 *
 * @since 1.0.0
*/
function it_exchange_abandoned_carts_activation_hook() {
	// Setup cron on activation
	wp_schedule_event( time(), 'hourly', 'it_exchange_abandoned_carts_hourly_event_hook' );
	update_option( 'it-exchange-create-abandoned-cart-demo-email', true );
}
register_activation_hook( __FILE__, 'it_exchange_abandoned_carts_activation_hook' );

/**
 * Deactivation hook. Runs on deactivation
 *
 * @since 1.0.0
*/
function it_exchange_abandoned_carts_deactivation_hook() {
	// clear cron on deactivation
	wp_clear_scheduled_hook( 'it_exchange_abandoned_carts_hourly_event_hook' );
}
register_deactivation_hook( __FILE__, 'it_exchange_abandoned_carts_deactivation_hook' );


function exchange_abandoned_carts_plugin_updater() {

	$license_data = get_transient( 'exchangewp_license_check' );

	if ( $license_data->license == 'valid' ) {

		$exchangewp_license = it_exchange_get_option( 'exchangewp_licenses' );
		$license = $exchangewp_license['exchangewp_license'];

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
				'version' 		=> '1.2.3', 		      		// current version number
				'license' 		=> $license, 		          // license key (used get_option above to retrieve from DB)
				'item_name' 	=> 'abandoned-carts', 	  // name of this plugin
				'author' 	  	=> 'ExchangeWP',          // author of this plugin
				'url'       	=> home_url(),
				'wp_override' => true,
				'beta'		  	=> false
			)
		);
		// var_dump($edd_updater);
		// die();
	}
}

add_action( 'admin_init', 'exchange_abandoned_carts_plugin_updater', 0 );
