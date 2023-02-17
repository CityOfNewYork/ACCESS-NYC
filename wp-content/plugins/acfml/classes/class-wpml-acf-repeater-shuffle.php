<?php

use ACFML\FieldState;
use ACFML\Repeater\Shuffle\Strategy;

/**
 * Handle the case when the user changes translated repeater state (reorder fields, add new field in the middle...).
 *
 * @package acfml
 */
class WPML_ACF_Repeater_Shuffle {

	const ACTION_SYNCHRONISE         = 'wpml_synchronise_acf_fields_translations_nonce';
	const SYNCHRONISE_WP_OPTION_NAME = 'acfml_synchronise_repeater_fields';

	/**
	 * @var array Meta data after the shuffle.
	 */
	private $meta_data_after_move;
	/**
	 * @var array Final metadata values in another languages to save
	 */
	private $meta_to_update;

	/**
	 * @var bool Store information if synchronisation checkbox has been already displayed.
	 */
	private $synchronisation_checkbox_displayed = false;

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
	public function register_hooks() {
		add_action( 'acf/save_post', [ $this, 'storeSynchroniseOption' ], 4, 1 );
		add_action( 'acf/save_post', [ $this, 'store_state_before' ], 5, 1 );
		add_action( 'acf/save_post', [ $this, 'update_translated_repeaters' ], 15, 1 );
		add_action( 'acf/render_fields', [ $this, 'display_synchronisation_switcher' ], 10, 2 );
		add_filter( 'wpml_custom_field_values_for_post_signature', [ $this, 'revertFieldValuesForSignature' ], 10, 2 );
	}

	/**
	 * Outputs HTML with checkbox to enable synchronisation for changes in order of fields.
	 *
	 * @param mixed $fields  The ACF fields to display on the post edit screen.
	 * @param int   $element_id Current post ID.
	 */
	public function display_synchronisation_switcher( $fields, $element_id ) {
		if ( $this->hasRepeaterOrFlexibleField( $fields ) && $this->should_display_synchronisation_switcher( $element_id ) ) {
			?>
			<div class="acf-field acfml-synchronise-repeater-checkbox">
				<div class="acf-label">
					<label for="wpml_synchronise_acf_fields_translations"><?php esc_html_e( 'Synchronise translations', 'acfml' ); ?></label>
				</div>
				<div class="acf-input">
					<div class="acf-input-wrap">
						<input type="checkbox" name="wpml_synchronise_acf_fields_translations" value="synchronise" <?php checked( $this->isSynchroniseOptionChecked( $element_id ), true, true ); ?> />
						<?php wp_nonce_field( self::ACTION_SYNCHRONISE, self::ACTION_SYNCHRONISE ); ?>
						<?php esc_html_e( 'Synchronise repeater and flexible sub-fields positions in post translations (record drag-and-drop moves and do the same moves in other translations).', 'acfml' ); ?>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Checks if synchronisation switcher (select box) should be displayed.
	 *
	 * @param int $element_id The element ID.
	 *
	 * @return bool
	 */
	private function should_display_synchronisation_switcher( $element_id ) {
		$should = false;
		if ( ! $this->synchronisation_checkbox_displayed ) {
			$this->synchronisation_checkbox_displayed = true;
			$should                                   = $this->shuffled->hasTranslations( $element_id );
		}
		return $should;
	}

	/**
	 * Load all existing translations for this post and all existing metadata for this post.
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function store_state_before( $post_id = 0 ) {
		if ( $this->synchronise_option_selected() ) {
			$this->field_state->storeStateBefore( $post_id );
		}
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
		return $this->synchronise_option_selected() && $this->shuffled->isValidId( $post_id ) && $this->field_state->getStateBefore();
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
	 * Checks if checkbox to synchronise is selected.
	 *
	 * @return bool
	 */
	private function synchronise_option_selected() {
		return isset( $_POST[ self::ACTION_SYNCHRONISE ] )
			&& wp_verify_nonce( $_POST[ self::ACTION_SYNCHRONISE ], self::ACTION_SYNCHRONISE )
			&& isset( $_POST['wpml_synchronise_acf_fields_translations'] )
			&& 'synchronise' === $_POST['wpml_synchronise_acf_fields_translations'];
	}

	/**
	 * Checks if synchronise checkbox has been sent during the post save.
	 *
	 * @return bool
	 */
	private function synchroniseOptionSent() {
		return isset( $_POST[ self::ACTION_SYNCHRONISE ] );
	}

	/**
	 * Checks if post/taxonomy has repeater field associated with it.
	 *
	 * @param array $fields Fields belonging to the element (post or taxonomy).
	 *
	 * @return bool
	 */
	private function hasRepeaterOrFlexibleField( $fields ) {
		foreach ( (array) $fields as $field ) {
			if ( isset( $field['type'] ) && in_array( $field['type'], [ 'repeater', 'flexible_content' ], true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Save repeater synchronisation option in wp_options table.
	 *
	 * @param int $elementID Processed element (post, taxonomy) ID.
	 */
	public function storeSynchroniseOption( $elementID ) {
		if ( $this->shuffled->hasTranslations( $elementID ) ) {
			$trid = $this->shuffled->getTrid( $elementID );
			if ( $trid && $this->synchroniseOptionSent() ) {
				$synchroniseOption = get_option( self::SYNCHRONISE_WP_OPTION_NAME, [] );
				if ( $this->synchronise_option_selected() ) {
					$synchroniseOption[ $trid ] = true;
				} else {
					$synchroniseOption[ $trid ] = false;
				}
				update_option( self::SYNCHRONISE_WP_OPTION_NAME, $synchroniseOption );
			}
		}
	}

	/**
	 * Get repeater synchronisation option from wp_options table.
	 *
	 * @param int $elementID Processed element (post, taxonomy) ID.
	 *
	 * @return bool
	 */
	protected function isSynchroniseOptionChecked( $elementID ) {
		$trid = $this->shuffled->getTrid( $elementID );
		if ( $trid ) {
			$synchroniseOption = get_option( self::SYNCHRONISE_WP_OPTION_NAME, [] );
			if ( isset( $synchroniseOption[ $trid ] ) ) {
				return (bool) $synchroniseOption[ $trid ];
			}
		}
		return defined( 'ACFML_REPEATER_SYNC_DEFAULT' ) ? (bool) constant( 'ACFML_REPEATER_SYNC_DEFAULT' ) : true;
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
		if ( $this->synchronise_option_selected() && is_array( $customFields ) && ! empty( $customFields ) && is_numeric( $postId ) && $postId > 0 ) {
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
}
