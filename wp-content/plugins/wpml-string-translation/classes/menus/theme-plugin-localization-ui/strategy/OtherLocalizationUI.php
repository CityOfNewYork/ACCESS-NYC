<?php

namespace WPML\ST\ThemePluginLocalization;

class OtherLocalizationUI implements \IWPML_Theme_Plugin_Localization_UI_Strategy {

	/** @var \WPML_Localization */
	private $localization;

	/** @var \WPML\ST\TranslationFile\FilesToScanRepository */
	private $filesToScanRepository;

	/** @var string */
	private $base_st_url;

	/**
	 * WPML_ST_Other_Localization_UI constructor.
	 *
	 * @param \WPML_Localization                             $localization
	 * @param \WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository
	 */
	public function __construct(
		\WPML_Localization $localization,
		\WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository
	) {
		$this->localization          = $localization;
		$this->filesToScanRepository = $filesToScanRepository;
		$this->base_st_url           = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php' );
	}

	/**
	 * @return array
	 */
	public function get_model() {
		$filesToScanData = $this->filesToScanRepository->getFilesToScanData();
		$renderSection   = in_array( 'WordPress', $filesToScanData['other'] );

		$model = [
			'show_active_label'  => false,
			'scan_button_label'  => __( 'Scan selected plugins for strings', 'wpml-string-translation' ),
			'completed_title'    => __( 'Completely translated strings', 'wpml-string-translation' ),
			'needs_update_title' => __( 'Strings in need of translation', 'wpml-string-translation' ),
			'component'          => __( 'Core', 'wpml-string-translation' ),
			'domain'             => __( 'Textdomain', 'wpml-string-translation' ),
			'show_textdomains'   => __( 'show textdomains', 'wpml-string-translation' ),
			'hide_textdomains'   => __( 'hide textdomains', 'wpml-string-translation' ),
			'download_po'        => __( 'Download .po file', 'wpml_string_translation' ),
			'type'               => 'other',
			'components'         => $this->get_components(),
			'stats_id'           => 'wpml_plugin_scan_stats',
			'scan_button_id'     => 'wpml_plugin_localization_scan',
			'section_class'      => 'wpml_plugin_localization',
			'status_count'       => [
				'active'   => 0,
				'inactive' => 0,
			],
			'render_filters'     => false,
			'render_section'     => $renderSection,
		];

		return $model;
	}

	/**
	 * @return array
	 */
	private function get_components() {
		$components = [];

		$components[ 'core' ] = [
			'id'                       => md5( 'other' ),
			'file'                     => 'other',
			'component_name'           => 'WordPress',
			'component_id_for_mo_scan' => 'WordPress',
			'active'                   => true,
			'completed'                => false,
			'needs_rescan'             => true,
			'statusIconTitle'          => __( 'Needs re-scanning', 'wpml-string-translation' ),
			'domains'                  => [
				'WordPress' => [
					'domain_link' => add_query_arg( [ 'context' => 'WordPress' ], $this->base_st_url ),
				],
			],
		];

		return $components;
	}

	/** @return string */
	public function get_template() {
		return 'theme-plugin-localization-ui.twig';
	}
}
