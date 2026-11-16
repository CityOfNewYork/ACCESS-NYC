<?php
/**
 * Licensing_Data.
 * Expose licensing information like wpconnect URL and the product ID.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Models;

/**
 * Class Licensing_Data
 */
class Licensing_Data {
	/**
	 * URL to EDD server (e.g https://wpconnect.co).
	 *
	 * @var string
	 */
	private $wpc_url;

	/**
	 * EDD product ID.
	 *
	 * @var int
	 */
	private $wpc_product_id;

	/**
	 * Public product page URL (e.g https://wpconnect.co/air-wp-sync-plugin/).
	 *
	 * @var string
	 */
	private $wpc_product_page_url;

	/**
	 * Constructor.
	 *
	 * @param string $wpc_url URL to EDD server (e.g https://wpconnect.co).
	 * @param int    $wpc_product_id EDD product ID.
	 * @param string $wpc_product_page_url Public product page URL (e.g https://wpconnect.co/air-wp-sync-plugin/).
	 */
	public function __construct( $wpc_url, $wpc_product_id, $wpc_product_page_url ) {
		$this->wpc_url              = $wpc_url;
		$this->wpc_product_id       = $wpc_product_id;
		$this->wpc_product_page_url = $wpc_product_page_url;
	}

	/**
	 * Return URL to EDD server.
	 *
	 * @return string
	 */
	public function get_wpc_url(): string {
		return $this->wpc_url;
	}

	/**
	 * Returns EDD product ID.
	 *
	 * @return int
	 */
	public function get_wpc_product_id(): int {
		return $this->wpc_product_id;
	}

	/**
	 * Returns public product page URL (e.g https://wpconnect.co/air-wp-sync-plugin/).
	 *
	 * @return string
	 */
	public function get_wpc_product_page_url(): string {
		return $this->wpc_product_page_url;
	}
}
