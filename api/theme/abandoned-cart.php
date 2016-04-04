<?php
/**
 * Theme API for abandoned cart emails.
 *
 * @since   1.3
 * @license GPLv2
 */

/**
 * Class IT_Theme_API_Abandoned_Cart
 */
class IT_Theme_API_Abandoned_Cart implements IT_Theme_API {

	/**
	 * @var IT_Exchange_Abandoned_Cart
	 */
	private $abandoned_cart;

	/**
	 * @var array
	 */
	private $cart;

	/**
	 * @var array
	 */
	private $cart_product = array();

	/**
	 * IT_Theme_API_Abandoned_Cart constructor.
	 */
	public function __construct() {
		$this->abandoned_cart = isset( $GLOBALS['it_exchange']['abandoned_cart'] ) ? $GLOBALS['it_exchange']['abandoned_cart'] : null;

		$this->cart = it_exchange_get_cached_customer_cart( $this->abandoned_cart->customer_id );

		if ( ! empty( $GLOBALS['it_exchange']['abandoned_cart_product'] ) ) {
			$this->cart_product = $GLOBALS['it_exchange']['abandoned_cart_product'];
		}
	}

	/**
	 * @return string
	 */
	function get_api_context() {
		return 'abandoned-cart';
	}

	public $_tag_map = array(
		'storename'        => 'store_name',
		'reclaimlink'      => 'reclaim_link',
		'tracker'          => 'tracker',
		'products'         => 'products',
		'productattribute' => 'product_attribute',
		'variants'         => 'variants',
		'featuredimage'    => 'featured_image',
	);

