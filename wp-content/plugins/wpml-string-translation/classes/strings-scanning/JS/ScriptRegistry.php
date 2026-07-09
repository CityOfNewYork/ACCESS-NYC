<?php

namespace WPML\ST\StringsScanning\JS;

use WPML\WP\OptionManager;

class ScriptRegistry {

	const OPTION_GROUP           = 'js_script_registry';
	const OPTION_KEY_PATHS       = 'paths';
	const OPTION_KEY_TEXTDOMAINS = 'textdomains';

	/**
	 * @param array $scriptsMap
	 *
	 * @return void
	 */
	public static function register( $scriptsMap ) {
		$persistedPaths       = self::getPersistedPaths();
		$persistedTextDomains = self::getPersistedTextdomains();

		foreach ( (array) $scriptsMap as $handle => $src ) {
			$data = self::getRelativeFilePathAndDomain( $handle, $src );

			if ( $data ) {
				list( $path, $textDomain ) = $data;
				$persistedPaths[ $handle ] = $path;

				if ( $textDomain ) {
					// Many scripts don't have a text domain, we'll store only existing ones.
					$persistedTextDomains[ $handle ] = $textDomain;
				}
			}
		}

		$persistedPaths       = array_filter( $persistedPaths );
		$persistedTextDomains = array_filter( $persistedTextDomains );

		OptionManager::updateWithoutAutoLoad( self::OPTION_GROUP, self::OPTION_KEY_PATHS, $persistedPaths );
		OptionManager::updateWithoutAutoLoad( self::OPTION_GROUP, self::OPTION_KEY_TEXTDOMAINS, $persistedTextDomains );
	}

	/**
	 * @param string $handle
	 * @param string $src
	 *
	 * @return string[]|null
	 */
	private static function getRelativeFilePathAndDomain( $handle, $src ) {
		/** @var \WP_Scripts $wp_scripts */
		$wp_scripts = wp_scripts();

		$dep = $wp_scripts->registered[ $handle ] ?? null;
		$src = $dep->src ?? $src;

		if ( ! $src ) {
			return null; // nothing to resolve (inline or data-only).
		}

		// Build absolute URL from base_url when needed.
		if ( 0 === strpos( $src, '//' ) ) {
			$absURL = ( is_ssl() ? 'https:' : 'http:' ) . $src;
		} elseif ( preg_match( '#^https?://#i', $src ) ) {
			$absURL = $src;
		} elseif ( '/' === $src[0] ) { // starts with / -> site root relative
			$absURL = home_url( $src );
		} else { // relative to base_url in WP_Scripts
			$absURL = trailingslashit( $wp_scripts->base_url ) . $src;
		}

		$absHost  = wp_parse_url( $absURL, PHP_URL_HOST );
		$homeHost = wp_parse_url( home_url(), PHP_URL_HOST );

		$isExternalHost = $absHost && $homeHost && strcasecmp( $absHost, $homeHost ) !== 0;

		if ( $isExternalHost ) {
			return null;
		}

		$relURL  = wp_make_link_relative( $absURL );
		$relURL  = ltrim( (string) strtok( $relURL, '?' ) , '/' );
		$absPath = ABSPATH . $relURL;

		if ( file_exists( $absPath ) ) {
			$textdomain = $dep->textdomain ?? '';
			$textdomain = 'default' !== $textdomain ? $textdomain : '';

			return [ wp_normalize_path( $relURL ), $textdomain ];
		}

		return null;
	}

	/**
	 * @param string $id
	 * @param string $type
	 *
	 * @return string[]
	 */
	public static function getAbsScriptPathsForComponents( $id, $type ) {
		$needle = null;

		if ( 'plugin' === $type ) {
			$parts  = explode( '/', $id );
			$needle = wp_normalize_path( WP_PLUGIN_DIR . '/' . $parts[0] . '/' ) ;
		} elseif ( 'theme' === $type ) {
			$needle = wp_normalize_path( get_theme_root() . '/' . $id . '/' );
		}

		if ( $needle ) {
			return wpml_collect( self::getPersistedPaths() )
				->filter( function( $relFilePath ) use ( $needle ) {
					$haystack = ABSPATH . wp_normalize_path( $relFilePath );

					return strpos( $haystack, $needle ) === 0;
				} )
				->map( function( $relFilePath ) {
					return ABSPATH . $relFilePath;
				} )
				->values()
				->toArray();
		}

		return [];
	}

	/**
	 * @param string $absPath
	 *
	 * @return array<string,string>
	 */
	public static function getHandleAndTextdomainByPath( $absPath ) {
		$handle = wpml_collect( self::getPersistedPaths() )
			->filter( function( $relPath ) use ( $absPath ) {
				return strpos( $absPath, $relPath ) !== false;
			} )
			->keys()
			->first();

		$textdomain = wpml_collect( self::getPersistedTextdomains() )
			->filter( function( $textdomain, $keyHandle ) use ( $handle ) {
				return $keyHandle === $handle;
			} )
			->first();

		return [ $handle, $textdomain ];
	}

	/**
	 * @return array
	 */
	private static function getPersistedPaths() {
		return (array) OptionManager::getOr( [], self::OPTION_GROUP, self::OPTION_KEY_PATHS );
	}

	/**
	 * @return array
	 */
	private static function getPersistedTextdomains() {
		return (array) OptionManager::getOr( [], self::OPTION_GROUP, self::OPTION_KEY_TEXTDOMAINS );
	}
}
