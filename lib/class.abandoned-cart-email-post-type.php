<?php
/**
 * Creates the post type for Abandoned Carts Emails
 *
 * @package IT_Exchange
 * @since 1.0.0
*/

/**
 * Registers the it_ex_abandond_email post type
 *
 * @since 1.0.0
*/
class IT_Exchange_Abandoned_Cart_Email_Post_Type {

	/**
	 * Class Constructor
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function __construct() {

		$this->admin_menu_capability = apply_filters( 'it_exchange_abandoned_carts_admin_menu_cap', 'activate_plugins' );
		$this->init();

		add_action( 'save_post_it_ex_abandond_email', array( $this, 'save_abandoned_cart_email' ) );
		add_action( 'it_exchange_save_abandoned_cart_email', array( $this, 'update_scheduling' ) );

		if ( is_admin() ) {
			add_filter( 'manage_edit-it_ex_abandond_email_columns', array( $this, 'modify_all_abandoned_cart_emails_table_columns' ) );
			add_filter( 'manage_edit-it_ex_abandond_email_sortable_columns', array( $this, 'make_abandoned_cart_email_custom_columns_sortable' ) );
			add_filter( 'manage_it_ex_abandond_email_posts_custom_column', array( $this, 'add_abandoned_cart_email_info_to_view_all_table_rows' ) );
			add_filter( 'it_exchange_abandoned_cart_email_metabox_callback', array( $this, 'register_abandoned_cart_email_details_admin_metabox' ) );
			add_filter( 'enter_title_here', array( $this, 'modify_title_placeholder' ), 10, 2 );
		}

	}

	/**
	 * Class Deprecated Constructor
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Abandoned_Cart_Email_Post_Type() {
		self::__construct();
	}

	function init() {

		$this->post_type = 'it_ex_abandond_email';
		$labels    = array(
			'name'               => __( 'Abandoned Cart Email Templates', 'LION' ),
			'singular_name'      => __( 'Abandoned Cart Email Template', 'LION' ),
			'add_new_item'       => __( 'Add New Email Template', 'LION' ),
			'edit_item'          => __( 'Edit Email Template', 'LION' ),
			'new_item'           => __( 'New Email Template', 'LION' ),
			'search_items'       => __( 'Search Templates', 'LION' ),
			'not_found'          => __( 'No templates found', 'LION' ),
			'not_found_in_trash' => __( 'No templates found in trash', 'LION' ),
		);
		$this->options = array(
			'labels'               => $labels,
			'description'          => __( 'An iThemes Exchange Post Type for storing all Abandoned Cart email templates in the system', 'LION' ),
			'public'               => false,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'show_in_menu'         => false, // We will be adding it manually with various labels based on available product-type add-ons
			'show_in_admin_bar'    => false,
			'register_meta_box_cb' => array( $this, 'meta_box_callback' ),
			'supports'             => array(),
			'capabilities'         => array(
				'edit_posts'        => 'edit_posts',
				'edit_others_posts' => 'edit_others_posts',
				'publish_posts'     => 'publish_posts',
			),
			'map_meta_cap'         => true,
			'capability_type'      => 'post',
		);

		add_action( 'init', array( $this, 'register_the_post_type' ) );
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
		$abandoned_cart_email = it_exchange_get_abandoned_cart( $post );

		// Do action for any product type
		do_action( 'it_exchange_abandoned_cart_email_metabox_callback', $abandoned_cart_email );
	}

	/**
	 * Provides specific hooks for when iThemes Exchange abandoned_carts are saved.
	 *
	 * This method is hooked to save_post. It provides hooks for add-on developers
	 * that will only be called when the post being saved is an iThemes Exchange abandoned_cart.
	 * It provides the following 4 hooks:
	 * - it_exchange_save_abandoned_cart_email_unvalidated // Runs every time an iThemes Exchange abandoned_cart is saved.
	 * - it_exchange_save_abandoned_cart_email             // Runs every time an iThemes Exchange abandoned_cart is saved if not an autosave and if user has permission to save post
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function save_abandoned_cart_email( $post ) {

		// Exit if not it_exchange_prod post_type
		if ( ! 'it_ex_abandond_email' == get_post_type( $post ) )
			return;

		// These hooks fire off any time a it_ex_abandond_email post is saved w/o validations
		do_action( 'it_exchange_save_abandoned_cart_email_unvalidated', $post );

		// Fire off actions with validations that most instances need to use.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! current_user_can( 'edit_post', $post ) )
			return;

		// This is called any time save_post hook
		do_action( 'it_exchange_save_abandoned_cart_email', $post );
	}

	/**
	 * Updates the scheduling on save
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function update_scheduling( $post_id ) {
		if ( empty( $_POST['it-exchange-abandonded-cart-emails-scheduling']['updating'] ) )
			return;

		$human_readable_scheduling = empty( $_POST['it-exchange-abandonded-cart-emails-scheduling'] ) ? array(): $_POST['it-exchange-abandonded-cart-emails-scheduling'];
		$unix_scheduling = false;

		if ( ! empty( $human_readable_scheduling['int'] ) && ! empty( $human_readable_scheduling['unit'] ) ) {
			// Set the base unit
			switch ( $human_readable_scheduling['unit'] ) {
				case 'weeks' :
					$base = WEEK_IN_SECONDS;
					break;
				case 'days' :
					$base = DAY_IN_SECONDS;
					break;
				case 'hours' :
					$base = HOUR_IN_SECONDS;
					break;
				case 'minutes' :
				default        :
					$base = MINUTE_IN_SECONDS;
					break;
			}
			// Multiply the length times the units to get seconds for set frequency
			$unix_scheduling = $human_readable_scheduling['int'] * $base;
		}

		update_post_meta( $post_id, '_it_exchange_abandoned_cart_emails_scheduling', $human_readable_scheduling );
		update_post_meta( $post_id, '_it_exchange_abandoned_cart_emails_scheduling_unix', $unix_scheduling );
	}

	/**
	 * Adds custom columns to the View All abandoned_carts table
	 *
	 * @since 1.0.0
	 * @param array $existing  exisiting columns array
	 * @return array  modified columns array
	*/
	function modify_all_abandoned_cart_emails_table_columns( $existing ) {

		// Remove Checkbox - adding it back below
		if ( isset( $existing['cb'] ) ) {
			$check = $existing['cb'];
			unset( $existing['cb'] );
		}

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
			'cb'                                                   => $check,
			'title'                                                => __( 'Subject', 'LION' ),
			'it_exchange_abandoned_cart_email_status_column'       => __( 'Status', 'LION' ),
			'it_exchange_abandoned_cart_email_scheduling_column'   => __( 'Scheduling', 'LION' ),
			'it_exchange_abandoned_cart_email_delivered_column'    => __( 'Emails Sent', 'LION' ),
			'it_exchange_abandoned_cart_email_opened_column'       => __( 'Opened Rate', 'LION' ),
			'it_exchange_abandoned_cart_email_clickthrough_column' => __( 'Clickthrough Rate', 'LION' ),
			'it_exchange_abandoned_cart_email_recovered_column'    => __( 'Recovered Rate', 'LION' ),
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
	function make_abandoned_cart_email_custom_columns_sortable( $sortables ) {
		$sortables['it_exchange_abandoned_cart_email_status_column']   = 'it_exchange_abandoned_cart_email_status';
		$sortables['it_exchange_abandoned_cart_email_scheduling_column'] = 'it_exchange_abandoned_cart_email_scheduling';
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
	function add_abandoned_cart_email_info_to_view_all_table_rows( $column ) {
		global $post;

		switch( $column ) {
			case 'it_exchange_abandoned_cart_email_status_column' :
				$post_status = get_post_status_object( $post->post_status );
				esc_attr_e( empty( $post_status->label ) ? ucwords( $post->post_status ) : $post_status->label );

				if ( has_shortcode( $post->post_content, 'exchange-abandoned-carts' ) ) {
					echo " &ndash; <span style='color: #dc3232;'>" . __( "Legacy", 'LION' ) . '</span>';
				}
				break;
			case 'it_exchange_abandoned_cart_email_subject_column' :
				esc_attr_e( get_the_title( $post->ID ) );
				break;
			case 'it_exchange_abandoned_cart_email_scheduling_column' :
				echo it_exchange_get_abandoned_cart_email_human_readable_schedule( $post->ID );
				break;
			case 'it_exchange_abandoned_cart_email_delivered_column' :
				echo it_exchange_get_abandoned_cart_email_times_sent( $post->ID );
				break;
			case 'it_exchange_abandoned_cart_email_opened_column' :
				echo it_exchange_get_abandoned_cart_email_opened_rate( $post->ID );
				break;
			case 'it_exchange_abandoned_cart_email_clickthrough_column' :
				echo it_exchange_get_abandoned_cart_email_clickthrough_rate( $post->ID );
				break;
			case 'it_exchange_abandoned_cart_email_recovered_column' :
				echo it_exchange_get_abandoned_cart_email_recovered_rate( $post->ID );
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
	function register_abandoned_cart_email_details_admin_metabox( $post ) {
		// Remove Slug metabox
		remove_meta_box( 'slugdiv', 'it_ex_abandond_email', 'normal' );

		// Remove screen options tab
		add_filter('screen_options_show_screen', '__return_false');

		// Remove Builder Layoutmetabox
		remove_meta_box( 'layout_meta_box', 'it_ex_abandond_email', 'side' );

		// Cart Details
		$title     = __( 'Scheduling', 'LION' );
		$callback  = array( $this, 'print_abandoned_cart_email_scheduling_metabox' );
		$post_type = 'it_ex_abandond_email';
		add_meta_box( 'it-exchange-abandoned-cart-email-scheduling', $title, $callback, $post_type, 'side', 'default' );

		$title     = __( 'Available Tags', 'LION' );
		$callback  = array( $this, 'print_abandoned_cart_email_shortcodes_metabox' );
		add_meta_box( 'it-exchange-abandoned-cart-email-shortcodes', $title, $callback, $post_type, 'normal', 'high' );
	}

	/**
	 * Modifies the title place holder on the edit page
	 *
	 * @since 1.0.0
	 *
	 * @param string $placeholder incoming placeholder from WP
	 * @param object $post the wp post
	 * @return string
	*/
	function modify_title_placeholder( $placeholder, $post ) {
		if (  'it_ex_abandond_email' == get_post_type( $post ) )
			$placeholder = __( 'Enter subject here', 'LION' );

		return $placeholder;
	}

	/**
	 * Prints the abandoned cart details metabox
	 *
	 * @since 1.0.0
	 * @param object $post post object
	 * @return void
	*/
	function print_abandoned_cart_email_scheduling_metabox( $post ) {
		do_action( 'it_exchange_before_abandoned_cart_scheduling' );
		$human_readable = get_post_meta( $post->ID, '_it_exchange_abandoned_cart_emails_scheduling', true );
		$selected_int   = empty( $human_readable['int'] ) ? 1 : $human_readable['int'];
		$selected_unit  = empty( $human_readable['unit'] ) ? 'hours' : $human_readable['unit'];
		?>
		<p><?php _e( 'How long should we wait to send this email after a customer abandons their cart?', 'LION' ); ?></p>
		<select name="it-exchange-abandonded-cart-emails-scheduling[int]">
		<?php
		$ceiling = apply_filters( 'it_exchange_abandoned_carts_allow_minutes_in_schedule', false ) ? 59 : 23;
		for( $i=1;$i<=$ceiling;$i++ ) {
			?><option value="<?php echo $i; ?>" <?php selected( $i, $selected_int ); ?>><?php echo $i; ?></option><?php
		}
		?>
		</select>
		<select name="it-exchange-abandonded-cart-emails-scheduling[unit]">
			<?php if ( apply_filters( 'it_exchange_abandoned_carts_allow_minutes_in_schedule', false ) ) : ?>
			<option value="minutes" <?php selected( 'minutes', $selected_unit ); ?>><?php _e( 'minutes', 'LION' ); ?></option>
			<?php endif; ?>
			<option value="hours" <?php selected( 'hours', $selected_unit ); ?>><?php _e( 'hour(s)', 'LION' ); ?></option>
			<option value="days" <?php selected( 'days', $selected_unit ); ?>><?php _e( 'day(s)', 'LION' ); ?></option>
			<option value="weeks" <?php selected( 'weeks', $selected_unit ); ?>><?php _e( 'week(s)', 'LION' ); ?></option>
		</select>
		<input type="hidden" name="it-exchange-abandonded-cart-emails-scheduling[updating]" value="1" />
		<?php
	}

	/**
	 * Prints the abandoned cart shortcodes metabox
	 *
	 * @since 1.0.0
	 * @param object $post post object
	 * @return void
	*/
	function print_abandoned_cart_email_shortcodes_metabox( $post ) {

		$r = it_exchange_email_notifications()->get_replacer();

		do_action( 'it_exchange_before_abandoned_cart_shortcodes' );
		?>

		<?php if ( $post && has_shortcode( $post->post_content, 'exchange-abandoned-carts' ) ): ?>
			<div class="notice notice-error notice-alt notice-large below-h2">
				<p>
					<?php _e( 'Error: You are using the legacy email system. Upgrade to the new system for HTML templates.', 'LION' ); ?>
					<?php _e('To upgrade, replace usages of the [exchange-abandoned-carts] shortcode, with the email tags listed below.', 'LION'); ?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<strong>
				<?php _e( "Use the following tags in your email template.", 'LION' ); ?>
				<?php _e( "A summary of the customer's cart, and a link back to their cart is included in the email template.", 'LION' ); ?>
			</strong>
		</p>
		<hr>

		<ul>
		<?php foreach ( $r->get_tags_for( new IT_Exchange_Customer_Email_Notification( 'Abandoned Cart', 'abandoned-cart' ) ) as $tag ): ?>
			<li><code><?php echo $r->format_tag( $tag ); ?></code> &mdash; <?php echo $tag->get_description(); ?></li>
		<?php endforeach; ?>
		</ul>

		<?php
	}
}
$IT_Exchange_Abandoned_Cart_Email_Post_Type = new IT_Exchange_Abandoned_Cart_Email_Post_Type();
