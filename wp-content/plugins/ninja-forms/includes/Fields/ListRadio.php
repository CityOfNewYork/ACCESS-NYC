<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_RadioList
 */
class NF_Fields_ListRadio extends NF_Abstracts_List
{
    protected $_name = 'listradio';

    protected $_type = 'listradio';

    protected $_section = 'common';

    protected $_icon = 'dot-circle-o';

    protected $_templates = 'listradio';

    protected $_old_classname = 'list-radio';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Radio List', 'ninja-forms' );

        add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );
    }

    public function get_calc_value( $value, $field )
    {
        if( isset( $field[ 'options' ] ) ) {
            foreach ($field['options'] as $option ) {
                if( ! isset( $option[ 'value' ] ) || $value != $option[ 'value' ] || ! isset( $option[ 'calc' ] ) ) continue;
                $value =  $option[ 'calc' ];
            }
        }
        return $value;
    }
}
