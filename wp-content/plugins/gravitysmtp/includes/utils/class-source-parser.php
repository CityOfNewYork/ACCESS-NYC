<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

/**
 * Source_Parser
 *
 * Takes the trace from a debug_backtrace() call and parses it to find the source
 * of a wp_mail() call (if present).
 */
class Source_Parser {

	/**
	 * Get the source of a wp_mail call from a given trace.
	 *
	 * @since 1.0
	 *
	 * @param array $trace
	 *
	 * @return string
	 */
	public function get_source_from_trace( $trace ) {
		$relevant_trace = $this->get_relevant_trace_data( $trace );

		if ( ! $relevant_trace ) {
			return __( 'N/A', 'gravitysmtp' );
		}

		$file_path = $relevant_trace['file'];

		return $this->get_source_from_file_path( $file_path );
	}

	/**
	 * Get the relevant wp_mail trace data from a given trace array.
	 *
	 * @since 1.0
	 *
	 * @param array $trace
	 *
	 * @return boolean|array
	 */
	protected function get_relevant_trace_data( $trace ) {
		$filtered = array_filter( $trace, function ( $item ) {
			return $item['function'] === 'wp_mail';
		} );

		if ( empty( $filtered ) ) {
			return false;
		}

		return reset( $filtered );
	}

	/**
	 * For a given file path, determine the source of the call (WordPress core, a theme, a plugin/mu-plugin, or N/A if not found)
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	protected function get_source_from_file_path( $path ) {
		$core = $this->get_core_source( $path );

		if ( $core ) {
			return $core;
		}

		$theme = $this->get_theme_source( $path );

		if ( $theme ) {
			return $theme;
		}

		$plugin = $this->get_plugin_source( $path );

		if ( $plugin ) {
			return $plugin;
		}

		$mu_plugin = $this->get_mu_plugin_source( $path );

		if ( $mu_plugin ) {
			return $mu_plugin;
		}

		return __( 'N/A', 'gravitysmtp' );
	}

	/**
	 * Determine if WordPress Core was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|boolean
	 */
	protected function get_core_source( $path ) {
		if (
			strpos( $path, 'wp-admin' ) !== false ||
			strpos( $path, 'wp-includes' ) !== false
		) {
			return __( 'WordPress', 'gravitysmtp' );
		}

		return false;
	}

	/**
	 * Determine if a plugin was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|boolean
	 */
	protected function get_plugin_source( $path ) {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}

		$root      = basename( WP_PLUGIN_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root$separator(.[^$separator]+)($separator|\.php)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			$all_plugins = \get_plugins();
			$plugin_slug = $result[1];

			$filtered = array_filter( $all_plugins, function ( $plugin_data, $plugin ) use ( $plugin_slug ) {
				return 1 === preg_match( "/^$plugin_slug(\/|\.php)/", $plugin ) && isset( $plugin_data['Name'] );
			}, ARRAY_FILTER_USE_BOTH );

			if ( ! empty( $filtered ) ) {
				$found = reset( $filtered );

				return $found['Name'];
			}

			return $result[1];
		}

		return false;
	}

	/**
	 * Determine if an MU-plugin was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|boolean
	 */
	protected function get_mu_plugin_source( $path ) {
		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return false;
		}

		$root      = basename( WPMU_PLUGIN_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root$separator(.[^$separator]+)($separator|\.php)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			return __( 'MU Plugin' );
		}

		return false;
	}

	/**
	 * Determine if a theme was the source.
	 *
	 * @since 1.0
	 *
	 * @param string $path
	 *
	 * @return string|boolean
	 */
	protected function get_theme_source( $path ) {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return false;
		}

		$root      = basename( WP_CONTENT_DIR );
		$separator = defined( 'DIRECTORY_SEPARATOR' ) ? '\\' . DIRECTORY_SEPARATOR : '\/';

		preg_match( "/$separator$root{$separator}themes{$separator}(.[^$separator]+)/", $path, $result );

		if ( ! empty( $result[1] ) ) {
			$theme = \wp_get_theme( $result[1] );

			return method_exists( $theme, 'get' ) ? $theme->get( 'Name' ) : $result[1];
		}

		return false;
	}

}