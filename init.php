<?php
/**
 * Includes all of our files
*/

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

/**
 * Abandond Cart Emails Post Type
*/
include( dirname( __FILE__ ) . '/lib/class.abandoned-cart-email-post-type.php' );