	/**
	 * Print the store name.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function store_name() {

		$settings = it_exchange_get_option( 'settings_general' );

		return $settings['company-name'];
	}

	/**
	 * Tracker pixel.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function tracker() {
		return '<img src="' . add_query_arg( array( 'it-exchange-cart-summary' => $this->abandoned_cart->email . '-' . $this->abandoned_cart->ID ), get_home_url() ) . '" width="1" height="1" />';
	}

	/**
	 * Generate reclaim link.
	 *
	 * @since 1.3.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function reclaim_link( $options = array() ) {

		$defaults = array(
			'format' => 'url',
			'label'  => __( 'Continue Shopping', 'LION' )
		);

		$options = ITUtility::merge_defaults( $options, $defaults );

		$options['label'] = apply_filters( 'it_exchange_abandoned_carts_continue_shopping_label', $options['label'] );

		switch ( $options['format'] ) {
			case 'label':
				return $options['label'];
			case 'url':
			default:
				return it_exchange_generate_reclaim_link_for_abandoned_email( $this->abandoned_cart->email, $this->abandoned_cart->ID );
		}
	}

	/**
	 * This loops through the abandoned_cart_products GLOBAL and updates the abandoned_cart_product global.
	 *
	 * It return false when it reaches the last product
	 * If the has flag has been passed, it just returns a boolean
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	function products( $options = array() ) {

		// Return boolean if has flag was set
		if ( ! empty( $options['has'] ) ) {
			return ! empty( $this->cart ) && ! empty( $this->cart['products'] ) && count( $this->cart['products'] ) > 0;
		}

		// If we made it here, we're doing a loop of abandoned_cart_products for the current query.
		// This will init/reset the abandoned_cart_products global and loop through them.
		if ( empty( $GLOBALS['it_exchange']['abandoned_cart_products'] ) ) {

			$products = $this->cart['products'];

			$GLOBALS['it_exchange']['abandoned_cart_products'] = $products;
			$GLOBALS['it_exchange']['abandoned_cart_product']  = reset( $GLOBALS['it_exchange']['abandoned_cart_products'] );

			return true;
		} else {
			if ( next( $GLOBALS['it_exchange']['abandoned_cart_products'] ) ) {
				$GLOBALS['it_exchange']['abandoned_cart_product'] = current( $GLOBALS['it_exchange']['abandoned_cart_products'] );

				return true;
			} else {
				$GLOBALS['it_exchange']['abandoned_cart_products'] = array();
				end( $GLOBALS['it_exchange']['abandoned_cart_products'] );
				$GLOBALS['it_exchange']['abandoned_cart_product'] = false;

				return false;
			}
		}
	}

	/**
	 * Use this to get a abandoned cart product attribute like title, description, price, etc.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	function product_attribute( $options = array() ) {

		// Set defaults
		$defaults = array(
			'wrap'         => false,
			'format'       => 'html',
			'attribute'    => false,
			'format_price' => true,
			'class'        => false
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		// Return empty if attribute was not provided
		if ( empty( $options['attribute'] ) ) {
			return '';
		}

		// Return empty string if empty
		if ( 'description' == $options['attribute'] ) {
			$attribute = it_exchange_get_product_feature( $this->cart_product['product_id'], 'description' );
			if ( empty( $attribute ) ) {
				return '';
			}
		} else if ( 'product_price' == $options['attribute'] ) {

			$product_id = $this->cart_product['product_id'];

			if ( ! empty( $this->cart_product['itemized_data'] ) ) {
				$itemized = maybe_unserialize( $this->cart_product['itemized_data'] );

				if ( ! empty( $itemized['it_variant_combo_hash'] ) ) {
					$combo_hash = $itemized['it_variant_combo_hash'];
				}
			}

			$images_located = false;

			if ( isset( $combo_hash ) && function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {

				$variant_combos_data = it_exchange_get_variant_combo_attributes_from_hash( $product_id, $combo_hash );
				$combos_array        = empty( $variant_combos_data['combo'] ) ? array() : $variant_combos_data['combo'];
				$alt_hashes          = it_exchange_addon_get_selected_variant_alts( $combos_array, $product_id );

				$controller = it_exchange_variants_addon_get_product_feature_controller( $product_id, 'base-price', array( 'setting' => 'variants' ) );

				if ( $variant_combos_data['hash'] == $combo_hash ) {
					if ( ! empty( $controller->post_meta[ $combo_hash ]['value'] ) ) {
						$attribute = $controller->post_meta[ $combo_hash ]['value'];
					}
				}
				// Look for alt hashes if direct match was not found
				if ( ! $images_located && ! empty( $alt_hashes ) ) {
					foreach ( $alt_hashes as $alt_hash ) {
						if ( ! empty( $controller->post_meta[ $alt_hash ]['value'] ) ) {
							$attribute = $controller->post_meta[ $alt_hash ]['value'];
						}
					}
				}
			}

			if ( empty( $attribute ) ) {
				$attribute = it_exchange_get_product_feature( $this->cart_product['product_id'], 'base-price' );
			}

			if ( $options['format_price'] ) {
				$attribute = it_exchange_format_price( $attribute );
			}
		} else if ( 'product_count' == $options['attribute'] ) {
			$attribute = $this->cart_product['count'];
		} else if ( 'product_name' == $options['attribute'] ) {
			$attribute = get_the_title( $this->cart_product['product_id'] );
		} else {
			$attribute = '';
		}

		$open_wrap  = empty( $options['wrap'] ) ? '' : '<' . esc_attr( $options['wrap'] ) . ' class="' . $options['class'] . '">';
		$close_wrap = empty( $options['wrap'] ) ? '' : '</' . esc_attr( $options['wrap'] ) . '>';
		$result     = '';

		if ( 'html' == $options['format'] ) {
			$result .= $open_wrap;
		}

		$result .= apply_filters( 'it_exchange_api_theme_abandoned_cart_product_attribute', $attribute, $options, $this->cart, $this->cart_product );

		if ( 'html' == $options['format'] ) {
			$result .= $close_wrap;
		}

		return $result;
	}

	/**
	 * Print the varaints.
	 *
	 * @since 1.3.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	function variants( $options = array() ) {

		$product = $this->cart_product;

		if ( empty( $product['itemized_data'] ) ) {
			return '';
		}

		$itemized_data = maybe_unserialize( $product['itemized_data'] );

		if ( empty( $itemized_data['it_variant_combo_hash'] ) || ! function_exists( 'it_exchange_get_variant_combo_attributes_from_hash' ) ) {

			return '';
		}

		$atts = it_exchange_get_variant_combo_attributes_from_hash( $product['product_id'], $itemized_data['it_variant_combo_hash'] );

		$out = '';

		foreach ( $atts['combo'] as $variant_group => $variant ) {
			$out .= get_the_title( $variant_group ) . ': ' . get_the_title( $variant ) . '<br>';
		}

		return $out;
	}

	/**
	 * The product's featured image
	 *
	 * @since 1.3.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	function featured_image( $options = array() ) {

		// Get the real product item or return empty
		if ( ( ! $product_id = empty( $this->cart_product['product_id'] ) ? false : $this->cart_product['product_id'] ) ) {
			return false;
		}

		// Return boolean if has flag was set
		if ( $options['supports'] ) {
			return it_exchange_product_supports_feature( $product_id, 'product-images' );
		}

		// Return boolean if has flag was set
		if ( $options['has'] ) {
			return it_exchange_product_has_feature( $product_id, 'product-images' );
		}

		$defaults = array(
			'format' => 'html'
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( ( it_exchange_product_supports_feature( $product_id, 'product-images' ) && it_exchange_product_has_feature( $product_id, 'product-images' ) ) ) {

			$defaults = array(
				'size' => 'thumbnail'
			);

			$options = ITUtility::merge_defaults( $options, $defaults );

			if ( ! empty( $this->cart_product['itemized_data'] ) ) {
				$itemized = maybe_unserialize( $this->cart_product['itemized_data'] );

				if ( ! empty( $itemized['it_variant_combo_hash'] ) ) {
					$combo_hash = $itemized['it_variant_combo_hash'];
				}
			}

			$images_located = false;

			if ( isset( $combo_hash ) && function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {

				$variant_combos_data = it_exchange_get_variant_combo_attributes_from_hash( $product_id, $combo_hash );
				$combos_array        = empty( $variant_combos_data['combo'] ) ? array() : $variant_combos_data['combo'];
				$alt_hashes          = it_exchange_addon_get_selected_variant_alts( $combos_array, $product_id );

				$controller = it_exchange_variants_addon_get_product_feature_controller( $product_id, 'product-images', array( 'setting' => 'variants' ) );

				if ( $variant_combos_data['hash'] == $combo_hash ) {
					if ( ! empty( $controller->post_meta[ $combo_hash ]['value'] ) ) {
						$product_images = $controller->post_meta[ $combo_hash ]['value'];
						$images_located = true;
					}
				}
				// Look for alt hashes if direct match was not found
				if ( ! $images_located && ! empty( $alt_hashes ) ) {
					foreach ( $alt_hashes as $alt_hash ) {
						if ( ! empty( $controller->post_meta[ $alt_hash ]['value'] ) ) {
							$product_images = $controller->post_meta[ $alt_hash ]['value'];
							$images_located = true;
						}
					}
				}
			}

			if ( ! $images_located || ! isset( $product_images ) ) {
				$product_images = it_exchange_get_product_feature( $product_id, 'product-images' );
			}

			$feature_image = array(
				'id'    => $product_images[0],
				'thumb' => wp_get_attachment_thumb_url( $product_images[0] ),
				'large' => wp_get_attachment_url( $product_images[0] ),
			);

			if ( is_array( $options['size'] ) ) {
				$img_src = wp_get_attachment_image_url( $product_images[0], $options['size'] );
			} elseif ( 'thumbnail' === $options['size'] ) {
				$img_src = $feature_image['thumb'];
			} else {
				$img_src = $feature_image['large'];
			}

			$img_src = apply_filters( 'it_exchange_theme_api_abandoned_cart_product_featured_image_src', $img_src, $this->cart_product, $this->cart );


			if ( $options['format'] === 'url' ) {
				return $img_src;
			}

			ob_start();
			?>
			<div class="it-exchange-feature-image-<?php echo get_the_ID(); ?> it-exchange-featured-image">
				<div class="featured-image-wrapper">
					<img alt="" src="<?php echo $img_src ?>" data-src-large="<?php echo $feature_image['large'] ?>"
					     data-src-thumb="<?php echo $feature_image['thumb'] ?>" />
				</div>
			</div>
			<?php
			$output = ob_get_clean();

			return $output;
		}

		return false;
	}
}
