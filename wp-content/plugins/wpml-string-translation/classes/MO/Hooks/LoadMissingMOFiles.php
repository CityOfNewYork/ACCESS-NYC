<?php

namespace WPML\ST\MO\Hooks;

use WPML\Collect\Support\Collection;
use WPML\ST\MO\Generate\MissingMOFile;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class LoadMissingMOFiles implements \IWPML_Action {

	const MISSING_MO_FILES_DIR                = '/wpml/missing/';
	const OPTION_GROUP                        = 'ST-MO';
	const MISSING_MO_OPTION                   = 'missing-mo';
	const TIMEOUT                             = 10;
	const WPML_VERSION_INTRODUCING_ST_MO_FLOW = '4.3.0';

	/**
	 * @var MissingMOFile
	 */
	private $generateMissingMoFile;
	/**
	 * @var OptionManager
	 */
	private $optionManager;

	/** @var \WPML_ST_Translations_File_Dictionary_Storage_Table */
	private $moFilesDictionary;

	public function __construct(
		MissingMOFile $generateMissingMoFile,
		OptionManager $optionManager,
		\WPML_ST_Translations_File_Dictionary_Storage_Table $moFilesDictionary
	) {
		$this->generateMissingMoFile = $generateMissingMoFile;
		$this->optionManager         = $optionManager;
		$this->moFilesDictionary     = $moFilesDictionary;
	}

	public function add_hooks() {
		if ( $this->wasWpmlInstalledPriorToMoFlowChanges() ) {
			add_filter( 'load_textdomain_mofile', [ $this, 'recordMissing' ], 10, 2 );
			add_action( 'shutdown', [ $this, 'generateMissing' ] );
		}
	}

	/**
	 * @param string $mofile
	 * @param string $domain
	 *
	 * @return string
	 */
	public function recordMissing( $mofile, $domain ) {
		if ( strpos( $mofile, WP_LANG_DIR . '/themes/' ) === 0 ) {
			return $mofile;
		}
		if ( strpos( $mofile, WP_LANG_DIR . '/plugins/' ) === 0 ) {
			return $mofile;
		}

		$missing = $this->getMissing();

		if ( self::isReadable( $mofile ) ) {
			if ( $missing->has( $domain ) ) {
				$this->saveMissing( $missing->forget( $domain ) );
			}

			return $mofile;
		}

		if ( ! $this->moFilesDictionary->find( $mofile ) ) {
			return $mofile;
		}

		$generatedFile = $this->getGeneratedFileName( $mofile, $domain );
		if ( self::isReadable( $generatedFile ) ) {
			return $generatedFile;
		}

		if ( $this->generateMissingMoFile->isNotProcessed( $generatedFile ) ) {
			$this->saveMissing( $missing->put( $domain, $mofile ) );
		}

		return $mofile;
	}

	public function generateMissing() {
		$lock = make( 'WPML\Utilities\Lock', [ ':name' => self::class ] );

		$missing = $this->getMissing();
		if ( $missing->count() && $lock->create() ) {

			$generate = function ( $pair ) {
				list( $domain, $mofile ) = $pair;
				$generatedFile = $this->getGeneratedFileName( $mofile, $domain );
				$this->generateMissingMoFile->run( $generatedFile, $domain );
			};

			$unProcessed = $missing->assocToPair()
			                       ->eachWithTimeout( $generate, self::getTimeout() )
			                       ->pairToAssoc();
			$this->saveMissing( $unProcessed );

			$lock->release();
		}
	}

	public static function isReadable( $mofile ) {
		return is_readable( $mofile );
	}

	/**
	 * @return \WPML\Collect\Support\Collection
	 */
	private function getMissing() {
		return wpml_collect( $this->optionManager->get( self::OPTION_GROUP, self::MISSING_MO_OPTION, [] ) );
	}

	/**
	 * @param \WPML\Collect\Support\Collection $missing
	 */
	private function saveMissing( \WPML\Collect\Support\Collection $missing ) {
		$this->optionManager->set( self::OPTION_GROUP, self::MISSING_MO_OPTION, $missing->toArray() );
	}

	public static function getTimeout() {
		return self::TIMEOUT;
	}

	/**
	 * @return bool
	 */
	private function wasWpmlInstalledPriorToMoFlowChanges() {
		$wpml_start_version = \get_option( \WPML_Installation::WPML_START_VERSION_KEY, '0.0.0' );

		return version_compare( $wpml_start_version, self::WPML_VERSION_INTRODUCING_ST_MO_FLOW, '<' );
	}

	/**
	 * @param string $mofile
	 * @param string $domain
	 *
	 * @return string
	 */
	private function getGeneratedFileName( $mofile, $domain ) {
		$fileName = basename( $mofile );

		if ( $this->isNonDefaultWithMissingDomain( $fileName, $domain ) ) {
			$fileName = $domain . '-' . $fileName;
		}

		return WP_LANG_DIR . self::MISSING_MO_FILES_DIR . $fileName;
	}

	/**
	 * There's a fallback for theme that is looking for
	 * this kind of file `wp-content/themes/hybrid/ru_RU.mo`.
	 * We need to add the domain otherwise it collides with
	 * the MO file for the default domain.
	 *
	 * @param string $fileName
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isNonDefaultWithMissingDomain( $fileName, $domain ) {
		return 'default' !== $domain
		       && preg_match( '/^[a-z]+_?[A-Z]*\.mo$/', $fileName );
	}
}
