<?php

namespace ACFML\Repeater\Shuffle;

abstract class Strategy {
	/**
	 * @var string Element ID prefix.
	 */
	protected $id_prefix;
	/**
	 * Check if this is valid ID of processed post, term etc.
	 *
	 * @param mixed $id Post or term ID to validate.
	 *
	 * @return bool
	 */
	abstract public function isValidId( $id );

	/**
	 * Gets all post meta or term meta for given ID.
	 *
	 * @param int $id The post or term ID.
	 *
	 * @return mixed
	 */
	abstract public function getAllMeta( $id );

	/**
	 * Gets one post/term meta.
	 *
	 * @param int    $id     The post or term ID.
	 * @param string $key    The meta key.
	 * @param bool   $single
	 *
	 * @return mixed
	 */
	abstract public function getOneMeta( $id, $key, $single );

	/**
	 * Deletes one post/term meta from database.
	 *
	 * @param int    $id  The post or term ID.
	 * @param string $key The meta key.
	 *
	 * @return mixed
	 */
	abstract public function deleteOneMeta( $id, $key );

	/**
	 * Updates term/post meta in database.
	 *
	 * @param int    $id  The post or term ID.
	 * @param string $key The meta key.
	 * @param mixed  $val New value.
	 *
	 * @return mixed
	 */
	abstract public function updateOneMeta( $id, $key, $val );

	/**
	 * Changes term ID into numeric.
	 *
	 * @param string|int $id The post/term ID.
	 *
	 * @return int|string
	 */
	protected function getNumericId( $id ) {
		if ( ! is_numeric( $id ) && isset( $this->id_prefix ) ) {
			$id = substr( $id, strlen( $this->id_prefix ) );
		}
		return (int) $id;
	}

	/**
	 * Checks if given post or term has translations.
	 *
	 * @param int $id Post or term ID.
	 *
	 * @return bool
	 */
	public function hasTranslations( $id ) {
		$has_translations = false;
		$element_type     = $this->get_element_type( $id );
		$trid             = apply_filters( 'wpml_element_trid', null, $this->getNumericId( $id ), $element_type );
		if ( $trid ) {
			$element_translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
			$has_translations = $element_translations && 1 < count( $element_translations );
		}
		return $has_translations;
	}

	/**
	 * Returns post or term translations.
	 *
	 * @param int $id The post or term ID.
	 *
	 * @return array|mixed|void
	 */
	public function getTranslations( $id ) {
		static $element_translations = array();
		static $last_id              = false;

		if ( $id !== $last_id || ! $element_translations ) {
			$element_type = $this->get_element_type( $id );
			$trid         = apply_filters( 'wpml_element_trid', null, $this->getNumericId( $id ), $element_type );
			if ( $trid ) {
				$element_translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
				if ( $element_translations ) {
					foreach ( $element_translations as $language_code => $element ) {
						if ( (int) $element->element_id === $this->getNumericId( $id ) ) {
							unset( $element_translations[ $language_code ] );
						}
					}
				}
			}
		}
		$last_id = $id;
		return $element_translations;
	}
}
