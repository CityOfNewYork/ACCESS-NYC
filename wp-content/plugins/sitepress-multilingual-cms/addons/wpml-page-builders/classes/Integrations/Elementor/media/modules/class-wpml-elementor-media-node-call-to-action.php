<?php

class WPML_Elementor_Media_Node_Call_To_Action extends WPML_Elementor_Media_Node {

	public function translate( $settings, $target_lang, $source_lang ) {
		foreach ( [ 'bg_image', 'graphic_image' ] as $property ) {
			$settings = $this->translate_image_property( $settings, $property, $target_lang, $source_lang );
		}

		return $settings;
	}

}
