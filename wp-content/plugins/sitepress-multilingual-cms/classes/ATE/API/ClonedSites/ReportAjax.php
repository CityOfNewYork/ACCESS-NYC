<?php

namespace WPML\TM\ATE\ClonedSites;

class ReportAjax implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/**
	 * @var Report
	 */
	private $reportHandler;

	/**
	 * @param Report $reportHandler
	 */
	public function __construct( Report $reportHandler ) {
		$this->reportHandler = $reportHandler;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_wpml_save_cloned_sites_report_type', [ $this, 'reportSiteCloned' ] );
	}

	/**
	 * @param string $reportType
	 */
	public function handleInstallerSiteUrlDetection($reportType) {
		$this->reportHandler->report( $reportType );
	}

	public function reportSiteCloned() {
		if ( $this->isValidRequest() && $this->reportHandler->report( $_POST['reportType'] ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	private function isValidRequest() {
		return array_key_exists( 'nonce', $_POST )
		       && array_key_exists( 'reportType', $_POST )
		       && wp_verify_nonce( $_POST['nonce'], 'icl_doc_translation_method_cloned_nonce' );
	}
}
