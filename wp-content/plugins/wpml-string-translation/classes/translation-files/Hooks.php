<?php

namespace WPML\ST\TranslationFile;

use WPML\ST\MO\Hooks\Factory;
use WPML\ST\MO\Plural;
use WPML_Action_Filter_Loader;
use WPML_Package_Translation_Schema;
use WPML_ST_Upgrade;
use WPML_ST_Upgrade_MO_Scanning;

class Hooks {

	/** @var WPML_Action_Filter_Loader $action_loader */
	private $action_loader;

	/** @var WPML_ST_Upgrade $upgrade */
	private $upgrade;

	public function __construct( WPML_Action_Filter_Loader $action_loader, WPML_ST_Upgrade $upgrade ) {
		$this->action_loader = $action_loader;
		$this->upgrade       = $upgrade;
	}

	public function install() {
		if ( $this->hasPackagesTable() && $this->hasTranslationFilesTables() ) {
			$this->action_loader->load( [ Factory::class, Plural::class ] );
		}
	}

	private function hasPackagesTable() {
		$updates_run = get_option( WPML_Package_Translation_Schema::OPTION_NAME, [] );
		return in_array( WPML_Package_Translation_Schema::REQUIRED_VERSION, $updates_run, true );
	}

	private function hasTranslationFilesTables() {
		return $this->upgrade->has_command_been_executed( WPML_ST_Upgrade_MO_Scanning::class );
	}

	public static function useFileSynchronization() {
		return defined( 'WPML_ST_SYNC_TRANSLATION_FILES' ) && constant( 'WPML_ST_SYNC_TRANSLATION_FILES' );
	}
}
