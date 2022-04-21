<?php

namespace WPML\TM\Menu\TranslationServices;

class ActiveServiceTemplate {

	const ACTIVE_SERVICE_TEMPLATE = 'active-service.twig';
	const HOURS_BEFORE_TS_REFRESH = 24;

	/**
	 * @param  callable         $templateRenderer
	 * @param  \WPML_TP_Service $active_service
	 *
	 * @return string
	 */
	public static function render( $templateRenderer, \WPML_TP_Service $active_service ) {
		return $templateRenderer( self::getModel( $active_service ), self::ACTIVE_SERVICE_TEMPLATE );
	}

	/**
	 * @return array
	 */
	private static function getModel( \WPML_TP_Service $active_service ) {
		$model = [
			'strings'            => [
				'title'                  => __( 'Active service:', 'wpml-translation-management' ),
				'deactivate'             => __( 'Deactivate', 'wpml-translation-management' ),
				'modal_header'           => sprintf(
					__(
						'Enter here your %s authentication details',
						'wpml-translation-management'
					),
					$active_service->get_name()
				),
				'modal_tip'              => $active_service->get_popup_message() ?
					$active_service->get_popup_message() :
					__( 'You can find API token at %s site', 'wpml-translation-management' ),
				'modal_title'            => sprintf(
					__( '%s authentication', 'wpml-translation-management' ),
					$active_service->get_name()
				),
				'refresh_language_pairs' => __( 'Refresh language pairs', 'wpml-translation-management' ),
				'refresh_ts_info'        => __( 'Refresh information', 'wpml-translation-management' ),
				'documentation_lower'    => __( 'documentation', 'wpml-translation-management' ),
				'refreshing_ts_message'  => __(
					'Refreshing translation service information...',
					'wpml-translation-management'
				),
			],
			'active_service'     => $active_service,
			'nonces'             => [
				\WPML_TP_Refresh_Language_Pairs::AJAX_ACTION => wp_create_nonce( \WPML_TP_Refresh_Language_Pairs::AJAX_ACTION ),
				ActivationAjax::REFRESH_TS_INFO_ACTION => wp_create_nonce( ActivationAjax::REFRESH_TS_INFO_ACTION ),
			],
			'needs_info_refresh' => self::shouldRefreshData( $active_service ),
		];

		$authentication_message = [];
		/* translators: sentence 1/3: create account with the translation service ("%1$s" is the service name) */
		$authentication_message[] = __(
			'To send content for translation to %1$s, you need to have an %1$s account.',
			'wpml-translation-management'
		);
		/* translators: sentence 2/3: create account with the translation service ("one" is "one account) */
		$authentication_message[] = __(
			"If you don't have one, you can create it after clicking the authenticate button.",
			'wpml-translation-management'
		);
		/* translators: sentence 3/3: create account with the translation service ("%2$s" is "documentation") */
		$authentication_message[] = __(
			'Please, check the %2$s page for more details.',
			'wpml-translation-management'
		);

		$model['strings']['authentication'] = [
			'description'               => implode( ' ', $authentication_message ),
			'authenticate_button'       => __( 'Authenticate', 'wpml-translation-management' ),
			'de_authorize_button'       => __( 'De-authorize', 'wpml-translation-management' ),
			'update_credentials_button' => __( 'Update credentials', 'wpml-translation-management' ),
			'is_authorized'             => self::isAuthorizedText( $active_service->get_name() ),
		];

		return $model;
	}

	private static function isAuthorizedText( $serviceName ) {
		$query_args = [
			'page' => WPML_TM_FOLDER . \WPML_Translation_Management::PAGE_SLUG_MANAGEMENT,
			'sm'   => 'dashboard',
		];

		$href = add_query_arg( $query_args, admin_url( 'admin.php' ) );

		$dashboard = '<a href="' . $href . '">' .
					 __( 'Translation Dashboard', 'wpml-translation-management' ) .
					 '</a>';

		$isAuthorized  = sprintf(
			__( 'Success! You can now send content to %s.', 'wpml-translation-management' ),
			$serviceName
		);
		$isAuthorized .= '<br/>';
		// translators: "%s" is replaced with the link to the "Translation Dashboard"
		$isAuthorized .= sprintf(
			__( 'Go to the %s to choose the content and send it to translation.', 'wpml-translation-management' ),
			$dashboard
		);

		return $isAuthorized;
	}

	private static function shouldRefreshData( \WPML_TP_Service $active_service ) {
		$refresh_time = time() - ( self::HOURS_BEFORE_TS_REFRESH * HOUR_IN_SECONDS );

		return ! $active_service->get_last_refresh() || $active_service->get_last_refresh() < $refresh_time;
	}
}
