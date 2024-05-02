<?php

namespace ACFML\Tools;

class Import extends Transfer implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	public function add_hooks() {
		add_action( 'acf/import_field_group', [ $this, 'setLanguage' ] );
	}
	
	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function setLanguage( $fieldGroup ) {
		if ( $this->isGroupTranslatable() && isset( $fieldGroup[ self::LANGUAGE_PROPERTY ], $fieldGroup[ 'ID' ] ) ) {
			$type = 'post_' . self::FIELD_GROUP_POST_TYPE;
			
			$details = apply_filters( 'wpml_element_language_details', null, [
				'element_id'   => $fieldGroup['ID'],
				'element_type' => self::FIELD_GROUP_POST_TYPE
			] );
			do_action( 'wpml_set_element_language_details', [
				'element_id'           => $fieldGroup['ID'],
				'element_type'         => $type,
				'trid'                 => $details->trid,
				'language_code'        => $fieldGroup[ self::LANGUAGE_PROPERTY ],
				'source_language_code' => $details->source_language_code,
				'check_duplicates'     => false
			] );
		}
	}
}