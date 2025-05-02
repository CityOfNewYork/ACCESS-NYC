<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\File\Manager;
use WPML\ST\MO\LoadedMODictionary;
use WPML_ST_Translations_File_Locale;
use function WPML\FP\partial;
use WPML\LIB\WP\WordPress;

class LoadTranslationFile
{

	private static $isHookLoaded = false;

	private static $fileReplacements = [];

	public static function replaceMoExtensionWithPhp( $mofile ) {
		if ( ! is_string( $mofile ) ) {
			return '';
		}

		return preg_replace( '/\.mo$/', '.l10n.php', $mofile );
	}

	public static function replaceTranslationFile( $domain, $mofile, $replacedMoFile ) {
		if ( ! self::$isHookLoaded ) {
			self::loadHook();
		}
		if ( ! isset( self::$fileReplacements[ $domain ] ) ) {
			self::$fileReplacements[ $domain ] = [];
		}
		self::$fileReplacements[ $domain ][ $mofile ] = $replacedMoFile;
	}


	public static function loadTranslationFile( $file, $domain ) {
		if ( isset( self::$fileReplacements[ $domain ][ $file ] ) ) {
			return self::$fileReplacements[ $domain ][ $file ];
		}
		return $file;
	}


	/**
	 * @param string $domain
	 * @param string $locale
	 * @param bool $disableVersionCheck
	 * @return null|string
	 */
	public static function getDefaultWordPressTranslationPath( $domain, $locale, $disableVersionCheck = false ) {
		if ( ! $disableVersionCheck && ! WordPress::versionCompare('>', '6.6.999') ) {
			return null;
		}
		$defaultPluginTranslation = self::checkTranslationsFolder( 'plugins', $domain, $locale );
		if ( $defaultPluginTranslation ) {
			return $defaultPluginTranslation;
		}
		$defaultThemeTranslation = self::checkTranslationsFolder( 'themes', $domain, $locale );
		if ( $defaultThemeTranslation ) {
			return $defaultThemeTranslation;
		}

		global $wp_textdomain_registry;
		if ( ! isset( $wp_textdomain_registry ) ) {
			return false;
		}
		$defaultPathDirectory =  $wp_textdomain_registry->get( $domain, $locale );

		$defaultPathFile =  "{$defaultPathDirectory}{$domain}-{$locale}.mo";

		$template_directory   = trailingslashit( get_template_directory() );
		$stylesheet_directory = trailingslashit( get_stylesheet_directory() );
		if (
			str_starts_with( $defaultPathDirectory, $template_directory ) ||
			str_starts_with( $defaultPathDirectory, $stylesheet_directory )
		) {
			$defaultPathFile = "{$defaultPathDirectory}{$locale}.mo";
		}

		$defaultPathPHP = self::replaceMoExtensionWithPhp( $defaultPathFile );
		if ( file_exists( $defaultPathFile ) ) {
			return $defaultPathFile;
		}
		if (  file_exists( $defaultPathPHP ) ) {
			return $defaultPathPHP;
		}
		return null;
	}


	private static function loadHook() {
		add_filter( 'load_translation_file', [ __CLASS__, 'loadTranslationFile' ], 10, 2 );
	}


	/**
	 * @param "plugins"|"themes" $prefix
	 * @param string $domain
	 * @param string $locale
	 * @return string|null
	 */
	private static function checkTranslationsFolder( $prefix, $domain, $locale ) {
		$defaultLegacyPath = WP_LANG_DIR . "/$prefix/$domain-$locale.mo";
		if ( file_exists( $defaultLegacyPath ) ) {
			return $defaultLegacyPath;
		}
		$defaultLegacyPathPHP = WP_LANG_DIR . "/$prefix/$domain-$locale.l10n.php";
		if ( file_exists( $defaultLegacyPathPHP ) ) {
			return $defaultLegacyPathPHP;
		}
		return null;
	}

}