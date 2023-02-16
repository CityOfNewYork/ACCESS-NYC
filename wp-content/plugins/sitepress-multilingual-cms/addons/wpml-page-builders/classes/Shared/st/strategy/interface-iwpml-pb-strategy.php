<?php

interface IWPML_PB_Strategy {

	/**
	 * @param \WP_Post|stdClass $post
	 */
	public function register_strings( $post );

	/**
	 * @param int $post_id
	 * @param string $content
	 * @param WPML\PB\Shortcode\StringCleanUp $stringCleanUp
	 *
	 * @return bool - true if strings were added.
	 */
	public function register_strings_in_content( $post_id, $content, WPML\PB\Shortcode\StringCleanUp $stringCleanUp );

	/**
	 * @param WPML_PB_Factory $factory
	 *
	 */
	public function set_factory( $factory );

	public function get_package_key( $page_id );
	public function get_package_kind();
	public function get_update_post( $package_data );
	public function get_content_updater();
	public function get_package_strings( $package_data );
	public function remove_string( $string_data );
	public function migrate_location( $post_id, $post_content );
}
