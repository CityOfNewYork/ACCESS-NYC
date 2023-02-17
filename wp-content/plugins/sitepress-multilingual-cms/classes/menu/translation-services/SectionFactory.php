<?php

namespace WPML\TM\Menu\TranslationServices;

use WPML\LIB\WP\Http;
use WPML\TM\Geolocalization;
use function WPML\Container\make;
use function WPML\FP\partial;
use function WPML\FP\partialRight;

class SectionFactory implements \IWPML_TM_Admin_Section_Factory {
	/**
	 * @return Section
	 */
	public function create() {
		global $sitepress;

		return new Section(
			$sitepress,
			$this->site_key_exists() ?
				$this->createServicesListRenderer() :
				partial( NoSiteKeyTemplate::class . '::render', $this->getTemplateRenderer() )
		);
	}

	/**
	 * @return bool|string
	 */
	private function site_key_exists() {
		$site_key = false;

		if ( class_exists( 'WP_Installer' ) ) {
			$repository_id = 'wpml';
			$site_key      = \WP_Installer()->get_site_key( $repository_id );
		}

		return $site_key;
	}

	/**
	 * @param  \WPML_Twig_Template_Loader $twig_loader
	 * @param  \WPML_TP_Client            $tp_client
	 *
	 * @return callable
	 */
	private function createServicesListRenderer() {
		/**
		 * Section: "Partner services", "Other services" and "Translation Management Services"
		 */
		$getServicesTabs = partial(
			ServicesRetriever::class . '::get',
			$this->getTpApiServices(),
			Geolocalization::getCountryByIp( Http::post() ),
			partialRight(
				[ ServiceMapper::class, 'map' ],
				[ ActiveServiceRepository::class, 'getId' ]
			)
		);

		return partial(
			MainLayoutTemplate::class . '::render',
			$this->getTemplateRenderer(),
			ActiveServiceTemplateFactory::createRenderer(),
			\TranslationProxy::has_preferred_translation_service(),
			$getServicesTabs
		);
	}

	/**
	 * @return callable
	 */
	private function getTemplateRenderer() {
		$template = make(
			\WPML_Twig_Template_Loader::class,
			[
				':paths' => [
					WPML_TM_PATH . '/templates/menus/translation-services/',
					WPML_PLUGIN_PATH . '/templates/pagination/',
				],
			]
		)->get_template();

		return [ $template, 'show' ];
	}

	/**
	 * @return \WPML_TP_API_Services
	 */
	private function getTpApiServices() {
		return make( \WPML_TP_Client_Factory::class )->create()->services();
	}
}
