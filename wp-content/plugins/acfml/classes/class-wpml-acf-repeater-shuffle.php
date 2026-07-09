<?php

use ACFML\FieldGroup\Mode;
use ACFML\FieldState;
use ACFML\Helper\Fields;
use ACFML\Helper\HashCalculator;
use ACFML\Repeater\Shuffle\Strategy;
use ACFML\Repeater\Sync\CheckboxUI;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Relation;
use WPML\FP\Obj;

/**
 * Handle the case when the user changes translated repeater state (reorder fields, add new field in the middle...).
 *
 * @package acfml
 */
class WPML_ACF_Repeater_Shuffle implements \IWPML_Backend_Action {

	/**
	 * @var array Meta data after the shuffle.
	 */
	private $meta_data_after_move = [];
	/**
	 * @var array Final metadata values in another languages to save
	 */
	private $meta_to_update;

	/**
	 * @var Strategy
	 */
	private $shuffled;

	/**
	 * @var bool|int
	 */
	private $trid;

	/**
	 * @var FieldState
	 */
	private $field_state;

	/**
	 * @var string
	 */
	private $previousHash = '';

	/**
	 * WPML_ACF_Repeater_Shuffle constructor.
	 *
	 * @param Strategy   $shuffled
	 * @param FieldState $field_state
	 */
	public function __construct( Strategy $shuffled, FieldState $field_state ) {
		$this->shuffled    = $shuffled;
		$this->field_state = $field_state;
	}

	/**
	 * Registers hooks used while repeater field's values are being updated.
	 */
	public function add_hooks() {
		if ( Mode::LOCALIZATION !== Mode::getForFieldableEntity( $this->shuffled->getEntityType() ) ) {
			add_action( 'acf/save_post', [ $this, 'store_state_before' ], 5, 1 );
			add_action( 'acf/save_post', [ $this, 'update_translated_repeaters' ], 15, 1 );
			add_filter( 'wpml_custom_field_values_for_post_signature', [ $this, 'revertFieldValuesForSignature' ], 10, 2 );
		}
	}

	/**
	 * Load all existing translations for this post and all existing metadata for this post.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function store_state_before( $post_id = 0 ) {
		if ( $this->shouldSupportSync( $post_id ) ) {
			$this->field_state->storeStateBefore( $post_id );
			$this->previousHash = $this->calculateHash( $post_id );
		}
	}

	/**
	 * @param int|string $entityId
	 *
	 * @return bool
	 */
	public function shouldSupportSync( $entityId ) {
		$isEntityTranslatable = function() {
			$isTranslatable = Mode::TRANSLATION === Mode::getForFieldableEntity( $this->shuffled->getEntityType() );

			return $isTranslatable || CheckboxUI::isSelected();
		};

		return $this->shuffled->isOriginal( $entityId ) && $isEntityTranslatable();
	}

	/**
	 * @param int $post_id ID of the post being saved.
	 */
	public function update_translated_repeaters( $post_id = 0 ) {
		if ( $this->should_translation_update_run( $post_id ) ) {
			$this->meta_data_after_move = $this->field_state->getCurrentMetadata( $post_id );

			foreach ( $this->meta_data_after_move as $key => $value ) {
				$key_change = $this->get_keys_for_meta_value_changed( $key, $value );
				if ( $key_change ) {
					$translations = $this->shuffled->getTranslations( $post_id );
					if ( $translations ) {
						$this->remove_deprecated_meta( $translations, $key_change['was'], $key_change['is'] );
					}
				}
			}

			$this->readd_meta();
		}
	}

	/**
	 * Checks if post ID is given and if the state for comparision is already saved.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function should_translation_update_run( $post_id = 0 ) {
		return $this->shouldSupportSync( $post_id ) &&
			$this->shuffled->isValidId( $post_id ) &&
			$this->field_state->getStateBefore() &&
			$this->previousHash === $this->calculateHash( $post_id );
	}

	/**
	 * Returns meta key changed for given meta value.
	 *
	 * @param string $meta_key_after_move   The new meta key.
	 * @param mixed  $meta_value_after_move Meta value.
	 *
	 * @return array
	 */
	private function get_keys_for_meta_value_changed( $meta_key_after_move, $meta_value_after_move ) {
		$changed = [];
		// For given meta value after move, find related keys in data before move.
		$keys_before_move = $this->keys_before_move( $meta_value_after_move );
		if ( $keys_before_move ) {
			// Now find keys for the same data but after move.
			$keys_after_move = $this->keys_after_move( $meta_value_after_move );
			// Check if keys are different.
			$key_was = array_diff( $keys_before_move, $keys_after_move );
			$key_is  = array_diff( $keys_after_move, $keys_before_move );
			$found   = array_search( $meta_key_after_move, $key_is, true );
			if ( false !== $found ) {
				$key_was = array_values( $key_was );
				$key_is  = array_values( $key_is );
				if ( $key_was && $key_is ) {
					$changed = [
						'was' => array_shift( $key_was ),
						'is'  => array_shift( $key_is ),
					];
				}
			}
		}
		return $changed;
	}

