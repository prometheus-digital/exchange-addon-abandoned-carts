<?php
/**
 * Includes all of our files
 */

/**
 * Hooks
 */
include( dirname( __FILE__ ) . '/lib/hooks.php' );
include( dirname( __FILE__ ) . '/lib/email.php' );
include( dirname( __FILE__ ) . '/lib/display.php' );

/**
 * API
 */
include( dirname( __FILE__ ) . '/api/abandoned-carts.php' );

/**
 * Theme API
 */
include( dirname( __FILE__ ) . '/api/theme/abandoned-cart.php' );

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

include ( dirname( __FILE__ ) ) . '/lib/class.emails.php';