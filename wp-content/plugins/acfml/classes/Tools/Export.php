<?php

namespace ACFML\Tools;

use WPML\FP\Obj;

class Export extends Transfer implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	
	public function add_hooks() {
		add_filter( 'acf/prepare_field_group_for_export', [ $this, 'addLanguageInformation' ] );
	}
	
	/**
	 * @param string $fieldKey
	 *
	 * @return null|string
	 */
	private function getLanguageCode( $fieldKey ) {
		return apply_filters( 'wpml_element_language_code', null, [
			'element_id'   => Obj::prop( 'ID', acf_get_field_group( $fieldKey ) ),
			'element_type' => self::FIELD_GROUP_POST_TYPE
		] );
	}
	
	/**
	 * @param array $fieldGroup
	 *
	 * @return array
	 */
	public function addLanguageInformation( $fieldGroup ) {
		if ( $this->isGroupTranslatable() ) {
			$language = $this->getLanguageCode( Obj::prop( 'key', $fieldGroup ) );
			if ( $language ) {
				$fieldGroup[ self::LANGUAGE_PROPERTY ] = $language;
			}
		}
		
		return $fieldGroup;
	}
}
