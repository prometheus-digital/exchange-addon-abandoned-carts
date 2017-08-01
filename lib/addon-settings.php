<?php
/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to
 * save / retreive options. Add-ons are not required to do this.
*/

/**
 * This is the function registered in the options array when it_exchange_register_addon was called for Abandoned_Carts
 *
 * It tells Exchange where to find the settings page
 *
 * @return void
*/
function it_exchange_abandoned_carts_addon_settings_callback() {
    $IT_Exchange_Abandoned_Carts_Add_On = new IT_Exchange_Abandoned_Carts_Add_On();
    $IT_Exchange_Abandoned_Carts_Add_On->print_settings_page();
}

/**
 * Class for Abandoned_Carts
 * @since 1.0.0
*/
class IT_Exchange_Abandoned_Carts_Add_On {

    /**
     * @var boolean $_is_admin true or false
     * @since 1.0.0
    */
    var $_is_admin;

    /**
     * @var string $_current_page Current $_GET['page'] value
     * @since 1.0.0
    */
    var $_current_page;

    /**
     * @var string $_current_add_on Current $_GET['add-on-settings'] value
     * @since 1.0.0
    */
    var $_current_add_on;

    /**
     * @var string $status_message will be displayed if not empty
     * @since 1.0.0
    */
    var $status_message;

    /**
     * @var string $error_message will be displayed if not empty
     * @since 1.0.0
    */
    var $error_message;

    // this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
    // const EXCHANGE_2CHECKOUT_STORE_URL = 'https://exchangewp.com';
    // the name of your product. This should match the download name in EDD exactly
    // const EXCHANGE_2CHECKOUT_ITEM_NAME = 'abandoned_carts';
    // the name of the settings page for the license input to be displayed
    // const EXCHANGE_2CHECKOUT_PLUGIN_LICENSE_PAGE = 'abandoned_carts-license';

    /**
     * Set up the class
     *
     * @since 1.0.0
    */
    function __construct() {
        $this->_is_admin       = is_admin();
        $this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
        $this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];
        // $this->license = get_option( 'exchange_abandoned_carts_license_key' );
        // $this->exstatus  = trim( get_option( 'exchange_abandoned_carts_license_status' ) );

