<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Views\View;

class Meta extends Base implements Type {

	protected $type_id = 'wp-type-meta';

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text',
		'files',
		'text_rich',
		'text_plain',
		'choice_radio',
		'choice_checkbox',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->option_label = __( 'Custom Fields', 'gathercontent-import' );
	}

	public function underscore_template( View $view ) {
		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
			<select class="gc-select2 gc-select2-add-new wp-type-value-select <?php $this->e_type_id(); ?>" name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
				<# _.each( data.metaKeys, function( key ) { #>
					<option <# if ( key.value === data.field_value ) { #>selected="selected"<# } #> value="{{ key.value }}">{{ key.value }}</option>
				<# }); #>
				<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
			</select>
		<# } #>
		<?php
	}

}
