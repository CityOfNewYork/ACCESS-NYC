<?php

namespace WPML\ST\MO\File;

use GlobIterator;
use WPML\Collect\Support\Collection;
use WPML\ST\TranslationFile\Domains;
use WPML\ST\TranslationFile\StringsRetrieve;
use WPML_Language_Records;

class Manager extends \WPML\ST\TranslationFile\Manager {

	public function __construct(
		StringsRetrieve $strings,
		Builder $builder,
		\WP_Filesystem_Direct $filesystem,
		WPML_Language_Records $language_records,
		Domains $domains
	) {
		parent::__construct( $strings, $builder, $filesystem, $language_records, $domains );
	}

	/**
	 * @return string
	 */
	protected function getFileExtension() {
		return 'mo';
	}

	/**
	 * @return bool
	 */
	public function isPartialFile() {
		return true;
	}

	/**
	 * @return Collection
	 */
	protected function getDomains() {
		return $this->domains->getMODomains();
	}

	/**
	 * @return bool
	 */
	public static function hasFiles() {
		return (bool) ( new GlobIterator( self::getSubdir() . '/*.mo' ) )->count();
	}
}
