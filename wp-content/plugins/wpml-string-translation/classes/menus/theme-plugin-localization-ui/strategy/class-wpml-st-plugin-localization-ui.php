<?php

use WPML\FP\Str;

class WPML_ST_Plugin_Localization_UI implements IWPML_Theme_Plugin_Localization_UI_Strategy {

	/** @var WPML_ST_Plugin_Localization_Utils */
	private $utils;

	/** @var WPML_Localization */
	private $localization;

	/** @var \WPML\ST\TranslationFile\FilesToScanRepository */
	private $filesToScanRepository;

	/**
	 * WPML_ST_Plugin_Localization_UI constructor.
	 *
	 * @param WPML_Localization                              $localization
	 * @param WPML_ST_Plugin_Localization_Utils              $utils
	 * @param \WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository
	 */
	public function __construct(
		WPML_Localization $localization,
		WPML_ST_Plugin_Localization_Utils $utils,
		\WPML\ST\TranslationFile\FilesToScanRepository $filesToScanRepository
	) {
		$this->localization          = $localization;
		$this->utils                 = $utils;
		$this->filesToScanRepository = $filesToScanRepository;
	}

	/**
	 * @return array
	 */
	public function get_model() {

		$model = array(
			'show_active_label'  => false,
			'scan_button_label'  => __( 'Scan selected plugins for strings', 'wpml-string-translation' ),
			'completed_title'    => __( 'Completely translated strings', 'wpml-string-translation' ),
			'needs_update_title' => __( 'Strings in need of translation', 'wpml-string-translation' ),
			'component'          => __( 'Plugins', 'wpml-string-translation' ),
			'domain'             => __( 'Textdomain', 'wpml-string-translation' ),
			'all_text'           => __( 'All', 'wpml-string-translation' ),
			'active_text'        => __( 'Active', 'wpml-string-translation' ),
			'inactive_text'      => __( 'Inactive', 'wpml-string-translation' ),
			'show_textdomains'   => __( 'show textdomains', 'wpml-string-translation' ),
			'hide_textdomains'   => __( 'hide textdomains', 'wpml-string-translation' ),
			'download_po'        => __( 'Download .po file', 'wpml_string_translation' ),
			'type'               => 'plugin',
			'components'         => $this->get_components( $this->utils->get_plugins(), $this->localization->get_localization_stats( 'plugin' ) ),
			'stats_id'           => 'wpml_plugin_scan_stats',
			'scan_button_id'     => 'wpml_plugin_localization_scan',
			'section_class'      => 'wpml_plugin_localization',
			'status_count'       => array(
				'active'   => count( $this->utils->get_plugins_by_status( true ) ),
				'inactive' => count( $this->utils->get_plugins_by_status( false ) ),
			),
			'render_filters'     => true,
			'render_section'     => true,
		);

		return $model;
	}

	/**
	 * @param array $plugins
	 * @param array $plugin_stats
	 *
	 * @return array
	 */
	private function get_components( $plugins, $plugin_stats ) {
		$components         = [];
		$pluginFilesInStats = array_keys( $plugin_stats );
		$filesToScanData    = $this->filesToScanRepository->getFilesToScanData();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$id = plugin_dir_path( WP_PLUGIN_URL . '/' . $plugin_file );
			if ( Str::endsWith( '/plugins/', $id ) ) {
				$id .= $plugin_file;
			}

			$components[ $plugin_file ] = [
				'id'                       => md5( $id ),
				'file'                     => basename( $plugin_file ),
				'component_name'           => $plugin_data['Name'],
				'component_id_for_mo_scan' => $plugin_data['Name'],
				'active'                   => $this->utils->is_plugin_active( $plugin_file ),
				'completed'                => false,
				'needs_rescan'             => false,
				'statusIconTitle'          => __( 'Not scanned yet', 'wpml-string-translation' ),
				'domains'                  => $this->localization->getDomainsFromLocalizationStats( $plugin_stats, $plugin_file, $plugin_data ),
			];

			if ( in_array( $plugin_file, $pluginFilesInStats ) ) {
				$components[ $plugin_file ]['completed']       = true;
				$components[ $plugin_file ]['statusIconTitle'] = __( 'Scanned', 'wpml-string-translation' );
			}

			if ( in_array( $plugin_data["Name"], $filesToScanData['plugins'] ) ) {
				$components[ $plugin_file ]['completed']       = false;
				$components[ $plugin_file ]['needs_rescan']    = true;
				$components[ $plugin_file ]['statusIconTitle'] = __( 'Needs re-scanning', 'wpml-string-translation' );
			}
		}

		return $components;
	}

	/** @return string */
	public function get_template() {
		return 'theme-plugin-localization-ui.twig';
	}

	public function getActivePluginNamesWithoutRegisteredTranslationFiles(): array {
		$pluginIdsWithTranslationFiles = $this->filesToScanRepository->getAllPluginIdsWithBackendTranslationFiles();
		$pluginsData = $this->utils->get_plugins_by_status( true );

		$pluginNamesWithoutTranslationFiles = [];
		foreach ( $pluginsData as $pluginId => $pluginData ) {
			if ( ! in_array( $pluginId, $pluginIdsWithTranslationFiles ) ) {
				$pluginNamesWithoutTranslationFiles[] = $pluginData['Name'];
			}
		}

		return $pluginNamesWithoutTranslationFiles;
	}
}
