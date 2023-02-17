<?php

namespace WPML\TM\Menu\TranslationServices;

use WPML\DocPage;
use WPML\LIB\WP\Nonce;
use WPML\Setup\Option;
use WPML\TM\Menu\TranslationServices\Endpoints\Activate;
use WPML\TM\Menu\TranslationServices\Endpoints\Deactivate;
use WPML\TM\Menu\TranslationServices\Endpoints\Select;
use WPML\UIPage;

class MainLayoutTemplate {

	const SERVICES_LIST_TEMPLATE = 'services-layout.twig';

	/**
	 * @param  callable $templateRenderer
	 * @param  callable $activeServiceRenderer
	 * @param  bool     $hasPreferredService
	 * @param  callable $retrieveServiceTabsData
	 */
	public static function render(
		$templateRenderer,
		$activeServiceRenderer,
		$hasPreferredService,
		$retrieveServiceTabsData
	) {
		echo $templateRenderer(
			self::getModel( $activeServiceRenderer, $hasPreferredService, $retrieveServiceTabsData ),
			self::SERVICES_LIST_TEMPLATE
		);
	}

	/**
	 * @param  callable $activeServiceRenderer
	 * @param  bool     $hasPreferredService
	 * @param  callable $retrieveServiceTabsData
	 *
	 * @return array
	 */
	private static function getModel( $activeServiceRenderer, $hasPreferredService, $retrieveServiceTabsData ) {
		$services = $retrieveServiceTabsData();

		$translationServicesUrl = 'https://wpml.org/documentation/translating-your-contents/professional-translation-via-wpml/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm';

		/* Translators: %s is documentation link for Translation Services */
		$sectionDescription = sprintf(
			'WPML integrates with dozens of professional <a target="_blank" href="%s">translation services</a>. Connect to your preferred service to send and receive translation jobs from directly within WPML.',
			$translationServicesUrl
		);

		return [
			'active_service'        => $activeServiceRenderer(),
			'services'              => $services,
			'has_preferred_service' => $hasPreferredService,
			'has_services'          => ! empty( $services ),
			'translate_everything'  => Option::shouldTranslateEverything(),
			'nonces'                => [
				ActivationAjax::NONCE_ACTION    => wp_create_nonce( ActivationAjax::NONCE_ACTION ),
				AuthenticationAjax::AJAX_ACTION => wp_create_nonce( AuthenticationAjax::AJAX_ACTION ),
			],
			'settings_url'         => UIPage::getSettings(),
			'lsp_logo_placeholder' => WPML_TM_URL . '/res/img/lsp-logo-placeholder.png',
			'strings'                => [
				'translation_services'             => __( 'Translation Services', 'wpml-translation-management' ),
				'translation_services_description' => __( $sectionDescription, 'wpml-translation-management' ),
				'ts'                   => [
					'different'   => __( 'Looking for a different translation service?', 'wpml-translation-management' ),
					'tell_us_url' => DocPage::addTranslationServiceForm(),
					'tell_us'     => __( 'Tell us which one', 'wpml-translation-management' ),
				],
			],
			'endpoints' => [
				'selectService' => [
					'endpoint' => Select::class,
					'nonce'    => Nonce::create( Select::class )
				],
				'deactivateService' => [
					'nonce'    => Nonce::create( Deactivate::class ),
					'endpoint' => Deactivate::class
				],
				'activateService' => [
					'nonce'    => Nonce::create( Activate::class ),
					'endpoint' => Activate::class
				],
			],
		];
	}
}
