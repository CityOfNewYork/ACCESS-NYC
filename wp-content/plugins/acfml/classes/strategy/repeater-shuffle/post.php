<?php

namespace ACFML\Repeater\Shuffle;

class Post extends Strategy {
	/**
	 * @var string
	 */
	protected $id_prefix = '';

	/**
	 * @return string
	 */
	public function getEntityType() {
		return 'post';
	}

	/**
	 * @param mixed $id
	 *
	 * @return bool
	 */
	public function isValidId( $id ) {
		return is_numeric( $id ) && $id > 0;
	}

	/**
	 * Get value object for given post ID.
	 *
	 * @param  int $id The post ID
	 *
	 * @return object|void Value object with id and type or null when element not found.
	 */
	protected function getElement( $id ) {
		if ( $this->isValidId( $id ) ) {
			return (object) [
				'id'   => $id,
				'type' => get_post_type( $id ),
			];
		}
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function getAllMeta( $id ) {
		return get_post_meta( $id );
	}

	/**
	 * @param int $id
	 * @param string $key
	 * @param bool $single
	 *
	 * @return mixed
	 */
	public function getOneMeta( $id, $key, $single = true ) {
		return get_post_meta( $id, $key, $single );
	}

	/**
	 * @param int $id
	 * @param string $key
	 *
	 * @return mixed|void
	 */
	public function deleteOneMeta( $id, $key ) {
		delete_post_meta( $id, $key );
	}

	/**
	 * @param int $id
	 * @param string $key
	 * @param mixed $val
	 *
	 * @return mixed|void
	 */
	public function updateOneMeta( $id, $key, $val ) {
		update_post_meta( $id, $key, $val );
	}

	/**
	 * @param int|null $id
	 *
	 * @return mixed|void
	 */
	protected function get_element_type( $id ) {
		return apply_filters( 'wpml_element_type', get_post_type( $id ) );
	}
}