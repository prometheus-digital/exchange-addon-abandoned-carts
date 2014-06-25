<?php
/**
 * Creates the post type for Abandoned Carts
 *
 * @package IT_Exchange
 * @since 1.0.0
*/

/**
 * Registers the it_ex_abandoned post type
 *
 * @since 1.0.0
*/
class IT_Exchange_Abandoned_Cart_Post_Type {

	/**
	 * Class Constructor
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Abandoned_Cart_Post_Type() {

		$this->admin_menu_capability = apply_filters( 'it_exchange_abandoned_carts_admin_menu_cap', 'activate_plugins' );
		$this->init();

		add_action( 'save_post_it_ex_abandoned', array( $this, 'save_abandoned_cart' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_submenu_to_exchange' ) );
			add_filter( 'manage_edit-it_ex_abandoned_columns', array( $this, 'modify_all_abandoned_carts_table_columns' ) );
			add_filter( 'manage_edit-it_ex_abandoned_sortable_columns', array( $this, 'make_abandoned_cart_custom_columns_sortable' ) );
			add_filter( 'manage_it_ex_abandoned_posts_custom_column', array( $this, 'add_abandoned_cart_method_info_to_view_all_table_rows' ) );
			add_filter( 'it_exchange_abandoned_cart_metabox_callback', array( $this, 'register_abandoned_cart_details_admin_metabox' ) );
			add_filter( 'screen_layout_columns', array( $this, 'modify_details_page_layout' ) );
			add_filter( 'get_user_option_screen_layout_it_ex_abandoned', array( $this, 'update_user_column_options' ) );
			add_filter( 'bulk_actions-edit-it_ex_abandoned', '__return_empty_array' );
		}
	}

	function init() {

		$this->post_type = 'it_ex_abandoned';
		$labels    = array(
			'name'          => __( 'Abandoned Carts', 'LION' ),
			'singular_name' => __( 'Abandoned Cart', 'LION' ),
		);
		$this->options = array(
			'labels'               => $labels,
			'description'          => __( 'An iThemes Exchange Post Type for storing all Abandoned Carts in the system', 'LION' ),
			'public'               => false,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'show_in_menu'         => false, // We will be adding it manually with various labels based on available product-type add-ons
			'show_in_admin_bar'    => false,
			'register_meta_box_cb' => array( $this, 'meta_box_callback' ),
			'supports'             => array(),
			'capabilities'         => array(
				'edit_posts'        => 'edit_posts',
				'create_posts'      => apply_filters( 'it_ex_abandoned_create_posts_capabilities', 'do_not_allow' ),
				'edit_others_posts' => 'edit_others_posts',
				'publish_posts'     => 'publish_posts',
			),
			'map_meta_cap'         => true,
			'capability_type'      => 'post',
		);

		add_action( 'init', array( $this, 'register_the_post_type' ) );
	}

	/**
	 * Adds the submenu item to exchagne menu
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function add_submenu_to_exchange() {
		add_submenu_page( 'it-exchange', __( 'Abandoned Carts', 'LION' ), __( 'Abandoned Carts', 'LION' ), $this->admin_menu_capability, 'edit.php?post_type=it_ex_abandoned' );
	}

	/**
	 * Set the max columns option for the add / edit product page.
	 *
	 * @since 1.0.0
	 *
	 * @param $columns Existing array of how many colunns to show for a post type
	 * @return array Filtered array
	*/
	function modify_details_page_layout( $columns ) {
		$columns['it_ex_abandoned'] = 1;
		return $columns;
	}

	/**
	 * Updates the user options for number of columns to use on abandoned_cart details page
	 *
	 * @since 1.0.0
	 *
	 * @return 2
	*/
	function update_user_column_options( $existing ) {
		return 1;
	}

	/**
	 * Actually registers the post type
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_the_post_type() {
		register_post_type( $this->post_type, $this->options );
	}

	/**
	 * Callback hook for abandoned_cart post type admin views
	 *
	 * @since 1.0.0
	 * @uses it_exchange_get_enabled_add_ons()
	 * @return void
	*/
	function meta_box_callback( $post ) {
		$abandoned_cart = it_exchange_get_abandoned_cart( $post );

		// Do action for any product type
		do_action( 'it_exchange_abandoned_cart_metabox_callback', $abandoned_cart );
	}

