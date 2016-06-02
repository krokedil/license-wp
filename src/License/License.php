<?php

namespace Never5\LicenseWP\License;

/**
 * Class License
 * @package Never5\LicenseWP\License
 */
class License {

	/** @var string */
	private $key = '';

	/** @var int */
	private $order_id = 0;

	/** @var int */
	private $user_id = 0;

	/** @var string */
	private $activation_email = '';

	/** @var int */
	private $product_id = 0;

	/** @var int */
	private $activation_limit = 0;

	/** @var \DateTime */
	private $date_created;

	/** @var \DateTime|bool */
	private $date_expires = false;

	/**
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function set_key( $key ) {
		$this->key = $key;
	}

	/**
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * @param int $order_id
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * @return int
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/**
	 * @return string
	 */
	public function get_activation_email() {
		return $this->activation_email;
	}

	/**
	 * @param string $activation_email
	 */
	public function set_activation_email( $activation_email ) {
		$this->activation_email = $activation_email;
	}

	/**
	 * @return int
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * @param int $product_id
	 */
	public function set_product_id( $product_id ) {
		$this->product_id = $product_id;
	}

	/**
	 * @return int
	 */
	public function get_activation_limit() {
		return $this->activation_limit;
	}

	/**
	 * @param int $activation_limit
	 */
	public function set_activation_limit( $activation_limit ) {
		$this->activation_limit = $activation_limit;
	}

	/**
	 * @return \DateTime
	 */
	public function get_date_created() {
		return $this->date_created;
	}

	/**
	 * @param \DateTime $date_created
	 */
	public function set_date_created( $date_created ) {
		$this->date_created = $date_created;
	}

	/**
	 * @return \DateTime
	 */
	public function get_date_expires() {
		return apply_filters('license_wp_date_expires', $this->date_expires, $this->order_id, $this->product_id );
	}

	/**
	 * @param \DateTime|bool $date_expires or false if never expires
	 */
	public function set_date_expires( $date_expires ) {
		$this->date_expires = $date_expires;
	}

	/**
	 * Return if a license has expired
	 *
	 * @return bool
	 */
	public function is_expired() {

		// check if license expired
		if ( $this->get_date_expires() && $this->get_date_expires() < new \DateTime() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get API products this license gives access to
	 *
	 * @return array<\Never5\LicenseWP\ApiProduct\ApiProduct>
	 */
	public function get_api_products() {

		// get correct product id (variations etc.)
		if ( 'product_variation' === get_post_type( $this->get_product_id() ) ) {
			$variation  = get_post( $this->get_product_id() );
			$product_id = $variation->post_parent;
		} else {
			$product_id = $this->get_product_id();
		}

		// get the api product ids
		$api_product_ids = (array) json_decode( get_post_meta( $product_id, '_api_product_permissions', true ) );

		// array that stores the api products
		$api_products = array();

		// check and loop
		if ( is_array( $api_product_ids ) && count( $api_product_ids ) > 0 ) {
			foreach ( $api_product_ids as $api_product_id ) {

				// create ApiProduct objects and store them in array
				$api_products[] = license_wp()->service( 'api_product_factory' )->make( $api_product_id );
			}
		}

		// return array
		return array_filter( $api_products );
	}

	/**
	 * Get API product of license by slug
	 *
	 * @param $slug
	 *
	 * @return \Never5\LicenseWP\ApiProduct\ApiProduct
	 */
	public function get_api_product_by_slug( $slug ) {
		$api_products = $this->get_api_products();

		if ( count( $api_products ) > 0 ) {
			foreach ( $api_products as $api_product ) {
				if ( $api_product->get_slug() == $slug ) {
					return $api_product;
				}
			}
		}

		return null;
	}

	/**
	 * Get activations of license
	 * Uses Activations\Manager:get_activations
	 *
	 * @param \Never5\LicenseWP\ApiProduct\ApiProduct $api_product
	 *
	 * @return array
	 */
	public function get_activations( $api_product = null ) {
		return license_wp()->service( 'activation_manager' )->get_activations( $this, $api_product );
	}

	/**
	 * Return renewal URL
	 *
	 * @return string
	 */
	public function get_renewal_url() {
		return apply_filters( 'license_wp_license_renewal_url', add_query_arg( array(
			'renew_license'    => $this->get_key(),
			'activation_email' => $this->get_activation_email()
		), apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) ) ), $this );
	}

}
