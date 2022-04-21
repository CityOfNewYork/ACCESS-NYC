<?php

namespace WPML\PB\SiteOrigin;

class UpdateTranslation extends \WPML_Page_Builders_Update_Translation {

	/** @param array $data_array */
	public function update_strings_in_modules( array &$data_array ) {
		foreach ( $data_array as &$data ) {
			if ( isset( $data[ TranslatableNodes::SETTINGS_FIELD ] ) ) {
				$data = $this->update_strings_in_node( $data[ TranslatableNodes::SETTINGS_FIELD ]['class'], $data );
			} elseif ( is_array( $data ) ) {
				$this->update_strings_in_modules( $data );
			}
		}
	}

	/**
	 * @param string $node_id
	 * @param array  $settings
	 *
	 * @return mixed
	 */
	public function update_strings_in_node( $node_id, $settings ) {
		$strings = $this->translatable_nodes->get( $node_id, $settings );
		foreach ( $strings as $string ) {
			$translation = $this->get_translation( $string );
			$settings    = $this->translatable_nodes->update( $node_id, $settings, $translation );
		}
		return $settings;
	}

}
