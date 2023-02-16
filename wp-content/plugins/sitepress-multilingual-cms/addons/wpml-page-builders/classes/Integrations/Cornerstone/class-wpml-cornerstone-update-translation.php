<?php

use WPML\PB\Cornerstone\Utils;

class WPML_Cornerstone_Update_Translation extends WPML_Page_Builders_Update_Translation {

	/** @param array $data_array */
	public function update_strings_in_modules( array &$data_array ) {
		foreach ( $data_array as $key => &$data ) {
			if ( isset( $data['_type'] ) && ! Utils::typeIsLayout( $data['_type'] ) ) {
				$data = $this->update_strings_in_node( Utils::getNodeId( $data ), $data );
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
	protected function update_strings_in_node( $node_id, $settings ) {
		$strings = $this->translatable_nodes->get( $node_id, $settings );
		foreach ( $strings as $string ) {
			$translation = $this->get_translation( $string );
			$settings    = $this->translatable_nodes->update( $node_id, $settings, $translation );
		}

		return $settings;
	}

}
