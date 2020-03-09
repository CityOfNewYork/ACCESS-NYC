<?php
/**
 * WPML_BBPress_Filters class file.
 *
 * @package WPML\Core
 */

/**
 * Class WPML_BBPress_Filters
 */
class WPML_BBPress_Filters {

	/**
	 * WPML_BBPress_API instance.
	 *
	 * @var WPML_BBPress_API
	 */
	private $wpml_bbpress_api;

	/**
	 * WPML_BBPress_Filters constructor.
	 *
	 * @param WPML_BBPress_API $wpml_bbpress_api WPML_BBPress_API instance.
	 */
	public function __construct( $wpml_bbpress_api ) {
		$this->wpml_bbpress_api = $wpml_bbpress_api;
	}

	/**
	 * Destruct instance.
	 */
	public function __destruct() {
		$this->remove_hooks();
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		add_filter( 'author_link', array( $this, 'author_link_filter' ), 10, 3 );
	}

	/**
	 * Remove hooks.
	 */
	public function remove_hooks() {
		remove_filter( 'author_link', array( $this, 'author_link_filter' ), 10 );
	}

	/**
	 * Author link filter.
	 *
	 * @param string $link            Author link.
	 * @param int    $author_id       Author id.
	 * @param string $author_nicename Author nicename.
	 *
	 * @return mixed
	 */
	public function author_link_filter( $link, $author_id, $author_nicename ) {
		if (
			doing_action( 'wpseo_head' ) ||
			doing_action( 'wp_head' ) ||
			doing_filter( 'wpml_active_languages' )
		) {
			return $this->wpml_bbpress_api->bbp_get_user_profile_url( $author_id, $author_nicename );
		}

		return $link;
	}
}
