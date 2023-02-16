<?php

class WPML_TM_Translation_Batch {
	/** @var WPML_TM_Translation_Batch_Element[] */
	private $elements;

	/** @var string */
	private $basket_name;

	/** @var array */
	private $translators;

	/** @var DateTime */
	private $deadline;

	/**
	 * @param WPML_TM_Translation_Batch_Element[] $elements
	 * @param string                              $basket_name
	 * @param array                               $translators
	 * @param DateTime                            $deadline
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $elements, $basket_name, array $translators, DateTime $deadline = null ) {
		if ( empty( $elements ) ) {
			throw new InvalidArgumentException( 'Batch elements cannot be empty' );
		}

		if ( empty( $basket_name ) ) {
			throw new InvalidArgumentException( 'Basket name cannot be empty' );
		}

		if ( empty( $translators ) ) {
			throw new InvalidArgumentException( 'Translators array cannot be empty' );
		}

		$this->elements    = $elements;
		$this->basket_name = (string) $basket_name;
		$this->translators = $translators;
		$this->deadline    = $deadline;
	}

	/**
	 * @return WPML_TM_Translation_Batch_Element[]
	 */
	public function get_elements() {
		return $this->elements;
	}

	public function add_element( WPML_TM_Translation_Batch_Element $element ) {
		$this->elements[] = $element;
	}

	/**
	 * @param string $type
	 *
	 * @return WPML_TM_Translation_Batch_Element[]
	 */
	public function get_elements_by_type( $type ) {
		$result = array();
		foreach ( $this->get_elements() as $element ) {
			if ( $element->get_element_type() === $type ) {
				$result[] = $element;
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function get_basket_name() {
		return $this->basket_name;
	}

	/**
	 * @return array
	 */
	public function get_translators() {
		return $this->translators;
	}

	public function get_translator( $lang ) {
		return $this->translators[ $lang ];
	}

	/**
	 * @return DateTime
	 */
	public function get_deadline() {
		return $this->deadline;
	}

	/**
	 * @return array
	 */
	public function get_target_languages() {
		$result = array();
		foreach ( $this->get_elements() as $element ) {
			$result[] = array_keys( $element->get_target_langs() );
		}

		return array_values( array_unique( call_user_func_array( 'array_merge', $result ) ) );
	}

	/**
	 * @return array
	 */
	public function get_remote_target_languages() {
		return array_values(
			array_filter(
				$this->get_target_languages(),
				array(
					$this,
					'is_remote_target_language',
				)
			)
		);
	}

	private function is_remote_target_language( $lang ) {
		return isset( $this->translators[ $lang ] ) && ! is_numeric( $this->translators[ $lang ] );
	}

	/**
	 * @return array
	 */
	public function get_batch_options() {
		return array(
			'basket_name'   => $this->get_basket_name(),
			'deadline_date' => $this->get_deadline() ? $this->get_deadline()->format( 'Y-m-d' ) : '',
		);
	}
}
