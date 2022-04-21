<?php

class WPML_TM_Upgrade_Default_Editor_For_Old_Jobs implements IWPML_Upgrade_Command {

	/** @var SitePress */
	private $sitepress;

	public function __construct( $args ) {
		$this->sitepress = $args[0];
	}

	/**
	 * @return bool
	 */
	private function run() {
		$default = get_option( WPML_TM_Old_Jobs_Editor::OPTION_NAME );
		if ( ! $default ) {
			$method  = $this->sitepress->get_setting( 'doc_translation_method' );
			$default = WPML_TM_Editors::ATE === strtolower( $method ) ? WPML_TM_Editors::ATE : WPML_TM_Editors::WPML;
			update_option( WPML_TM_Old_Jobs_Editor::OPTION_NAME, $default );
		}

		return true;
	}


	public function run_admin() {
		return $this->run();
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
		return $this->run();
	}

	/** @return bool */
	public function get_results() {
		return true;
	}
}
