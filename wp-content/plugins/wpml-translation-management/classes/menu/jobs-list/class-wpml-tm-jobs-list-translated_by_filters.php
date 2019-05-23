<?php

class WPML_TM_Jobs_List_Translated_By_Filters {
	/** @var WPML_TM_Jobs_List_Services */
	private $services;

	/** @var WPML_TM_Jobs_List_Translators */
	private $translators;

	/**
	 * @param WPML_TM_Jobs_List_Services    $services
	 * @param WPML_TM_Jobs_List_Translators $translators
	 */
	public function __construct( WPML_TM_Jobs_List_Services $services, WPML_TM_Jobs_List_Translators $translators ) {
		$this->services    = $services;
		$this->translators = $translators;
	}

	/**
	 * @return array
	 */
	public function get() {
		$options = array(
			array(
				'value' => 'any',
				'label' => __( 'Anyone', 'wpml-translation-management' ),
			)
		);

		$services = $this->services->get();
		if ( $services ) {
			$options[] = array(
				'value' => 'any-service',
				'label' => __( 'Any Translation Service', 'wpml-translation-management' ),
			);
		}

		$translators = $this->translators->get();
		if ( $translators ) {
			$options[] = array(
				'value' => 'any-local-translator',
				'label' => __( 'Any WordPress Translator', 'wpml-translation-management' ),
			);
		}

		return array(
			'options'     => $options,
			'services'    => $services,
			'translators' => $translators,
		);
	}
}