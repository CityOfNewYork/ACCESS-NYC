<?php

class WPML_TM_Jobs_Sorting_Param {
	/** @var string */
	private $column;

	/** @var string */
	private $direction;

	/**
	 * @param string $column
	 * @param string $direction
	 */
	public function __construct( $column, $direction = 'asc' ) {
		$sortable = WPML_TM_Rest_Jobs_Columns::get_sortable();
		if ( ! isset( $sortable[ $column ] ) ) {
			throw new InvalidArgumentException( "Column {$column} is not sortable." );
		}

		$direction = strtolower( $direction );
		if ( 'asc' !== $direction && 'desc' !== $direction ) {
			$direction = 'asc';
		}

		$this->column    = $column;
		$this->direction = $direction;
	}

	/**
	 * @return string
	 */
	public function get_column() {
		return $this->column;
	}

	/**
	 * @return string
	 */
	public function get_direction() {
		return $this->direction;
	}
}