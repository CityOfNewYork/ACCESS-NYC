<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Views\View;

class WPSEO extends Base implements Type {

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'text',
		'text_rich',
		'text_plain',
	);

	protected $type_id = 'wp-type-meta--seo';
	protected $post_types = array();
	protected $yoast_field_types = array(
		'text',
		'textarea',
		'select',
		'upload',
		'radio',
		'multiselect',
		'focuskeyword',
	);

	/**
	 * SEO meta keys/labels.
	 *
	 * @var array
	 */
	protected $seo_options = array();

	/**
	 * SEO meta keys.
	 *
	 * Label values to be translateable, so moved to __construct.
	 *
	 * @var array
	 */
	protected $seo_keys = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( array $post_types ) {
		$this->post_types = $post_types;
		$this->seo_options = $this->get_seo_options();
		$this->option_label = __( 'SEO', 'gathercontent-import' );

		add_filter( 'gathercontent_importer_custom_field_keys_blacklist', array( $this, 'remove_wpseo_keys' ) );
	}

	protected function get_seo_options() {
		$this->initialize_wpseo();

		$options = array();

		global $post;

		if ( !$post ) {
			$post = (object)array('post_type' => 'post');
		}

		$advanced_fields = \WPSEO_Meta::get_meta_field_defs( 'advanced' );
		$filtered_fields = apply_filters( 'wpseo_save_metaboxes', array() );
		$universal_fields = array_merge( $advanced_fields, $filtered_fields );

		$options = $this->build_options( $universal_fields, 'all_types', $options );

		$cpt_opts = array();
		foreach ( $this->post_types as $post_type => $object ) {

			$cpt_fields = \WPSEO_Meta::get_meta_field_defs( 'general', $post_type );

			$cpt_opts = $this->build_options( $cpt_fields, $post_type, $cpt_opts );
		}

		$options['all_types'] = array_merge( array_reduce( $cpt_opts, 'array_intersect', $cpt_opts[ key( $cpt_opts ) ] ), $options['all_types'] );

		foreach ( $cpt_opts as $post_type => $_cpt_options ) {
			$cpt_opts[ $post_type ] = array_diff( $_cpt_options, $options['all_types'] );
		}

		$options = array_merge( $options, $cpt_opts );

		return $options;
	}

	public function build_options( $fields, $key, $options ) {
		foreach ( $fields as $field_name => $field ) {
			$can_add = in_array( $field_name, array( 'title', 'metadesc' ), 1 );

			if ( ! $can_add ) {
				$can_add = in_array( $field['type'], $this->yoast_field_types ) && ! empty( $field['title'] );
			}

			if ( $can_add ) {

				$seo_key = 'focuskw_text_input' === $field_name
					? '_yoast_wpseo_focuskw'
					: '_yoast_wpseo_' . esc_attr( $field_name );

				$this->seo_keys[ $seo_key ] = $seo_key;
				$options[ $key ][ $seo_key ] = esc_html( $field['title'] );
			}
		}

		return $options;
	}

	protected function initialize_wpseo() {
		if ( !isset($GLOBALS['wpseo_admin']) ) {
			wpseo_init();
			wpseo_admin_init();
			wpseo_load_textdomain();
		}

		$options = \WPSEO_Options::get_all();

		new \WPSEO_Metabox;
		\WPSEO_Metabox::translate_meta_boxes();

		if ( $options['opengraph'] === true || $options['twitter'] === true || $options['googleplus'] === true ) {
			new \WPSEO_Social_Admin;
			\WPSEO_Social_Admin::translate_meta_boxes();
		}
	}

	public function remove_wpseo_keys( $blacklist ) {
		// Remove yoast meta keys from the main meta-keys array.
		$blacklist += $this->seo_keys;

		return $blacklist;
	}

	public function underscore_template( View $view ) {
		$seo_options = $this->seo_options;
		$all = $seo_options['all_types'];
		unset( $seo_options['all_types'] );
		?>
		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>
			<select class="gc-select2 wp-type-value-select <?php $this->e_type_id(); ?>" name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
				<?php $this->underscore_options( $all ); ?>
				<?php foreach ( $seo_options as $post_type => $options ) : ?>
					<# if ( '<?php echo $post_type; ?>' === data.post_type ) { #>
					<?php $this->underscore_options( $options ); ?>
					<# } #>
				<?php endforeach; ?>
				<?php $this->underscore_empty_option( __( 'Do Not Import', 'gathercontent-import' ) ); ?>
			</select>
		<# } #>
		<?php
	}

}
