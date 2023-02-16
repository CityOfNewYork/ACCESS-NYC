<?php

namespace WPML\TM\Menu\TranslationServices;

class NoSiteKeyTemplate {

	const TEMPLATE = 'no-site-key.twig';

	/**
	 * @param  callable $templateRenderer
	 */
	public static function render( $templateRenderer ) {
		echo $templateRenderer( self::get_no_site_key_model(), self::TEMPLATE );
	}

	/**
	 * @return array
	 */
	private static function get_no_site_key_model() {
		return [
			'registration' => [
				'link' => admin_url( 'plugin-install.php?tab=commercial#repository-wpml' ),
				'text' => __(
					'Please register WPML to enable the professional translation option',
					'wpml-translation-management'
				),
			],
		];
	}
}