	/**
	 * Provides specific hooks for when iThemes Exchange abandoned_carts are saved.
	 *
	 * This method is hooked to save_post. It provides hooks for add-on developers
	 * that will only be called when the post being saved is an iThemes Exchange abandoned_cart.
	 * It provides the following 4 hooks:
	 * - it_exchange_save_abandoned_cart_unvalidated                    // Runs every time an iThemes Exchange abandoned_cart is saved.
	 * - it_exchange_save_abandoned_cart                                // Runs every time an iThemes Exchange abandoned_cart is saved if not an autosave and if user has permission to save post
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function save_abandoned_cart( $post ) {

		// Exit if not it_exchange_prod post_type
		if ( ! 'it_ex_abandoned' == get_post_type( $post ) )
			return;

		// These hooks fire off any time a it_ex_abandoned post is saved w/o validations
		do_action( 'it_exchange_save_abandoned_cart_unvalidated', $post );

		// Fire off actions with validations that most instances need to use.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! current_user_can( 'edit_post', $post ) )
			return;

		// This is called any time save_post hook
		do_action( 'it_exchange_save_abandoned_cart', $post );
	}

	/**
	 * Adds custom columns to the View All abandoned_carts table
	 *
	 * @since 1.0.0
	 * @param array $existing  exisiting columns array
	 * @return array  modified columns array
	*/
	function modify_all_abandoned_carts_table_columns( $existing ) {

		// Remove Checkbox
		if ( isset( $existing['cb'] ) )
			unset( $existing['cb'] );

		// Remove Title - adding it back below
		if ( isset( $existing['title'] ) )
			unset( $existing['title'] );

		// Remove Format
		if ( isset( $existing['format'] ) )
			unset( $existing['format'] );

		// Remove Author
		if ( isset( $existing['author'] ) )
			unset( $existing['author'] );

		// Remove Comments
		if ( isset( $existing['comments'] ) )
			unset( $existing['comments'] );

		// Remove Date
		if ( isset( $existing['date'] ) )
			unset( $existing['date'] );

		// Remove Builder
		if ( isset( $existing['builder_layout'] ) )
			unset( $existing['builder_layout'] );


		// All Core should be removed at this point. Build ours back (including date from core)
		$exchange_columns = array(
			'date'                                          => __( 'Date', 'LION' ),
			'it_exchange_abandoned_cart_customer_column'    => __( 'Customer', 'LION' ),
			'it_exchange_abandoned_cart_status_column'      => __( 'Cart Status', 'LION' ),
			'it_exchange_abandoned_cart_emails_sent_column' => __( 'Emails Sent', 'LION' ),
			'it_exchange_abandoned_cart_total_column'       => __( 'Cart Value', 'LION' ),
		);

		// Merge ours back with existing to preserve any 3rd party columns
		$columns = array_merge( $exchange_columns, $existing );
		return $columns;
	}

	/**
	 * Makes some of the custom abandoned_cart columns added above sortable
	 *
	 * @since 1.0.0
	 * @param array $sortables  existing sortable columns
	 * @return array  modified sortable columnns
	*/
	function make_abandoned_cart_custom_columns_sortable( $sortables ) {
		$sortables['it_exchange_abandoned_cart_status_column']   = 'it_exchange_abandoned_cart_status_column';
		$sortables['it_exchange_abandoned_cart_customer_column'] = 'it_exchange_abandoned_cart_customer_column';
		$sortables['it_exchange_abandoned_cart_total_column']    = 'it_exchange_abandoned_cart_total_column';
		return $sortables;
	}

	/**
	 * Adds the values to each row of the custom columns added above
	 *
	 * @since 1.0.0
	 * @param string $column  column title
	 * @param integer $post  post ID
	 * @return void
	*/
	function add_abandoned_cart_method_info_to_view_all_table_rows( $column ) {
		global $post;
		$abandoned_cart = it_exchange_get_abandoned_cart( $post );
		$emails         = it_exchange_abandoned_carts_get_abandonment_emails();

		switch( $column ) {
			case 'it_exchange_abandoned_cart_status_column' :
				esc_attr_e( it_exchange_get_abanonded_cart_status_label( $abandoned_cart ) );
				break;
			case 'it_exchange_abandoned_cart_customer_column' :
				if ( $customer = it_exchange_get_customer( $abandoned_cart->customer_id ) )
					esc_attr_e( empty( $customer->wp_user->display_name ) ? $customer->wp_user->user_login : $customer->wp_user->display_name );
				else
					esc_attr_e( __( 'Unknown', 'LION' ) );
				break;
			case 'it_exchange_abandoned_cart_emails_sent_column' :
				if ( empty( $abandoned_cart->emails_sent ) || ! is_array( $abandoned_cart->emails_sent ) ) {
					esc_attr_e( __( 'None', 'LION' ) );
					break;
				}

				foreach( $abandoned_cart->emails_sent as $email ) {
					if ( isset( $emails[$email]['title'] ) ) {
						$emails_sent[] = $emails[$email]['title'];
					}
				};
				echo implode( $emails_sent, '<br />' );
				break;
			case 'it_exchange_abandoned_cart_total_column' :
				esc_attr_e( it_exchange_get_cart_total( true, array( 'use_cached_customer_cart' => $abandoned_cart->customer_id ) ) );
				break;
		}
	}

	/**
	 * Registers the abandoned_cart details meta box
	 *
	 * @since 1.0.0
	 *
	 * @param object $post post object
	 * @return void
	*/
	function register_abandoned_cart_details_admin_metabox( $post ) {
		// Remove Publish metabox
		remove_meta_box( 'submitdiv', 'it_ex_abandoned', 'side' );

		// Remove Slug metabox
		remove_meta_box( 'slugdiv', 'it_ex_abandoned', 'normal' );

		// Remove screen options tab
		add_filter('screen_options_show_screen', '__return_false');

		// Cart Details
		$title     = __( 'Abandoned Cart Details', 'LION' );
		$callback  = array( $this, 'print_abandoned_cart_details_metabox' );
		$post_type = 'it_ex_abandoned';
		add_meta_box( 'it-exchange-abandoned-cart-details', $title, $callback, $post_type, 'normal', 'high' );

	}

	/**
	 * Prints the abandoned cart details metabox
	 *
	 * @since 1.0.0
	 * @param object $post post object
	 * @return void
	*/
	function print_abandoned_cart_details_metabox( $post ) {
		do_action( 'it_exchange_before_abandoned_cart_details' );
		?><p>Here i am</p><?php
		do_action( 'it_exchange_after_abandoned_cart_details' );
	}
}
$IT_Exchange_Abandoned_Cart_Post_Type = new IT_Exchange_Abandoned_Cart_Post_Type();
