<?php
/**
 * This file holds the class for an ExchangeWP Abandoned Cart
 *
 * @package IT_Exchange
 * @since 1.0.0
*/

/**
 * Merges a WP Post with ExchangeWP Abandoned Cart data
 *
 * @since 1.0.0
*/
class IT_Exchange_Abandoned_Cart {

	// WP Post Type Properties
	var $ID;
	var $post_author;
	var $post_date;
	var $post_date_gmt;
	var $post_content;
	var $post_title;
	var $post_excerpt;
	var $post_status;
	var $comment_status;
	var $ping_status;
	var $post_password;
	var $post_name;
	var $to_ping;
	var $pinged;
	var $post_modified;
	var $post_modified_gmt;
	var $post_content_filtered;
	var $post_parent;
	var $guid;
	var $menu_order;
	var $post_type;
	var $post_mime_type;
	var $comment_count;

	/**
	 * Customer ID this cart belongs to
	 * @var int $customer_id
	*/
	var $customer_id = false;

	/**
	 * Abandoned Cart Status
	 * @var string $cart_status
	*/
	var $cart_status = 'abandoned';

	/**
	 * Abandoned Cart Value
	 * @var string $cart_value
	*/
	var $cart_value;

	/**
	 * Emails Sent. Array of email_id => time_sent
	 * @var array
	*/
	var $emails_sent;

	/**
	 * Constructor. Loads post data and product data
	 *
	 * @since 1.0.0
	 * @param mixed $post  wp post id or post object. optional.
	 * @return void
	*/
	function __construct( $post=false ) {
		// If not an object, try to grab the WP object
		if ( ! is_object( $post ) )
			$post = get_post( (int) $post );

		// Ensure that $post is a WP_Post object
		if ( is_object( $post ) && 'WP_Post' != get_class( $post ) )
			$post = false;

		// Ensure this is a product post type
		if ( 'it_ex_abandoned' != get_post_type( $post ) )
			$post = false;

		// Return a WP Error if we don't have the $post object by this point
		if ( ! $post )
			return new WP_Error( 'it-exchange-product-not-a-wp-post', __( 'The IT_Exchange_Abandoned_Cart class must have a WP post object or ID passed to its constructor', 'LION' ) );

		// Grab the $post object vars and populate this objects vars
		foreach( (array) get_object_vars( $post ) as $var => $value ) {
			$this->$var = $value;
		}

		// Populate core postmeta
		$this->customer_id = get_post_meta( $this->ID, '_it_exchange_abandoned_cart_customer_id', true );
		$this->cart_status = get_post_meta( $this->ID, '_it_exchange_abandoned_cart_cart_status', true );
		$this->cart_value  = get_post_meta( $this->ID, '_it_exchange_abandoned_cart_cart_value', true );
		$this->emails_sent = get_post_meta( $this->ID, '_it_exchange_abandoned_cart_emails_sent', true );
		do_action( 'it_exchange_abandoned_cart_populate_post_meta_object_properties', $this->ID );
	}

	/**
	 * Class Deprecated Constructor
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Abandoned_Cart() {
		self::__construct();
	}
}
