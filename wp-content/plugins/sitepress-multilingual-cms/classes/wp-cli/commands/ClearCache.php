<?php
namespace WPML\CLI\Core\Commands;

class ClearCache implements ICommand {

	/**
	 * @var \WPML_Cache_Directory
	 */
	private $cache_directory;

	public function __construct( \WPML_Cache_Directory $cache_directory ) {
		$this->cache_directory = $cache_directory;
	}

	/**
	 * Clear the WPML cache
	 *
	 * ## EXAMPLE
	 *
	 *     wp wpml clear-cache
	 *
	 * @when wpml_loaded
	 *
	 * {@inheritDoc}
	 */
	public function __invoke( $args, $assoc_args ) {
		icl_cache_clear();
		$this->cache_directory->remove();

		\WP_CLI::success( 'WPML cache cleared' );
	}

	/**
	 * @return string
	 */
	public function get_command() {
		return 'clear-cache';
	}

}