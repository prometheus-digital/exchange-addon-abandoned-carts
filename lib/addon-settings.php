<?php
/**
 * Callback function for add-on settings
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_abandoned_carts_addon_settings_callback() {
	// Store Owners should never arrive here. Add a link just in case the do somehow
	?>
	<div class="wrap">
		<?php ITUtility::screen_icon( 'it-exchange' ); ?>
		<h2><?php _e( 'Abandoned Carts License', 'LION' ); ?></h2>
		<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

		<?php
		$exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_abandoned_carts-addon' );
		$license = trim( $exchangewp_abandoned_carts_options['abandoned_carts-license-key'] );
		// var_dump($license);
		$exstatus = trim( get_option( 'exchange_abandoned_carts_license_status' ) );
		//  var_dump($exstatus);

		$after_license = wp_nonce_field( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' );

		if( $exstatus !== false && $exstatus == 'valid' ) {

			$after_license .= '<span style="color:green;">active</span>';
			$after_license .= '<input type="submit" class="button-secondary" name="exchange_abandoned_carts_license_deactivate" value="Deactivate License"/>';
		} else {
			$after_license .= '<input type="submit" class="button-secondary" name="exchange_abandoned_carts_license_activate" value="Activate License"/>';
		}

		$options = array(
			'prefix'      => 'abandoned_carts-addon',
			'form-fields' => array(
				array(
					'type'	=> 'heading',
					'label' => __('License Key', 'LION' ),
					'slug' => 'abandoned_carts-license-key-heading',
				),
				array(
					'type' => 'text_box',
					'label' => __('Enter License Key', 'LION'),
					'slug' => 'abandoned_carts-license-key',
					'after' => $after_license,
				),
			),
		);
		it_exchange_print_admin_settings_form( $options );
		?>
	</div>
	<?php
}

function exchange_abandoned_carts_license_activate() {

	if( isset( $_POST['exchange_abandoned_carts_license_activate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_abandoned_carts_license_key' ) );
	   $exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_abandoned_carts-addon' );
	   $license = trim( $exchangewp_abandoned_carts_options['abandoned_carts-license-key'] );

			// 	var_dump($license);
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'abandoned-carts' ), // the name of our product in EDD
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
				$base_url = admin_url( 'admin.php?page=' . 'abandoned-carts' );
				$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( $redirect );
				exit();
			}

			//$license_data->license will be either "valid" or "invalid"
			update_option( 'exchange_abandoned_carts_license_status', $license_data->license );
			wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=abandoned-carts' ) );
			exit();
		}

}
add_action('admin_init', 'exchange_abandoned_carts_license_deactivate');
add_action('admin_init', 'exchange_abandoned_carts_license_activate');

function exchange_abandoned_carts_license_deactivate() {

	 // deactivate here
	 // listen for our activate button to be clicked
		if( isset( $_POST['exchange_abandoned_carts_license_deactivate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_abandoned_carts_nonce', 'exchange_abandoned_carts_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_abandoned_carts_license_key' ) );

			$exchangewp_abandoned_carts_options = get_option( 'it-storage-exchange_abandoned_carts-addon' );
 	    $license = trim( $exchangewp_abandoned_carts_options['abandoned_carts-license-key'] );



			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'abandoned-carts' ), // the name of our product in EDD
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

				wp_redirect( 'admin.php?page=it-exchange-addons&add-on-settings=abandoned-carts' );
				exit();
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' ) {
				delete_option( 'exchange_abandoned_carts_license_status' );
			}

			wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=abandoned-carts' ) );
			exit();

		}
}

/**
 * Set default settings if empty
 *
 * @since 1.0.0
 *
 * @return
*/
function it_exchange_abandoned_carts_addon_set_default_options() {
	$defaults = it_exchange_abandoned_carts_addon_get_default_settings();
	$current  = it_exchange_get_option( 'abandoned_carts-addon' );

	if ( empty( $current ) ) {
		it_exchange_save_option( 'abandoned_carts-addon', $defaults );
	}
}