        if ( !empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'abandoned_carts' == $this->_current_add_on ) {
            add_action( 'it_exchange_save_add_on_settings_abandoned_carts', array( $this, 'save_settings' ) );
            do_action( 'it_exchange_save_add_on_settings_abandoned_carts' );
        }

    }

    /**
     * Prints settings page
     *
     * @since 1.0.0
    */
    function print_settings_page() {
        $settings = it_exchange_get_option( 'addon_abandoned_carts', true );
        $form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
        $form_options = array(
            'id'      => apply_filters( 'it_exchange_add_on_abandoned_carts', 'it-exchange-add-on-abandoned_carts-settings' ),
            'enctype' => apply_filters( 'it_exchange_add_on_abandoned_carts_settings_form_enctype', false ),
            'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=abandoned-carts',
        );
        $form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-abandoned_carts' ) );

        if ( !empty ( $this->status_message ) )
            ITUtility::show_status_message( $this->status_message );
        if ( !empty( $this->error_message ) )
            ITUtility::show_error_message( $this->error_message );

        ?>
        <div class="wrap">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'Abandoned Carts Settings', 'LION' ); ?></h2>

            <?php do_action( 'it_exchange_paypa-pro_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <?php $form->start_form( $form_options, 'it-exchange-abandoned_carts-settings' ); ?>
                <?php do_action( 'it_exchange_abandoned_carts_settings_form_top' ); ?>
                <?php $this->get_form_table( $form, $form_values ); ?>
                <?php do_action( 'it_exchange_abandoned_carts_settings_form_bottom' ); ?>
                <p class="submit">
                    <?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
                </p>
            <?php $form->end_form(); ?>
            <?php do_action( 'it_exchange_abandoned_carts_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php
    }

    /**
     * Builds Settings Form Table
     *
     * @since 1.0.0
     */
    function get_form_table( $form, $settings = array() ) {

        if ( !empty( $settings ) ) {
            foreach ( $settings as $key => $var ) {
                $form->set_option( $key, $var );
    			}
    		}

        if ( !empty( $_GET['page'] ) && 'it-exchange-setup' == $_GET['page'] ) : ?>
            <h3><?php _e( 'Abandoned_Carts', 'LION' ); ?></h3>
        <?php endif; ?>
        <div class="it-exchange-addon-settings it-exchange-abandoned_carts-addon-settings">
            <p>
                <?php _e( 'Add your license key below.', 'LION' ); ?>
            </p>
            <h4>License Key</h4>
            <?php
                $exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_addon_abandoned_carts' );
                // $license = $exchangewp_abandoned_carts_options['abandoned_carts_license'];
                // var_dump($license);
                $exstatus = trim( get_option( 'exchange_abandoned_carts_license_status' ) );
                // var_dump($exstatus);
             ?>
            <p>
              <label class="description" for="exchange_abandoned_carts_license_key"><?php _e('Enter your license key'); ?></label>
              <!-- <input id="abandoned_carts_license" name="it-exchange-add-on-abandoned_carts-abandoned_carts_license" type="text" value="<?php #esc_attr_e( $license ); ?>" /> -->
              <?php $form->add_text_box( 'abandoned_carts_license' ); ?>
              <span>
                <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
    							<span style="color:green;"><?php _e('active'); ?></span>
    							<?php wp_nonce_field( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ); ?>
    							<input type="submit" class="button-secondary" name="exchange_abandoned_carts_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
    						<?php } else {
    							wp_nonce_field( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ); ?>
    							<input type="submit" class="button-secondary" name="exchange_abandoned_carts_license_activate" value="<?php _e('Activate License'); ?>"/>
    						<?php } ?>
              </span>
            </p>
        <?php
    }

    /**
     * Save settings
     *
     * @since 1.0.0
     * @return void
    */
    function save_settings() {
        $defaults = it_exchange_get_option( 'addon_abandoned_carts' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

        // Check nonce
        if ( !wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-abandoned_carts-settings' ) ) {
            $this->error_message = __( 'Error. Please try again', 'LION' );
            return;
        }

        $errors = apply_filters( 'it_exchange_add_on_abandoned_carts_validate_settings', $this->get_form_errors( $new_values ), $new_values );
        if ( !$errors && it_exchange_save_option( 'addon_abandoned_carts', $new_values ) ) {
            ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
        } else if ( $errors ) {
            $errors = implode( '<br />', $errors );
            $this->error_message = $errors;
        } else {
            $this->status_message = __( 'Settings not saved.', 'LION' );
        }

        // This is for all things licensing check
        // listen for our activate button to be clicked
      	if( isset( $_POST['exchange_abandoned_carts_license_activate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ) )
      			return; // get out if we didn't click the Activate button

      		// retrieve the license from the database
      		// $license = trim( get_option( 'exchange_abandoned_carts_license_key' ) );
          $exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_addon_abandoned_carts' );
          $license = trim( $exchangewp_abandoned_carts_options['abandoned_carts_license'] );

      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'activate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'abandoned_carts' ), // the name of our product in EDD
      			'url'        => home_url()
      		);

      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      		} else {

      			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

      			if ( false === $license_data->success ) {

      				switch( $license_data->error ) {

      					case 'expired' :

      						$message = sprintf(
      							__( 'Your license key expired on %s.' ),
      							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
      						);
      						break;

      					case 'revoked' :

      						$message = __( 'Your license key has been disabled.' );
      						break;

      					case 'missing' :

      						$message = __( 'Invalid license.' );
      						break;

      					case 'invalid' :
      					case 'site_inactive' :

      						$message = __( 'Your license is not active for this URL.' );
      						break;

      					case 'item_name_mismatch' :

      						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'abandoned_carts' );
      						break;

      					case 'no_activations_left':

      						$message = __( 'Your license key has reached its activation limit.' );
      						break;

      					default :

      						$message = __( 'An error occurred, please try again.' );
      						break;
      				}

      			}

      		}

      		// Check if anything passed on a message constituting a failure
      		if ( ! empty( $message ) ) {
      			return;
      		}

      		//$license_data->license will be either "valid" or "invalid"
      		update_option( 'exchange_abandoned_carts_license_status', $license_data->license );
      		// wp_redirect( admin_url( 'admin.php?page=' . 'abandoned_carts-license' ) );
      		// exit();
          return;
      	}

        // deactivate here
        // listen for our activate button to be clicked
      	if( isset( $_POST['exchange_abandoned_carts_license_deactivate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ) )
      			return; // get out if we didn't click the Activate button

      		// retrieve the license from the database
      		// $license = trim( get_option( 'exchange_abandoned_carts_license_key' ) );

          $exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_addon_abandoned_carts' );
          $license = $exchangewp_abandoned_carts_options['abandoned_carts_license'];


      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'deactivate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'abandoned_carts' ), // the name of our product in EDD
      			'url'        => home_url()
      		);
      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      			// $base_url = admin_url( 'admin.php?page=' . 'abandoned_carts-license' );
      			// $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

      			// wp_redirect( 'admin.php?page=abandoned_carts-license' );
      			// exit();
            return;
      		}

      		// decode the license data
      		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
      		// $license_data->license will be either "deactivated" or "failed"
      		if( $license_data->license == 'deactivated' ) {
      			delete_option( 'exchange_abandoned_carts_license_status' );
      		}

      		// wp_redirect( admin_url( 'admin.php?page=' . 'abandoned_carts-license' ) );
      		// exit();
          return;

      	}

    }

    /**
     * This is a means of catching errors from the activation method above and displaying it to the customer
     *
     * @since 1.2.2
     */
    function exchange_abandoned_carts_admin_notices() {
    	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

    		switch( $_GET['sl_activation'] ) {

    			case 'false':
    				$message = urldecode( $_GET['message'] );
    				?>
    				<div class="error">
    					<p><?php echo $message; ?></p>
    				</div>
    				<?php
    				break;

    			case 'true':
    			default:
    				// Developers can put a custom success message here for when activation is successful if they way.
    				break;

    		}
    	}
    }

    /**
     * Validates for values
     *
     * Returns string of errors if anything is invalid
     *
     * @since 1.0.0
     * @return array
    */
    public function get_form_errors( $values ) {

        $errors = array();

		if ( empty( $values['abandoned_carts_sid'] ) )
            $errors[] = __( 'Please include your Abandoned_Carts SID', 'LION' );

        if ( empty( $values['abandoned_carts_secret'] ) )
            $errors[] = __( 'Please include your Abandoned_Carts Secret Word', 'LION' );

        return $errors;

    }

}
