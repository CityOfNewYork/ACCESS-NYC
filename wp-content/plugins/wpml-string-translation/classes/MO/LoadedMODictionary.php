<?php

namespace WPML\ST\MO;

use MO;
use stdClass;
use WPML\Collect\Support\Collection;

class LoadedMODictionary {

	const PATTERN_SEARCH_LOCALE = '#([-]?)([a-z]+[_A-Z]*)(\.mo)$#i';
	const LOCALE_PLACEHOLDER = '{LOCALE}';

	/** @var array */
	private $domainsCache = [];

	/** @var null|Collection $mo_files */
	private $mo_files;

	public function __construct() {
		$this->mo_files = wpml_collect( [] );
		$this->collectFilesAddedBeforeInstantiation();
	}

	private function collectFilesAddedBeforeInstantiation() {
		if ( isset( $GLOBALS['l10n'] ) && is_array( $GLOBALS['l10n'] ) ) {
			wpml_collect( $GLOBALS['l10n'] )->each(
				function ( $mo, $domain ) {
					if ( $mo instanceof MO ) {
						$this->addFile( $domain, $mo->get_filename() );
					}
				}
			);
		}
	}

	/**
	 * @param string $domain
	 * @param string $mofile
	 */
	public function addFile( $domain, $mofile ) {
		$mofile_pattern = preg_replace(
			self::PATTERN_SEARCH_LOCALE,
			'$1' . self::LOCALE_PLACEHOLDER . '$3',
			$mofile,
			1
		);

		$hash = md5( $domain . $mofile_pattern );

		$entity = (object) [
			'domain'         => $domain,
			'mofile_pattern' => $mofile_pattern,
		];

		$this->mo_files->put( $hash, $entity );
		$this->domainsCache = [];
	}

	/**
	 * @param array $excluded
	 *
	 * @return array
	 */
	public function getDomains( array $excluded ) {
		$key = md5( implode( $excluded ) );
		if ( isset( $this->domainsCache[ $key ] ) ) {
			return $this->domainsCache[ $key ];
		}

		$domains = $this->mo_files
			->reject( $this->excluded( $excluded ) )
			->pluck( 'domain' )
			->unique()->values()->toArray();

		$this->domainsCache[ $key ] = $domains;

		return $domains;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return Collection
	 */
	public function getFiles( $domain, $locale ) {
		return $this->mo_files
			->filter( $this->byDomain( $domain ) )
			->map( $this->getFile( $locale ) )
			->values();
	}

	/**
	 * @param array $excluded
	 *
	 * @return \Closure
	 */
	private function excluded( array $excluded ) {
		return function ( stdClass $entity ) use ( $excluded ) {
			return in_array( $entity->domain, $excluded, true );
		};
	}

	/**
	 * @param string $domain
	 *
	 * @return \Closure
	 */
	private function byDomain( $domain ) {
		return function ( stdClass $entity ) use ( $domain ) {
			return $entity->domain === $domain;
		};
	}

	/**
	 * @param string $locale
	 *
	 * @return \Closure
	 */
	private function getFile( $locale ) {
		return
			function ( stdClass $entity ) use ( $locale ) {
				return str_replace(
					self::LOCALE_PLACEHOLDER,
					$locale,
					$entity->mofile_pattern
				);
			};
	}
}
