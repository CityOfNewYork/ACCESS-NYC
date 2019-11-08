<?php

class WPML_Cornerstone_Update_Translation extends WPML_Page_Builders_Update_Translation {

	/** @param array $data_array */
	public function update_strings_in_modules( array &$data_array ) {
		foreach ( $data_array as $key => &$data ) {
			if ( isset( $data['_type'] ) && ! in_array( $data['_type'], array( 'section', 'column', 'row' ) ) ) {
				$data = $this->update_strings_in_node( $this->get_node_id( $data ), $data );
			} elseif ( is_array( $data ) ) {
				$this->update_strings_in_modules( $data );
			}
		}
	}

	/**
	 * @param string $node_id
	 * @param $settings
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

	private function get_node_id( $data ) {
		return md5( serialize( $data ) );
	}
}
