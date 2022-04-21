<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\TranslationFile\Sync\FileSync;

class Sync implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var FileSync */
	private $fileSync;

	/** @var callable */
	private $useFileSynchronization;

	public function __construct( FileSync $fileSync, callable $useFileSynchronization ) {
		$this->fileSync               = $fileSync;
		$this->useFileSynchronization = $useFileSynchronization;
	}

	public function add_hooks() {
		if ( call_user_func( $this->useFileSynchronization ) ) {
			add_filter(
				'override_load_textdomain',
				[ $this, 'syncCustomMoFileOnLoadTextDomain' ],
				LoadTextDomain::PRIORITY_OVERRIDE - 1,
				3
			);
		}
	}

	public function syncFile( $domain, $moFile ) {
		if ( call_user_func( $this->useFileSynchronization ) ) {
			$this->fileSync->sync( $moFile, $domain );
		}
	}

	/**
	 * @param  bool  $override
	 * @param  string  $domain
	 * @param  string  $moFile
	 *
	 * @return bool
	 */
	public function syncCustomMoFileOnLoadTextDomain( $override, $domain, $moFile ) {
		$this->fileSync->sync( $moFile, $domain );

		return $override;
	}
}
