<?php

namespace BulkWP\BulkDelete\Core\Terms\Modules;

use BulkWP\BulkDelete\Core\Terms\TermsModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete Terms by Post Count.
 *
 * @since 6.0.0
 */
class DeleteTermsByPostCountModule extends TermsModule {
	protected function initialize() {
		$this->item_type     = 'terms';
		$this->field_slug    = 'terms_by_post_count';
		$this->meta_box_slug = 'bd_delete_terms_by_post_count';
		$this->action        = 'delete_terms_by_post_count';
		$this->messages      = array(
			'box_label' => __( 'Delete Terms by Post Count', 'bulk-delete' ),
			'scheduled' => __( 'The selected terms are scheduled for deletion', 'bulk-delete' ),
		);
	}

	public function render() {
		?>

		<fieldset class="options">
			<h4><?php _e( 'Select the taxonomy from which you want to delete terms', 'bulk-delete' ); ?></h4>

			<?php $this->render_taxonomy_dropdown(); ?>

			<h4><?php _e( 'Choose your filtering options', 'bulk-delete' ); ?></h4>

			<?php _e( 'Delete Terms if the post count is ', 'bulk-delete' ); ?>
			<?php $this->render_number_comparison_operators(); ?>
			<input type="number" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>" placeholder="Post count" min="0">
			<?php
			$markup  = '';
			$content = __( 'Post count is the number of posts that are assigned to a term.', 'bulk-delete' );
			echo '&nbsp' . bd_generate_help_tooltip( $markup, $content );
			?>
		</fieldset>

		<?php
		$this->render_submit_button();
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validatePostCount';
		$js_array['error_msg'][ $this->action ]  = 'validPostCount';
		$js_array['msg']['validPostCount']       = __( 'Please enter the post count based on which terms should be deleted. A valid post count will be greater than or equal to zero', 'bulk-delete' );

		$js_array['pre_action_msg'][ $this->action ] = 'deleteTermsWarning';
		$js_array['msg']['deleteTermsWarning']       = __( 'Are you sure you want to delete all the terms based on the selected option?', 'bulk-delete' );

		return $js_array;
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['operator']   = sanitize_text_field( bd_array_get( $request, 'smbd_' . $this->field_slug . '_operator' ) );
		$options['post_count'] = absint( bd_array_get( $request, 'smbd_' . $this->field_slug ) );

		return $options;
	}

	protected function get_term_ids_to_delete( $options ) {
		$term_ids = array();

		$terms = $this->get_all_terms( $options['taxonomy'] );
		foreach ( $terms as $term ) {
			if ( $this->should_delete_term_based_on_post_count( $term->count, $options['operator'], $options['post_count'] ) ) {
				$term_ids[] = $term->term_id;
			}
		}

		return $term_ids;
	}

	/**
	 * Determine if a term should be deleted based on post count.
	 *
	 * @param int    $term_post_count Number of posts associated with a term.
	 * @param string $operator        Operator.
	 * @param int    $compared_to     The user entered value to which the comparison should be made.
	 *
	 * @return int term id.
	 */
	protected function should_delete_term_based_on_post_count( $term_post_count, $operator, $compared_to ) {
		switch ( $operator ) {
			case '=':
				return $term_post_count === $compared_to;
			case '!=':
				return $term_post_count !== $compared_to;
			case '<':
				return $term_post_count < $compared_to;
			case '>':
				return $term_post_count > $compared_to;
		}
	}
}
