<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CheckboxList
 */
class NF_Fields_ListCheckbox extends NF_Abstracts_List
{
    protected $_name = 'listcheckbox';

    protected $_type = 'listcheckbox';

    protected $_nicename = 'Checkbox List';

    protected $_section = 'common';

    protected $_icon = 'list';

    protected $_templates = 'listcheckbox';

    protected $_old_classname = 'list-checkbox';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Checkbox List', 'ninja-forms' );

        add_filter( 'ninja_forms_merge_tag_calc_value_' . $this->_type, array( $this, 'get_calc_value' ), 10, 2 );
    }

    public function admin_form_element( $id, $value )
    {
        $field = Ninja_Forms()->form()->get_field( $id );

        $list = '';
        foreach( $field->get_setting( 'options' ) as $option ){
            $checked = ( in_array( $option[ 'value' ], $value ) ) ? "checked" : '';
            $list .= "<li><label><input type='checkbox' value='{$option[ 'value' ]}' name='fields[$id][]' $checked> {$option[ 'label' ]}</label></li>";
        }

        return "<ul>$list</ul>";
    }

    public function get_calc_value( $value, $field )
    {
        $selected = explode( ',', $value );
        $value = 0;
        if( isset( $field[ 'options' ] ) ) {
            foreach ($field['options'] as $option ) {
                if( ! isset( $option[ 'value' ] ) || ! in_array( $option[ 'value' ], $selected )  || ! isset( $option[ 'calc' ] ) ) continue;
                $value +=  $option[ 'calc' ];
            }
        }
        return $value;
    }
}
