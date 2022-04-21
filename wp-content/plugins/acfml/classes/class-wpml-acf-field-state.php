<?php

namespace ACFML;

use ACFML\Repeater\Shuffle\Strategy;

class FieldState {
	
	/**
	 * @var array Meta data before the shuffle.
	 */
	private $metaDataBeforeUpdate;
	
	/**
	 * @var Strategy
	 */
	private $shuffled;
	
	const PRIORITY_BEFORE_CF_UPDATED = 5;
	
	/**
	 * WPML_ACF_Repeater_Shuffle constructor.
	 *
	 * @param Strategy $shuffled
	 */
	public function __construct( Strategy $shuffled ) {
		$this->shuffled = $shuffled;
	}
	
	public function registerHooks() {
		add_action( 'acf/save_post', [ $this, 'storeStateBefore' ], self::PRIORITY_BEFORE_CF_UPDATED );
	}
	
	/**
	 * Load all existing translations for this post and all existing metadata for this post.
	 *
	 * @param int $id ID of the post being saved.
	 */
	public function storeStateBefore( $id ) {
		if ( $this->shuffled->isValidId( $id ) && ! $this->metaDataBeforeUpdate ) {
			$this->metaDataBeforeUpdate = $this->getCurrentMetadata( $id );
		}
	}
	
	public function getStateBefore() {
		return $this->metaDataBeforeUpdate;
	}
	
	/**
	 * Returns always meta data array with single values and always related to ACF fields.
	 *
	 * @param int $id Post ID.
	 *
	 * @return array Flatten array of ACF fields.
	 */
	public function getCurrentMetadata( $id ) {
		$metaData = (array) $this->shuffled->getAllMeta( $id );
		foreach ( $metaData as $key => $maybeArray ) {
			$acf_field = get_field_object( $key, $id );
			if ( ! $acf_field ) {
				unset( $metaData[ $key ] );
			} elseif ( is_array( $maybeArray ) ) {
				$metaData[ $key ] = end( $maybeArray );
			}
		}
		return $metaData;
	}
}