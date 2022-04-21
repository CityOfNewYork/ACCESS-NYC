<?php

namespace WPML\TM\Menu\TranslationServices;

use function WPML\Container\make;
use function WPML\FP\partial;

class ActiveServiceTemplateFactory {
	/**
	 * @return \Closure
	 */
	public static function createRenderer() {
		$activeService = ActiveServiceRepository::get();
		if ( $activeService ) {
			$templateRenderer = self::getTemplateRenderer();

			return partial( ActiveServiceTemplate::class . '::render', [ $templateRenderer, 'show' ], $activeService );
		}

		return function () {
			return null;
		};
	}

	/**
	 * @return \WPML_Twig_Template
	 */
	private static function getTemplateRenderer() {
		$paths      = [ WPML_TM_PATH . '/templates/menus/translation-services/' ];
		$twigLoader = make( \WPML_Twig_Template_Loader::class, [ ':paths' => $paths ] );

		return $twigLoader->get_template();
	}
}
