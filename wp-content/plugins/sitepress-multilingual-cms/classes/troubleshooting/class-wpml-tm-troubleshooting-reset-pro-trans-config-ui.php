<?php

class WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI {

	const TROUBLESHOOTING_RESET_PRO_TRANS_TEMPLATE = 'reset-pro-trans-config.twig';

	/**
	 * Template service.
	 *
	 * @var IWPML_Template_Service
	 */
	private $template_service;

	/**
	 * WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI constructor.
	 *
	 * @param IWPML_Template_Service $template_service WPML_Twig_Template twig service.
	 */
	public function __construct( IWPML_Template_Service $template_service ) {
		$this->template_service = $template_service;
	}

	/**
	 * Returns of template service render result.
	 *
	 * @return string
	 */
	public function show() {
		return $this->template_service->show( $this->get_model(), self::TROUBLESHOOTING_RESET_PRO_TRANS_TEMPLATE );
	}

	/**
	 * Returns model array for Troubleshooting Reset Pro Trans.
	 *
	 * @return array
	 */
	private function get_model() {
		$translation_service_name = TranslationProxy::get_current_service_name();

		if ( ! $translation_service_name ) {
			$translation_service_name = 'PRO';
			$alert_2                  = __( 'Only select this option if you have no pending jobs or you are sure of what you are doing.', 'wpml-translation-management' );
		} else {
			if ( ! TranslationProxy::has_preferred_translation_service() ) {
				/* translators: Reset professional translation state ("%1$s" is the service name) */
				$alert_2 = sprintf( __( 'If you have sent content to %1$s, you should cancel the projects in %1$s system. Any work that completes after you do this reset cannot be received by your site.', 'wpml-translation-management' ), $translation_service_name );
			} else {
				$alert_2 = __( 'Any work that completes after you do this reset cannot be received by your site.', 'wpml-translation-management' );
			}
		}

		$model = array(
			'strings'     => array(
				'title'         => __( 'Reset professional translation state', 'wpml-translation-management' ),
				'alert1'        => __( 'Use this feature when you want to reset your translation process. All your existing translations will remain unchanged. Any translation work that is currently in progress will be stopped.', 'wpml-translation-management' ),
				'alert2'        => $alert_2,
				/* translators: Reset professional translation state ("%1$s" is the service name) */
				'checkBoxLabel' => sprintf( __( 'I am about to stop any ongoing work done by %1$s.', 'wpml-translation-management' ), $translation_service_name ),
				'button'        => __( 'Reset professional translation state', 'wpml-translation-management' ),
			),
			'placeHolder' => 'icl_reset_pro',
		);

		return $model;
	}
}
