<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Textarea
 */
class NF_Fields_Textarea extends NF_Abstracts_Input
{
    protected $_name = 'textarea';

    protected $_section = 'common';

    protected $_icon = 'paragraph';

    protected $_type = 'textarea';

    protected $_templates = 'textarea';

    protected $_test_value = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.';

    protected $_settings = array( 'input_limit_set', 'rte_enable', 'rte_media', 'rte_mobile', 'disable_browser_autocomplete', 'textarea_rte', 'disable_rte_mobile', 'textarea_media', );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Paragraph Text', 'ninja-forms' );

        $this->_settings[ 'default' ][ 'type' ] = 'textarea';
        $this->_settings[ 'placeholder' ][ 'type' ] = 'textarea';

        add_filter( 'ninja_forms_merge_tag_value_' . $this->_name, array( $this, 'filter_merge_tag_value' ), 10, 2 );
    }

    public function admin_form_element( $id, $value )
    {
        return "<textarea class='widefat' name='fields[$id]'>$value</textarea>";
    }

    public function filter_merge_tag_value( $value, $field ) {
        return wpautop( $value );
    }
}
