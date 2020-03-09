<?php

use function WPML\Container\make;

/**
 * Class WPML_String_Registration_Factory
 */
class WPML_String_Registration_Factory {

	private $pb_plugin_name;

	public function __construct( $pb_plugin_name ) {
		$this->pb_plugin_name = $pb_plugin_name;
	}

	/**
	 * @return WPML_PB_String_Registration
	 */
	public function create() {
		global $sitepress;

		$string_factory = make( 'WPML_ST_String_Factory' );

		return new WPML_PB_String_Registration(
			new WPML_PB_API_Hooks_Strategy( $this->pb_plugin_name ),
			$string_factory,
			new WPML_ST_Package_Factory(),
			make( 'WPML_Translate_Link_Targets' ),
			WPML\PB\TranslateLinks::getTranslatorForString( $string_factory, $sitepress->get_active_languages() )
		);
	}
}
