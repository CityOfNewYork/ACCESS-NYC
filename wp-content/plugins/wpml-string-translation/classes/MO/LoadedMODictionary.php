<?php

namespace WPML\ST\MO;

use MO;
use stdClass;
use WPML\Collect\Support\Collection;
use WPML\LIB\WP\WordPress;

class LoadedMODictionary {

	const PATTERN_SEARCH_LOCALE = '#([-]?)([a-z]+[_A-Z]*)(\.mo)$#i';
	const LOCALE_PLACEHOLDER = '{LOCALE}';

	/** @var array */
	private $domainsCache = [];

	/** @var Collection $mo_files */
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
		// WP 6.5
		// We need to collect all files loaded in WP_Translation_Controller
		// at the moment of hooks registration.
		if ( class_exists('\WP_Translation_Controller') ) {
			$translationsController = \WP_Translation_Controller::get_instance();
			$reflection = new \ReflectionClass( $translationsController );
			$property = $reflection->getProperty( 'loaded_files' );
			$property->setAccessible( true );
			$loaded_files = $property->getValue( $translationsController );

			foreach ( $loaded_files as $loaded_file => $loaded_file_data ) {
				$moFileName = str_replace('.l10n.php', '.mo', $loaded_file);
				$locale = array_keys($loaded_file_data)[0];
				$locale_data = $loaded_file_data[$locale];
				foreach ( $locale_data as $domain => $translationFile ) {
					$this->addFile( $domain, $moFileName );
				}
			}
		}
	}

	/**
	 * @param string $domain
	 * @param string $mofile
	 */
	public function addFile( $domain, $mofile ) {
		// The WP RC version is `6.7-RC1` which is an improper format for comparison (we'd need `6.7.0-RC1`).
		if ( WordPress::versionCompare( '>', '6.6.999' ) ) {
			$mofile = $this->maybeAdjustPathToStandardLanguageFolders( $mofile, $domain );
		}

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
			'mofile'         => $mofile,
		];

		$this->mo_files->put( $hash, $entity );
		$this->domainsCache = [];
	}

	/**
	 * @param string $mofile
	 * @param string $domain
	 *
	 * @return string
	 */
	private function maybeAdjustPathToStandardLanguageFolders( $mofile, $domain ) {
		static $pluginsFolder = WP_LANG_DIR . '/plugins/';
		static $themesFolder  = WP_LANG_DIR . '/themes/';

		if (
			0 === strpos( $mofile, $pluginsFolder )
			|| 0 === strpos( $mofile, $themesFolder )
		) {
			return $mofile; // The file is already in the standard language folders.
		}

		if ( is_dir( dirname( $mofile ) ) ) {
			return $mofile; // The custom language folder exists.
		}

		preg_match( self::PATTERN_SEARCH_LOCALE, $mofile, $matches );

		if ( ! isset( $matches[2] ) ) {
			return $mofile;
		}

		$locale = $matches[2];
		$moName = $domain . '-' . $locale . '.mo';

		if ( is_file( $pluginsFolder . $moName ) ) {
			return $pluginsFolder . $moName;
		} elseif ( is_file( $themesFolder . $moName ) ) {
			return $themesFolder . $moName;
		}

		// Fallback, let's try find it in the standard plugin folder (it may work with a different locale).
		return $pluginsFolder . $moName;
	}

	/**
	 * @param array $excluded
	 *
	 * @return array
	 */
	public function getDomains( array $excluded = [] ) {
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
	 * @return Collection
	 */
	public function getEntities() {
		return $this->mo_files;
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
