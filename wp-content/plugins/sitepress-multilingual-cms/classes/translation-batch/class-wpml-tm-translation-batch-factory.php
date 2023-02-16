<?php

class WPML_TM_Translation_Batch_Factory {

	/** @var  WPML_Translation_Basket $basket */
	private $basket;

	/**
	 * @param WPML_Translation_Basket $basket
	 */
	public function __construct( WPML_Translation_Basket $basket ) {
		$this->basket = $basket;
	}


	/**
	 * @param array $batch_data
	 *
	 * @return WPML_TM_Translation_Batch
	 */
	public function create( array $batch_data ) {
		$translators = isset( $batch_data['translators'] ) ? $batch_data['translators'] : array();
		$basket_name = isset( $batch_data['basket_name'] ) ? filter_var(
			$batch_data['basket_name'],
			FILTER_SANITIZE_STRING
		) : '';
		$elements    = apply_filters(
			'wpml_tm_batch_factory_elements',
			$this->get_elements( $batch_data, array_keys( $translators ) ),
			$basket_name
		);

		$deadline_date = null;
		if ( isset( $batch_data['deadline_date'] ) && $this->validate_deadline( $batch_data['deadline_date'] ) ) {
			$deadline_date = new DateTime( $batch_data['deadline_date'] );
		}

		return new WPML_TM_Translation_Batch(
			$elements,
			$basket_name,
			$translators,
			$deadline_date
		);
	}

	private function get_elements( array $batch_data, array $translators_target_languages ) {
		if ( ! isset( $batch_data['batch'] ) || empty( $batch_data['batch'] ) ) {
			return array();
		}

		$basket = $this->basket->get_basket();

		if ( ! isset( $basket['post'] ) ) {
			$basket['post'] = array();
		}
		if ( ! isset( $basket['string'] ) ) {
			$basket['string'] = array();
		}

		$basket_items_types = array_keys( $this->basket->get_item_types() );

		$result = array();
		foreach ( $batch_data['batch'] as $item ) {
			$element_id   = $item['post_id'];
			$element_type = $item['type'];

			if ( ! in_array( $element_type, $basket_items_types, true ) ) {
				continue;
			}
			if ( ! isset( $basket[ $element_type ][ $element_id ] ) ) {
				continue;
			}

			$basket_item = $basket[ $element_type ][ $element_id ];

			$target_languages = array_intersect_key(
				$basket_item['to_langs'],
				array_combine( $translators_target_languages, $translators_target_languages )
			);
			if ( empty( $target_languages ) ) {
				throw new InvalidArgumentException( 'Element\'s target languages do not match to batch list' );
			}

			$media_to_translations = isset( $basket_item['media-translation'] ) ? $basket_item['media-translation'] : array();

			$result[] = new WPML_TM_Translation_Batch_Element(
				$element_id,
				$element_type,
				$basket[ $element_type ][ $element_id ]['from_lang'],
				$target_languages,
				$media_to_translations
			);
		}

		return $result;
	}

	/**
	 * The expected format is "2017-09-28"
	 *
	 * @param string $date
	 *
	 * @return bool
	 */
	private function validate_deadline( $date ) {
		$date_parts = explode( '-', $date );

		return is_array( $date_parts ) &&
			   count( $date_parts ) === 3 &&
			   checkdate( $date_parts[1], $date_parts[2], $date_parts[0] );
	}
}
