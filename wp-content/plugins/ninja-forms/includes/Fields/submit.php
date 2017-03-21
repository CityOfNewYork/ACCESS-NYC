<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Button
 */
class NF_Fields_Submit extends NF_Fields_Button
{
    protected $_name = 'submit';

    protected $_section = 'common';

    protected $_icon = 'square';

    protected $_type = 'submit';

    protected $_templates = 'submit';

    protected $_wrap_template = 'wrap-no-label';

    protected $_settings = array( 'label', 'timed_submit', 'processing_label', 'classes', 'key' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Submit', 'ninja-forms' );

        $this->_settings[ 'label' ][ 'width' ] = 'full';

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }

}
