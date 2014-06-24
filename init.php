<?php
/**
 * Includes all of our files
*/

/**
 * Settings
*/
include( dirname( __FILE__ ) . '/lib/settings.php' );

/**
 * Hooks
*/
include( dirname( __FILE__ ) . '/lib/hooks.php' );

/**
 * API
*/
include( dirname( __FILE__ ) . '/api/abandoned-carts.php' );

/**
 * Abandoned Carts Post Type
*/
include( dirname( __FILE__ ) . '/lib/class.abandoned-carts-post-type.php' );

/**
 * Abandoned Carts Post Class
*/
include( dirname( __FILE__ ) . '/lib/class.abandoned-cart.php' );
