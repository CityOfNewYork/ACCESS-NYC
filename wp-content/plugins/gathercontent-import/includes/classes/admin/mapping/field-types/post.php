<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Views\View;

class Post extends Base implements Type {

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text',
		'text_rich',
		'text_plain',
		'choice_radio',
	);

	protected $type_id = 'wp-type-post';
	protected $post_options = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( array $post_options ) {
		$this->post_options = $post_options;
		$this->option_label = __( 'Post Data', 'gathercontent-import' );
	}

	public function underscore_template( View $view ) {
		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
			<select class="gc-select2 wp-type-value-select <?php $this->e_type_id(); ?>" name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
				<?php $this->underscore_options( $this->post_options ); ?>
				<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
			</select>
		<# } #>
		<?php
	}

}
