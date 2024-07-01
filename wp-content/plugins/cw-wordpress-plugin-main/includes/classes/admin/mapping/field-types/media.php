<?php

namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Views\View;

class Media extends Base implements Type {

	protected $type_id = 'wp-type-media';

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'files',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->option_label = __( 'Media', 'content-workflow-by-bynder' );
	}

	public function underscore_template( View $view ) {
		$options = array(
			'featured_image' => __( 'Featured Image', 'content-workflow-by-bynder' ),
			'content_image'  => __( 'Content Image(s)', 'content-workflow-by-bynder' ),
			'excerpt_image'  => __( 'Excerpt Image(s)', 'content-workflow-by-bynder' ),
			'gallery'        => __( 'Gallery', 'content-workflow-by-bynder' ),
			'attachment'     => __( 'Attachment(s)', 'content-workflow-by-bynder' ),
		);

		$options = apply_filters( 'cwby_media_location_options', $options );

		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
		<select class="wp-type-value-select <?php $this->e_type_id(); ?>"
				name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
			<?php $this->underscore_options( $options ); ?>
			<?php $this->underscore_empty_option( __( 'Do Not Import', 'content-workflow-by-bynder' ) ); ?>
		</select>
		<# } #>
		<?php
	}

}
