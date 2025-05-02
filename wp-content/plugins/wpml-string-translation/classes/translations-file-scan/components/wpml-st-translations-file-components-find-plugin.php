<?php

class WPML_ST_Translations_File_Components_Find_Plugin implements WPML_ST_Translations_File_Components_Find {
	/** @var WPML_Debug_BackTrace */
	private $debug_backtrace;

	/** @var string */
	private $plugin_dir;

	/** @var array */
	private $plugin_ids;

	/** @var string */
	private $languages_plugin_dir;

	/**
	 * @param WPML_Debug_BackTrace $debug_backtrace
	 */
	public function __construct( WPML_Debug_BackTrace $debug_backtrace ) {
		$this->debug_backtrace = $debug_backtrace;
		$this->plugin_dir = realpath( WPML_PLUGINS_DIR );
		$this->languages_plugin_dir = WP_LANG_DIR . '/plugins/';
	}

	public function find_id( $file ) {
		$directory = $this->find_plugin_directory( $file );
		if ( ! $directory ) {
			return null;
		}

		return $this->get_plugin_id_by_directory( $directory );
	}

	private function find_plugin_directory( $file ) {
		if ( false !== strpos( $file, $this->plugin_dir ) ) {
			return $this->extract_plugin_directory( $file );
		}

		if ( false !== strpos( $file, $this->languages_plugin_dir ) ) {
			return $this->extract_plugin_directory_from_languages_directory( $file );
		}

		return $this->find_plugin_directory_in_backtrace();
	}

	private function find_plugin_directory_in_backtrace() {
		$file = $this->find_file_in_backtrace();
		if ( ! $file ) {
			return null;
		}

		return $this->extract_plugin_directory( $file );
	}

	private function find_file_in_backtrace() {
		$stack = $this->debug_backtrace->get_backtrace();

		foreach ( $stack as $call ) {
			if ( isset( $call['function'] ) && 'load_plugin_textdomain' === $call['function'] ) {
				return $call['file'];
			}
		}

		return null;
	}

	/**
	 * @param string $file_path
	 *
	 * @return string
	 */
	private function extract_plugin_directory( $file_path ) {
		$dir = ltrim( str_replace( $this->plugin_dir, '', $file_path ), DIRECTORY_SEPARATOR );
		$dir = explode( DIRECTORY_SEPARATOR, $dir );

		return trim( $dir[0], DIRECTORY_SEPARATOR );
	}

	/**
	 * @param string $file_path
	 *
	 * Examples: $file_path = "/var/www/mysite/wp-content/languages/plugins/akismet-da_DK.mo".
	 *           $file_path = "/var/www/mysite/wp-content/languages/plugins/akismet-ca.mo".
	 *
	 * @return string
	 */
	private function extract_plugin_directory_from_languages_directory( $file_path ) {
		$parts     = explode( DIRECTORY_SEPARATOR, $file_path );
		$file_name = current( explode( '.', end( $parts ) ) );

		if ( false !== strpos( $file_name, '_' ) ) {
			return substr( current( explode( '_', $file_name ) ), 0, -3 );
		}

		$parts = explode( '-', $file_name );
		array_pop( $parts );

		return implode( '-', $parts );
	}

	/**
	 * @param string $directory
	 *
	 * @return string|null
	 */
	private function get_plugin_id_by_directory( $directory ) {
		foreach ( $this->get_plugin_ids() as $plugin_id ) {
			if ( 0 === strpos( $plugin_id, $directory . '/' ) ) {
				return $plugin_id;
			}
		}

		return null;
	}

	/**
	 * @return string[]
	 */
	private function get_plugin_ids() {
		if ( null === $this->plugin_ids ) {
			$this->plugin_ids = array_keys( get_plugins() );
		}

		return $this->plugin_ids;
	}
}