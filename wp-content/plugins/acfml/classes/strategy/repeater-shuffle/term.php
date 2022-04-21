<?php

namespace ACFML\Repeater\Shuffle;

class Term extends Strategy {
	/**
	 * @var string
	 */
	protected $id_prefix = 'term_';

	/**
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Term constructor.
	 *
	 * @param string $taxonomy
	 */
	public function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * @param mixed $id
	 *
	 * @return bool
	 */
	public function isValidId( $id ) {
		return strpos( $id, $this->id_prefix ) === 0 && $this->getNumericId( $id ) > 0;
	}

	/**
	 * Get value object for given term ID.
	 *
	 * @param  string $id The element ID with "term_" at the beginning.
	 * @return object|void Value object with id and type or null when element not found.
	 */
	protected function getElement( $id ) {
		if ( $this->isValidId( $id ) ) {
			$id   = $this->getNumericId( $id );
			$term = get_term( $id );
			if ( isset( $term->taxonomy ) ) {
				return (object) [
					'id'   => $id,
					'type' => $term->taxonomy,
				];
			}
		}
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function getAllMeta( $id ) {
		return get_term_meta( $this->getNumericId( $id ) );
	}

	/**
	 * @param int    $id
	 * @param string $key
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	public function getOneMeta( $id, $key, $single = true ) {
		return get_term_meta( $this->getNumericId( $id ), $key, $single );
	}

	/**
	 * @param int    $id
	 * @param string $key
	 *
	 * @return mixed|void
	 */
	public function deleteOneMeta( $id, $key ) {
		delete_term_meta( $this->getNumericId( $id ), $key );
	}

	/**
	 * @param int    $id
	 * @param string $key
	 * @param mixed  $val
	 *
	 * @return mixed|void
	 */
	public function updateOneMeta( $id, $key, $val ) {
		update_term_meta( $this->getNumericId( $id ), $key, $val );
	}

	/**
	 * @param int|null $id
	 *
	 * @return mixed|void
	 */
	protected function get_element_type( $id = null ) {
		return apply_filters( 'wpml_element_type', $this->taxonomy );
	}
}
