<?php
/**
 * Contains the class responsible for sending emails.
 *
 * @since   1.3
 * @license GPLv2
 */

/**
 * Class IT_Exchange_Abandoned_Cart_Emails
 */
class IT_Exchange_Abandoned_Cart_Emails {

	private static $batching = false;
	private static $queue = array();

	/**
	 * Toggle batching of emails.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $batch
	 */
	public static function batch( $batch = true ) {
		self::$batching = $batch;

		if ( ! $batch && self::$queue ) {

			it_exchange_send_email( self::$queue );

			self::$queue = array();
		}
	}

	/**
	 * Send an email.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $abandoned_cart
	 * @param array $email_config
	 *
	 * @return bool
	 */
	public static function send_email( IT_Exchange_Abandoned_Cart $abandoned_cart, array $email_config ) {

		// Get cached cart and set products if it matches
		$cached_cart = it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id );

		if ( empty( $cached_cart ) ) {
			return false;
		}

		$email_id = $email_config['ID'];

		if ( has_shortcode( $email_config['content'], 'exchange-abandoned-carts' ) ) {
			return self::send_legacy( $abandoned_cart, $email_config );
		}

		$customer     = it_exchange_get_customer( $abandoned_cart->customer_id );
		$notification = new IT_Exchange_Customer_Email_Notification( 'Abandoned Cart', 'abandoned-cart', new IT_Exchange_Email_Template( 'abandoned-cart' ), array(
			'defaults' => array(
				'subject' => $email_config['subject'],
				'body'    => $email_config['content']
			)
		) );

		$abandoned_cart        = clone $abandoned_cart;
		$abandoned_cart->email = $email_id;

		$email = new IT_Exchange_Email( new IT_Exchange_Email_Recipient_Customer( $customer ), $notification, array(
			'abandoned-cart' => $abandoned_cart
		) );

		if ( self::$batching ) {
			self::$queue[] = $email;
		} else {
			it_exchange_send_email( $email );
		}

		// After sending the email, add this email to the list of emails sent for this abandoned cart
		$meta = array(
			'email_id'     => $email_id,
			'time_sent'    => time(),
			'to'           => $customer->data->user_email,
			'subject'      => $email->get_subject(),
			'message'      => $email->get_body(),
			'cart_details' => it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id ),
		);
		// Grab existing emails
		$emails_sent = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );

		if ( empty( $emails_sent ) ) {
			$emails_sent = array();
		}

		// Add this email info to the emails_sent array
		$emails_sent[ $email_id ] = $meta;
		// Update the post meta for the abadoned cart
		update_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', $emails_sent );

		// Also update the number of times this email template has been delivered
		$number_sent = get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', true );
		update_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', ( $number_sent + 1 ) );

		return true;
	}

	/**
	 * Legacy sender.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $abandoned_cart
	 * @param array $email
	 *
	 * @return bool
	 */
	protected static function send_legacy( $abandoned_cart, array $email ) {

		$email_id = $email['ID'];

		// Get the user for the email
		$user = get_userdata( $abandoned_cart->customer_id );

		// Get cached cart and set products if it matches
		$cached_cart = it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id );
		$products    = array();
		$cart_value  = empty( $abandoned_cart->cart_value ) ? false : $abandoned_cart->cart_value;
		$first_name  = get_user_meta( $abandoned_cart->customer_id, 'first_name', true );
		$last_name   = get_user_meta( $abandoned_cart->customer_id, 'last_name', true );

		if ( ! empty( $cached_cart['cart_id'][0] ) && $cached_cart['cart_id'][0] == $abandoned_cart->cart_id ) {
			if ( ! empty( $cached_cart['products'] ) ) {
				foreach ( (array) $cached_cart['products'] as $product ) {

					if ( empty( $product['product_id'] ) ) {
						continue;
					}

					$product_title = it_exchange_get_product_feature( $product['product_id'], 'title' );
					$base_price    = it_exchange_get_product_feature( $product['product_id'], 'base-price' );
					if ( ! empty( $product_title ) ) {
						$products[] = array(
							'title' => $product_title,
							'price' => it_exchange_format_price( $base_price )
						);
					}
				}
			}
		}

		// Setup globals for Shortcodes and apply the_content
		$GLOBALS['it_exchange']['abandoned_carts']['shortcode_data'] = array(
			'customer_name'       => empty( $user->data->display_name ) ? __( 'Customer', 'LION' ) : $user->data->display_name,
			'customer_first_name' => empty( $first_name ) ? __( 'Customer', 'LION' ) : $first_name,
			'customer_last_name'  => empty( $last_name ) ? __( '', 'LION' ) : $last_name,
			'cart_link_href'      => it_exchange_generate_reclaim_link_for_abandoned_email( $email_id, $abandoned_cart->ID ),
			'cart_products'       => $products,
			'cart_value'          => $cart_value,
		);
		$email['content'] = apply_filters( 'the_content', $email['content'] );

		unset( $GLOBALS['it_exchange']['abandoned_carts']['shortcode_data'] );

		// Make sure we found the email we're looking for.
		if ( empty( $email ) ) {
			return false;
		}

		// Add tracking code
		$email['content'] .= '<img src="' . add_query_arg( array( 'it-exchange-cart-summary' => $email_id . '-' . $abandoned_cart->ID ), get_home_url() ) . '" width="1" height="1" />';

		// Send the email
		if ( ! empty( $user->data->user_email ) ) {

			$settings = it_exchange_get_option( 'settings_email' );

			$headers = array();
			if ( ! empty( $settings['receipt-email-address'] ) ) {
				$headers[] = 'From: ' . $settings['receipt-email-name'] . ' <' . $settings['receipt-email-address'] . '>';
			}
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'Content-Type: text/html';
			$headers[] = 'charset=utf-8';

			wp_mail( $user->data->user_email, $email['subject'], $email['content'], $headers );

			// After sending the email, add this email to the list of emails sent for this abandoned cart
			$meta = array(
				'email_id'     => $email_id,
				'time_sent'    => time(),
				'to'           => $user->data->user_email,
				'subject'      => $email['subject'],
				'message'      => $email['content'],
				'cart_details' => it_exchange_get_cached_customer_cart( $abandoned_cart->customer_id ),
			);
			// Grab existing emails
			$emails_sent = get_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', true );

			if ( empty( $emails_sent ) ) {
				$emails_sent = array();
			}

			// Add this email info to the emails_sent array
			$emails_sent[ $email_id ] = $meta;
			// Update the post meta for the abadoned cart
			update_post_meta( $abandoned_cart->ID, '_it_exchange_abandoned_cart_emails_sent', $emails_sent );

			// Also update the number of times this email template has been delivered
			$number_sent = get_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', true );
			update_post_meta( $email_id, '_it_exchange_abandoned_cart_emails_sent', ( $number_sent + 1 ) );

			return true;
		}

		return false;
	}
}