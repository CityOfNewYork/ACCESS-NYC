<?php

namespace BulkWP\BulkDelete\Core\Addon;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Upsell Data about an add-on.
 *
 * This is a `Record` class that only contains data about a particular add-on.
 * `Info` suffix is generally considered bad, but this is an exception since the suffix makes sense here.
 *
 * @since 6.0.0
 */
class AddonUpsellInfo extends AddonInfo {
	protected $description;
	protected $slug;
	protected $url;
	protected $buy_url;
	protected $upsell_title;
	protected $upsell_message;

	/**
	 * Construct AddonUpsellInfo from an array.
	 *
	 * @param array $details Details about the add-on.
	 */
	public function __construct( $details = array() ) {
		if ( ! is_array( $details ) ) {
			return;
		}

		parent::__construct( $details );

		$keys = array(
			'description',
			'slug',
			'url',
			'buy_url',
			'upsell_title',
			'upsell_message',
		);

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $details ) ) {
				$this->{$key} = $details[ $key ];
			}
		}
	}

	public function get_description() {
		return $this->description;
	}

	public function get_slug() {
		return $this->slug;
	}

	public function get_url() {
		return $this->url;
	}

	/**
	 * Get the url where users can buy the add-on.
	 *
	 * This url might include GA campaign parameters.
	 * Addon url is used if buy url is not explicitly set.
	 *
	 * @return string Url from where the add-on could be bought or just url if it not set.
	 */
	public function get_buy_url() {
		if ( empty( $this->buy_url ) ) {
			return $this->url;
		}

		return $this->buy_url;
	}

	/**
	 * Get upsell title for the addon.
	 *
	 * Name is used if title is not explicitly set.
	 *
	 * @return string Upsell title for addon or Name if it not set.
	 */
	public function get_upsell_title() {
		if ( empty( $this->upsell_title ) ) {
			return $this->name;
		}

		return $this->upsell_title;
	}

	/**
	 * Get upsell message for the addon.
	 *
	 * Description is used if title is not explicitly set.
	 *
	 * @return string Upsell description for addon or Description if it not set.
	 */
	public function get_upsell_message() {
		if ( empty( $this->upsell_message ) ) {
			return $this->description;
		}

		return $this->upsell_message;
	}
}
