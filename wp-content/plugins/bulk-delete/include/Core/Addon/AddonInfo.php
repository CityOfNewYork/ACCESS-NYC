<?php

namespace BulkWP\BulkDelete\Core\Addon;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Data about an add-on.
 *
 * This is a `Record` class that only contains data about a particular add-on.
 * `Info` suffix is generally considered bad, but this is an exception since the suffix makes sense here.
 *
 * @since 6.0.0
 */
class AddonInfo {
	protected $name;
	protected $code;
	protected $version;
	protected $author = 'Sudar Muthu';
	protected $root_file;

	/**
	 * Construct AddonInfo from an array.
	 *
	 * @param array $details Details about the add-on.
	 */
	public function __construct( $details = array() ) {
		if ( ! is_array( $details ) ) {
			return;
		}

		$keys = array(
			'name',
			'code',
			'version',
			'author',
			'root_file',
		);

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $details ) ) {
				$this->{$key} = $details[ $key ];
			}
		}
	}

	public function get_name() {
		return $this->name;
	}

	public function get_code() {
		return $this->code;
	}

	public function get_version() {
		return $this->version;
	}

	public function get_author() {
		return $this->author;
	}

	public function get_root_file() {
		return $this->root_file;
	}

	/**
	 * Return add-on slug.
	 *
	 * Add-on slug is the name of the root file without file extension.
	 *
	 * @since 6.0.1
	 *
	 * @return string Add-on slug.
	 */
	public function get_addon_slug() {
		return basename( $this->root_file, '.php' );
	}

	public function get_addon_directory() {
		return plugin_dir_path( $this->root_file );
	}

	public function get_addon_directory_url() {
		return plugin_dir_url( $this->root_file );
	}
}
