<?php

namespace WPML\ST\TranslationFile;

use WP_Filesystem_Direct;
use WPML\Collect\Support\Collection;
use WPML\ST\MO\File\makeDir;
use WPML_Language_Records;
use WPML_ST_Translations_File_Dictionary;
use function wpml_collect;
use WPML_ST_Translations_File_Entry;

abstract class Manager {

	use makeDir;

	const SUB_DIRECTORY = 'wpml';

	/** @var StringsRetrieve $strings */
	protected $strings;
	/** @var WPML_Language_Records $language_records */
	protected $language_records;
	/** @var Builder $builder */
	protected $builder;
	/** @var WPML_ST_Translations_File_Dictionary $file_dictionary */
	protected $file_dictionary;
	/** @var Domains $domains */
	protected $domains;

	public function __construct(
		StringsRetrieve $strings,
		Builder $builder,
		WP_Filesystem_Direct $filesystem,
		WPML_Language_Records $language_records,
		Domains $domains
	) {
		$this->strings          = $strings;
		$this->builder          = $builder;
		$this->filesystem       = $filesystem;
		$this->language_records = $language_records;
		$this->domains          = $domains;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 */
	public function remove( $domain, $locale ) {
		$filepath = $this->getFilepath( $domain, $locale );
		$this->filesystem->delete( $filepath );

		// Delete the translation file .l10n.php along with the .mo file.
		if ( 'mo' === $this->getFileExtension() ) {
			$php_filepath = substr( $filepath, 0, -3 ) . '.l10n.php';
			if ( $this->filesystem->is_file( $php_filepath ) && $this->filesystem->is_readable( $php_filepath ) ) {
				$this->filesystem->delete( $php_filepath );
			}
		}

		do_action(
			'wpml_st_translation_file_removed',
			$filepath,
			$domain,
			$locale
		);
	}

	public function write( $domain, $locale, $content ) {
		$filepath = $this->getFilepath( $domain, $locale );
		$chmod    = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
		if ( ! $this->filesystem->put_contents( $filepath, $content, $chmod ) ) {
			return false;
		}

		do_action(
			'wpml_st_translation_file_written',
			$filepath,
			$domain,
			$locale
		);

		$this->write_php_file_from_mo( $filepath, $chmod );

		return $filepath;
	}

	private function write_php_file_from_mo( $mo_filepath, $chmod ) {
		if (
			! class_exists( '\WP_Translation_File' )
			|| ! method_exists( 'WP_Translation_File', 'transform' )
		) {
			return;
		}

		$content = \WP_Translation_File::transform( $mo_filepath, 'php' );
		if ( ! $content ) {
			return;
		}

		$filepath = str_replace( '.mo', '.l10n.php', $mo_filepath );
		$this->filesystem->put_contents( $filepath, $content, $chmod );
	}

	/**
	 * Builds and saves the .MO file.
	 * Returns false if file doesn't exist, file path otherwise.
	 *
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return false|string
	 */
	public function add( $domain, $locale ) {
		if ( ! $this->maybeCreateSubdir() ) {
			return false;
		}

		$lang_code = $this->language_records->get_language_code( $locale );
		$strings   = $this->strings->get( $domain, $lang_code, $this->isPartialFile() );

		if ( ! $strings && $this->isPartialFile() ) {
			$this->remove( $domain, $locale );
			return false;
		}

		$file_content = $this->builder
			->set_language( $locale )
			->get_content( $strings );

		return $this->write( $domain, $locale, $file_content );
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string|null
	 */
	public function get( $domain, $locale ) {
		$filepath = $this->getFilepath( $domain, $locale );

		if ( $this->filesystem->is_file( $filepath ) && $this->filesystem->is_readable( $filepath ) ) {
			return $filepath;
		}

		return null;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return string
	 */
	public function getFilepath( $domain, $locale ) {
		// Some domains for JS translations can contain '/' - like 'woocommerce-wc-blocks-cart-blocks/order-summary-heading-frontend-chunk'.
		// In such case file with custom JS translations will not be created in '/wp-content/languages/wpml' directory.
		$domain = str_replace( '/', '-', $domain );
		return $this->getSubdir() . '/' . strtolower( $domain ) . '-' . $locale . '.' . $this->getFileExtension();
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function handles( $domain ) {
		return $this->getDomains()->contains( $domain );
	}

	/** @return string */
	public static function getSubdir() {
		$subdir = WP_LANG_DIR . '/' . self::SUB_DIRECTORY;

		$site_id = get_current_blog_id();
		if ( $site_id > 1 ) {
			$subdir .= '/' . $site_id;
		}

		return $subdir;
	}

	/**
	 * @return string
	 */
	abstract protected function getFileExtension();

	/**
	 * @return bool
	 */
	abstract public function isPartialFile();

	/**
	 * @return Collection
	 */
	abstract protected function getDomains();
}
