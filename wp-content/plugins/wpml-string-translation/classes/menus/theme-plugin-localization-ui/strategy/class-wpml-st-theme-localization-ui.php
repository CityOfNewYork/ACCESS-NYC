<?php

class WPML_ST_Theme_Localization_UI implements IWPML_Theme_Plugin_Localization_UI_Strategy {

	private $utils;
	private $template_path;
	private $localization;

	/** @var \WPML\ST\TranslationFile\FilesToScanRepository */
	private $filesToScanRepository;

	/**
	 * WPML_ST_Theme_Localization_UI constructor.
	 *
	 * @param \WPML_Localization                             $localization
	 * @param \WPML_ST_Theme_Localization_Utils              $utils
	 * @param \WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository
	 * @param string                                         $template_path
	 */
	public function __construct(
		WPML_Localization $localization,
		WPML_ST_Theme_Localization_Utils $utils,
		\WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository,
		$template_path
	) {
		$this->localization          = $localization;
		$this->utils                 = $utils;
		$this->filesToScanRepository = $filesToScanRepository;
		$this->template_path         = $template_path;
	}

	/** @return array */
	public function get_model() {

		$model = array(
			'show_active_label'  => true,
			'scan_button_label'  => __( 'Scan selected themes for strings', 'wpml-string-translation' ),
			'completed_title'    => __( 'Completely translated strings', 'wpml-string-translation' ),
			'needs_update_title' => __( 'Strings in need of translation', 'wpml-string-translation' ),
			'component'          => __( 'Themes', 'wpml-string-translation' ),
			'domain'             => __( 'Textdomain', 'wpml-string-translation' ),
			'all_text'           => __( 'All', 'wpml-string-translation' ),
			'active_text'        => __( 'Active', 'wpml-string-translation' ),
			'inactive_text'      => __( 'Inactive', 'wpml-string-translation' ),
			'show_textdomains'   => __( 'show textdomains', 'wpml-string-translation' ),
			'hide_textdomains'   => __( 'hide textdomains', 'wpml-string-translation' ),
			'type'               => 'theme',
			'components'         => $this->get_components(),
			'stats_id'           => 'wpml_theme_scan_stats',
			'scan_button_id'     => 'wpml_theme_localization_scan',
			'section_class'      => 'wpml_theme_localization',
			'download_po'        => __( 'Download .po file', 'wpml_string_translation' ),
			'status_count'       => array(
				'active'   => 1,
				'inactive' => count( $this->utils->get_theme_data() ) - 1,
			),
			'render_filters'     => true,
			'render_section'     => true,
		);

		return $model;
	}

	/** @return array */
	private function get_components() {
		$components                = [];
		$theme_localization_status = $this->localization->get_localization_stats( 'theme' );
		$themeFolderNamesInStats   = array_keys( $theme_localization_status );
		$filesToScanData           = $this->filesToScanRepository->getFilesToScanData();

		foreach ( $this->utils->get_theme_data() as $theme_folder => $theme_data ) {
			$components[ $theme_folder ] = [
				'id'                       => md5( $theme_data['path'] ),
				'component_name'           => $theme_data['name'],
				'component_id_for_mo_scan' => $theme_folder, 
				'active'                   => wp_get_theme()->get( 'Name' ) === $theme_data['name'],
				'completed'                => false,
				'needs_rescan'             => false,
				'statusIconTitle'          => __( 'Not scanned yet', 'wpml-string-translation' ),
				'domains'                  => $this->localization->getDomainsFromLocalizationStats( $theme_localization_status, $theme_folder, $theme_data ),
			];

			if ( in_array( $theme_folder, $themeFolderNamesInStats ) ) {
				$components[ $theme_folder ]['completed']       = true;
				$components[ $theme_folder ]['statusIconTitle'] = __( 'Scanned', 'wpml-string-translation' );
			}

			if ( in_array( $theme_folder, $filesToScanData['themes'] ) ) {
				$components[ $theme_folder ]['completed']       = false;
				$components[ $theme_folder ]['needs_rescan']    = true;
				$components[ $theme_folder ]['statusIconTitle'] = __( 'Needs re-scanning', 'wpml-string-translation' );
			}
		}

		return $components;
	}

	/** @return string */
	public function get_template() {
		return 'theme-plugin-localization-ui.twig';
	}
}
