<?php

namespace BulkWP\BulkDelete\Core\Terms\Modules;

use BulkWP\BulkDelete\Core\Terms\TermsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Terms by Name.
 *
 * @since 6.0.0
 */
class DeleteTermsByNameModule extends TermsModule {
	protected function initialize() {
		$this->item_type     = 'terms';
		$this->field_slug    = 'terms_by_name';
		$this->meta_box_slug = 'bd_delete_terms_by_name';
		$this->action        = 'delete_terms_by_name';
		$this->messages      = array(
			'box_label' => __( 'Delete Terms by Name', 'bulk-delete' ),
			'scheduled' => __( 'The selected terms are scheduled for deletion', 'bulk-delete' ),
		);
	}

	public function render() {
		?>

		<fieldset class="options">
			<h4><?php _e( 'Select the taxonomy from which you want to delete terms', 'bulk-delete' ); ?></h4>

			<?php $this->render_taxonomy_dropdown(); ?>

			<h4><?php _e( 'Choose your filtering options', 'bulk-delete' ); ?></h4>

			<?php _e( 'Delete Terms if the name ', 'bulk-delete' ); ?>
			<?php $this->render_string_comparison_operators(); ?>
			<input type="text" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_value" placeholder="<?php _e( 'Term Name', 'bulk-delete' ); ?>">
		</fieldset>

		<?php
		$this->render_submit_button();
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validateTermName';
		$js_array['error_msg'][ $this->action ]  = 'enterTermName';
		$js_array['msg']['enterTermName']        = __( 'Please enter the term name that should be deleted', 'bulk-delete' );

		$js_array['pre_action_msg'][ $this->action ] = 'deleteTermsWarning';
		$js_array['msg']['deleteTermsWarning']       = __( 'Are you sure you want to delete all the terms based on the selected option?', 'bulk-delete' );

		return $js_array;
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['operator'] = sanitize_text_field( bd_array_get( $request, 'smbd_' . $this->field_slug . '_operator' ) );
		$options['value']    = sanitize_text_field( bd_array_get( $request, 'smbd_' . $this->field_slug . '_value' ) );

		return $options;
	}

	protected function get_term_ids_to_delete( $options ) {
		$term_ids = array();
		$value    = $options['value'];
		$operator = $options['operator'];
		if ( empty( $value ) ) {
			return $term_ids;
		}

		switch ( $operator ) {
			case 'equal_to':
				$term_ids = $this->get_terms_that_are_equal_to( $value, $options );
				break;

			case 'not_equal_to':
				$term_ids = $this->get_terms_that_are_not_equal_to( $value, $options );
				break;

			case 'starts_with':
				$term_ids = $this->get_terms_that_starts_with( $value, $options );
				break;

			case 'ends_with':
				$term_ids = $this->get_terms_that_ends_with( $value, $options );
				break;

			case 'contains':
				$term_ids = $this->get_terms_that_contains( $value, $options );
				break;

			case 'not_contains':
				$term_ids = $this->get_terms_that_not_contains( $value, $options );
				break;
		}

		return $term_ids;
	}

	/**
	 * Get terms with name that are equal to a specific string.
	 *
	 * @param string $value   Value to compare.
	 * @param array  $options User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_are_equal_to( $value, $options ) {
		$query = array(
			'taxonomy' => $options['taxonomy'],
			'name'     => $value,
		);

		return $this->query_terms( $query );
	}

	/**
	 * Get terms with that name that is not equal to a specific string.
	 *
	 * @param string $value   Value to compare.
	 * @param array  $options User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_are_not_equal_to( $value, $options ) {
		$name_like_args = array(
			'name'     => $value,
			'taxonomy' => $options['taxonomy'],
		);

		$query = array(
			'taxonomy' => $options['taxonomy'],
			'exclude'  => $this->query_terms( $name_like_args ),
		);

		return $this->query_terms( $query );
	}

	/**
	 * Get terms with name that start with a specific string.
	 *
	 * @param string $starts_with Substring to search.
	 * @param array  $options     User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_starts_with( $starts_with, $options ) {
		$term_ids = array();
		$terms    = $this->get_all_terms( $options['taxonomy'] );

		foreach ( $terms as $term ) {
			if ( bd_starts_with( $term->name, $starts_with ) ) {
				$term_ids[] = $term->term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Get terms with name that ends with a specific string.
	 *
	 * @param string $ends_with Substring to search.
	 * @param array  $options   User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_ends_with( $ends_with, $options ) {
		$term_ids = array();
		$terms    = $this->get_all_terms( $options['taxonomy'] );

		foreach ( $terms as $term ) {
			if ( bd_ends_with( $term->name, $ends_with ) ) {
				$term_ids[] = $term->term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Get terms with name that contains a specific string.
	 *
	 * @param string $contains Substring to search.
	 * @param array  $options  User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_contains( $contains, $options ) {
		$term_ids = array();
		$terms    = $this->get_all_terms( $options['taxonomy'] );

		foreach ( $terms as $term ) {
			if ( bd_contains( $term->name, $contains ) ) {
				$term_ids[] = $term->term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Get terms with name that doesn't contain a specific string.
	 *
	 * @param string $contains Substring to search.
	 * @param array  $options  User options.
	 *
	 * @return int[] Term ids.
	 */
	protected function get_terms_that_not_contains( $contains, $options ) {
		$term_ids = array();
		$terms    = $this->get_all_terms( $options['taxonomy'] );

		foreach ( $terms as $term ) {
			if ( ! bd_contains( $term->name, $contains ) ) {
				$term_ids[] = $term->term_id;
			}
		}

		return $term_ids;
	}
}
