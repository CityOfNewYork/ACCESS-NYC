<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_SelectList
 */
class NF_Fields_ListSelect extends NF_Abstracts_List
{
    protected $_name = 'listselect';

    protected $_type = 'listselect';

    protected $_nicename = 'Select';

    protected $_section = 'common';

    protected $_icon = 'chevron-down';

    protected $_templates = 'listselect';

    protected $_old_classname = 'list-select';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Select', 'ninja-forms' );

        add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );
    }

    public function get_calc_value( $value, $field )
    {
        if( isset( $field[ 'options' ] ) ) {
            foreach ($field['options'] as $option ) {
                if( ! isset( $option[ 'value' ] ) || $value != $option[ 'value' ] || ! isset( $option[ 'calc' ] ) ) continue;
                return $option[ 'calc' ];
            }
        }
        return $value;
    }
}