	/**
	 * Returns new meta keys for given meta value.
	 *
	 * @param mixed $meta_value_after_move
	 *
	 * @return array
	 */
	private function keys_after_move( $meta_value_after_move ) {
		return array_keys( $this->meta_data_after_move, $meta_value_after_move, true );
	}

	/**
	 * Returns original meta keys for given meta value.
	 *
	 * @param mixed $meta_value_after_move
	 *
	 * @return array
	 */
	private function keys_before_move( $meta_value_after_move ) {
		return array_keys( $this->field_state->getStateBefore(), $meta_value_after_move, true );
	}

	/**
	 * Re-add metas again from saved pairs.
	 */
	private function readd_meta() {
		if ( $this->meta_to_update ) {
			foreach ( $this->meta_to_update as $translated_post_id => $meta_pairs ) {
				foreach ( $meta_pairs as $pair ) {
					$this->shuffled->updateOneMeta( $translated_post_id, $pair[0], $pair[1] );
				}
			}
		}
	}

	/**
	 * Foreach existing translation remove it but keep its value in pair with new key.
	 *
	 * @param array  $translations Post translations to update.
	 * @param string $key_of_original_value Original meta key.
	 * @param string $meta_key_after_move New meta key.
	 */
	private function remove_deprecated_meta( array $translations, $key_of_original_value, $meta_key_after_move ) {
		foreach ( $translations as $language_code => $translated_post_data ) {
			$translated_meta_value =
				$this->shuffled->getOneMeta( $translated_post_data->element_id, $key_of_original_value, true );
			if ( $translated_meta_value ) {
				$this->shuffled->deleteOneMeta( $translated_post_data->element_id, $key_of_original_value );
				$this->meta_to_update[ $translated_post_data->element_id ][] = [
					$meta_key_after_move,
					$translated_meta_value,
				];
			}
		}
	}

	/**
	 * If option to synchronise custom fields has been selected, replace repeater subfields
	 * with values from version before meta data update.
	 *
	 * It runs when WPML calculates post md5 to compare with md5s of translations.
	 * In case user shuffled field we don't want to mark post as needed to be translated.
	 *
	 * @param array $customFields
	 * @param int   $postId
	 *
	 * @return array
	 */
	public function revertFieldValuesForSignature( $customFields, $postId = 0 ) {
		if ( CheckboxUI::isSelected() && is_array( $customFields ) && ! empty( $customFields ) && is_numeric( $postId ) && $postId > 0 ) {
			foreach ( $customFields as $key => $value ) {
				if ( isset( $this->field_state->getStateBefore()[ $key ] )
					 && $this->isChildOfRepeaterField( $key, $postId )
					 && $this->get_keys_for_meta_value_changed( $key, $value )
				) {
					$customFields[ $key ] = $this->field_state->getStateBefore()[ $key ];
				}
			}
		}

		return $customFields;
	}

	/**
	 * Check if processed field is a child of Repeater field.
	 *
	 * @param string $key
	 * @param int    $postId
	 *
	 * @return bool
	 */
	private function isChildOfRepeaterField( $key, $postId ) {
		$acfFieldObject = get_field_object( $key, $postId );
		if ( isset( $acfFieldObject['parent'] )
			 && $acfFieldObject['parent'] > 0 ) {
			$fieldParent        = get_post( $acfFieldObject['parent'] );
			$fieldParentContent = maybe_unserialize( $fieldParent->post_content );
			if ( isset( $fieldParentContent['type'] ) && 'repeater' === $fieldParentContent['type'] ) {
				return true;
			}
		}
		return false;
	}

	private function calculateHash( $post_id ) {
		$isOrContainsOrderable = function( $field ) {
			return Fields::isWrapper( $field ) || Relation::propEq( 'type', 'group', $field );
		};

		// $normalize :: ( array|false ) -> array
		$normalize = Logic::ifElse( Logic::isArray(), Fns::identity(), Fns::always( [] ) );

		$values = wpml_collect( $normalize( get_field_objects( $post_id ) ) )
			->filter( $isOrContainsOrderable )
			->map( Obj::prop( 'value' ) )->toArray();

		return HashCalculator::calculate( $values );
	}
}
