<?php

/**
 * Class WPML_Page_Builders_Defined
 */
class WPML_Page_Builders_Defined {

	private $settings;

	public function __construct() {
		$this->init_settings();
	}

	public function has( $page_builder ) {
		return defined( $this->settings[ $page_builder ]['constant'] );
	}

	/**
	 * @param array $components
	 *
	 * @return array
	 */
	public function add_components( $components ) {
		if ( isset( $components['page-builders'] ) ) {
			$components['page-builders']['beaver-builder'] = array(
				'name'            => 'Beaver Builder',
				'constant'        => $this->settings['beaver-builder']['constant'],
				'notices-display' => array(
					'wpml-translation-editor',
				),
			);
			$components['page-builders']['elementor']      = array(
				'name'            => 'Elementor',
				'constant'        => $this->settings['elementor']['constant'],
				'notices-display' => array(
					'wpml-translation-editor',
				),
			);
		}

		return $components;
	}

	public function init_settings() {
		$this->settings = array(
			'beaver-builder' => array(
				'constant' => 'FL_BUILDER_VERSION',
				'factory' => 'WPML_Beaver_Builder_Integration_Factory',
			),
			'elementor' => array(
				'constant' => 'ELEMENTOR_VERSION',
				'factory' => 'WPML_Elementor_Integration_Factory',
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

}