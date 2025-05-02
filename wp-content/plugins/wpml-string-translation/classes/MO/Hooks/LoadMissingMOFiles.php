<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\Generate\MissingMOFile;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class LoadMissingMOFiles implements \IWPML_Action {

	const MISSING_MO_FILES_DIR                = '/wpml/missing/';
	const OPTION_GROUP                        = 'ST-MO';
	const MISSING_MO_OPTION                   = 'missing-mo';
	const TIMEOUT                             = 10;

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
		if ( defined( 'WPML_CHECK_MISSING_MO_FILES' ) && true === WPML_CHECK_MISSING_MO_FILES ) {
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

		// Check if the file has already been generated.
		$generatedFile = $this->getGeneratedFileName( $mofile, $domain );
		if (
			self::isReadable( $generatedFile )
			&& $this->moFilesDictionary->is_path_handled( $mofile, $domain )
		) {
			// The file exists AND the path is handled by ST.
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
		$missing = $this->optionManager->get( self::OPTION_GROUP, self::MISSING_MO_OPTION, [] );
		return wpml_collect( is_array( $missing ) ? $missing : [] );
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
