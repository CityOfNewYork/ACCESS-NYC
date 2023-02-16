<?php

namespace ACFML\Repeater\Shuffle;

abstract class Strategy {
	/**
	 * @var string Element ID prefix.
	 */
	protected $id_prefix;

	/**
	 * @var false|int Translation ID for given element.
	 */
	protected $trid;

	/** @var array $element_translations */
	protected $element_translations;

	/**
	 * Check if this is valid ID of processed post, term etc.
	 *
	 * @param mixed $id Post or term ID to validate.
	 *
	 * @return bool
	 */
	abstract public function isValidId( $id );

	/**
	 * Get value object for given element ID.
	 *
	 * @param  int|string $id The element ID.
	 *
	 * @return object|null Value object with id and type or null when element not found.
	 */
	abstract protected function getElement( $id );

	/**
	 * Get translation ID for given element.
	 *
	 * @param int|string $elementId Processed element (post, taxonomy) ID.
	 *
	 * @return false|int|string Translated post or term or option page ID, or false if does not exist.
	 */
	public function getTrid( $elementId ) {
		if ( null === $this->trid ) {
			$this->trid = false;
			$element    = $this->getElement( $elementId );
			if ( isset( $element->id, $element->type ) ) {
				$type       = apply_filters( 'wpml_element_type', $element->type );
				$this->trid = apply_filters( 'wpml_element_trid', $this->trid, $element->id, $type );
			}
		}
		return $this->trid;
	}

	/**
	 * Gets all post meta or term meta or options page for given ID.
	 *
	 * @param int|string $id The post or term or options page ID.
	 *
	 * @return mixed
	 */
	abstract public function getAllMeta( $id );

	/**
	 * Gets one post/term/option page's meta.
	 *
	 * @param int|string $id     The post or term or options page ID.
	 * @param string     $key    The meta/option key.
	 * @param bool       $single Return single value.
	 *
	 * @return mixed
	 */
	abstract public function getOneMeta( $id, $key, $single );

	/**
	 * Deletes one post/term/option page's meta from database.
	 *
	 * @param int|string $id  The post or term or options page ID.
	 * @param string     $key The meta/option key.
	 *
	 * @return mixed
	 */
	abstract public function deleteOneMeta( $id, $key );

	/**
	 * Updates term/post meta in database.
	 *
	 * @param int|string $id  The post or term or options page ID.
	 * @param string     $key The meta/option key.
	 * @param mixed      $val New value.
	 *
	 * @return mixed
	 */
	abstract public function updateOneMeta( $id, $key, $val );

	/**
	 * @param int|null $id
	 *
	 * @return string
	 */
	abstract protected function get_element_type( $id );

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
		return count( $this->getTranslations( $id ) ) > 0;
	}

	/**
	 * Returns post or term translations.
	 *
	 * @param int|string $id The post or term or option page ID.
	 *
	 * @return array
	 */
	public function getTranslations( $id ) {
		if ( ! isset( $this->element_translations[ $id ] ) ) {
			$element_type                      = $this->get_element_type( $id );
			$trid                              = apply_filters( 'wpml_element_trid', false, $this->getNumericId( $id ), $element_type );
			$this->element_translations[ $id ] = apply_filters( 'wpml_get_element_translations', [], $trid, $element_type );

			foreach ( $this->element_translations[ $id ] as $language_code => $element ) {
				if ( (int) $element->element_id === $this->getNumericId( $id ) ) {
					unset( $this->element_translations[ $id ][ $language_code ] );
				}
			}
		}

		return $this->element_translations[ $id ];
	}
}
