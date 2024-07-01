<?php

namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Views\View;

class Taxonomy extends Base implements Type {

	protected $type_id = 'wp-type-taxonomy';
	protected $post_types = array();

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text_plain',
		'choice_radio',
		'choice_checkbox',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( array $post_types ) {
		$this->post_types   = $post_types;
		$this->option_label = __( 'Taxonomy/Terms', 'content-workflow-by-bynder' );
	}

	public function underscore_options( $tax_array ) {
		foreach ( $tax_array as $taxonomy ) {
			$this->underscore_option( $taxonomy->name, $taxonomy->label );
		}
	}

	public function underscore_template( View $view ) {
		foreach ( $this->post_types as $type ) : ?>
			<# if ( '<?php $this->e_type_id(); ?>' === data.field_type && '<?php echo esc_attr( $type->name ); ?>' === data.post_type ) { #>
			<select
				class="wp-type-value-select <?php $this->e_type_id(); ?> wp-taxonomy-<?php echo esc_html( $type->name ); ?>-type"
				name="<?php $view->get( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
				<?php if ( empty( $type->taxonomies ) ) : ?>
					<option selected="selected"
							value=""><?php esc_html_e( 'N/A', 'content-workflow-by-bynder' ); ?></option>
				<?php else : ?>
					<?php $this->underscore_options( $type->taxonomies ); ?>
					<?php $this->underscore_empty_option( __( 'Do Not Import', 'content-workflow-by-bynder' ) ); ?>
				<?php endif; ?>
			</select>
			<# } #>
		<?php
		endforeach;
	}

}
