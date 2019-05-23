<?php

class WPML_TM_Wizard_Translation_Editor_Step extends WPML_Twig_Template_Loader {

	private $model = array(
		'editor_types' => array(
			'ate'     => ICL_TM_TMETHOD_ATE,
			'classic' => ICL_TM_TMETHOD_EDITOR,
			'manual'  => ICL_TM_TMETHOD_MANUAL,
		)
	);
	/**
	 * @var WPML_TM_MCS_ATE
	 */
	private $mscs_ate;

	public function __construct( WPML_TM_MCS_ATE $mcs_ate, $current_mode ) {
		$this->mscs_ate = $mcs_ate;
		$this->model['current_mode'] = $current_mode;

		parent::__construct( array(
				WPML_TM_PATH . '/templates/wizard',
				$mcs_ate->get_template_path(),
			)
		);
	}

	public function render() {
		$this->add_strings();

		return $this->get_template()->show( $this->model, 'translation-editor-step.twig' );
	}

	public function add_strings() {

		$this->model['strings'] = array(
			'title'          => __( 'What translation tool do you want to use?', 'wpml-translation-management' ),
			'options'        => array(
				'classic' => array(
					'heading'    => __( "WPML's Classic Translation Editor", 'wpml-translation-management' ),

				),
				'ate'     => array(
					'heading'        => __( "WPML's Advanced Translation Editor", 'wpml-translation-management' ),
					'extra_template' => array(
						'template' => 'mcs-ate-controls.twig',
						'model'    => $this->mscs_ate->get_model(),
					)
				),
			),

			'features' => array(
				array(
					'label'   => __( 'Support for all content types', 'wpml-translation-management' ),
					'classic' => true,
					'ate'     => true,
				),
				array(
					'label'   => __( 'Spell checker', 'wpml-translation-management' ),
					'classic' => false,
					'ate'     => true,
				),
				array(
					'label'   => __( 'Translation Memory', 'wpml-translation-management' ),
					'classic' => false,
					'ate'     => true,
				),
				array(
					'label'   => __( 'Machine Translation', 'wpml-translation-management' ),
					'classic' => false,
					'ate'     => true,
				),
				array(
					'label'   => __( 'Translator preview', 'wpml-translation-management' ),
					'classic' => false,
					'ate'     => true,
				)
			),

			'ate' => $this->mscs_ate->get_model( array( 'wizard' => true ) ),

			'select'   => __( 'Select', 'wpml-translation-management' ),
			'continue' => __( 'Continue', 'wpml-translation-management' ),
			'go_back'  => __( 'Go back', 'wpml-translation-management' ),


		);
	}

}
