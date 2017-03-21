<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Shipping
 */
class NF_Fields_Shipping extends NF_Abstracts_Input
{
    protected $_name = 'shipping';

    protected $_section = 'pricing';

    protected $_icon = 'truck';

    protected $_aliases = array();

    protected $_type = 'shipping';

    protected $_templates = 'shipping';

    protected $_test_value = '0.00';

    protected $_settings =  array( 'shipping_type', 'shipping_cost', 'shipping_options' );

    protected $_settings_exclude = array( 'input_limit_set', 'disable_input', 'required' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Shipping', 'ninja-forms' );

        add_filter( 'ninja-forms-field-settings-groups', array( $this, 'add_setting_group' ) );

        add_filter( 'ninja_forms_merge_tag_value_shipping', array( $this, 'merge_tag_value' ), 10, 2 );
    }

    public function add_setting_group( $groups )
    {
        $groups[ 'advanced_shipping' ] = array(
            'id' => 'advanced_shipping',
            'label' => __( 'Advanced Shipping', 'ninja-forms' ),
        );

        return $groups;
    }

    public function admin_form_element( $id, $value )
    {
        $field = Ninja_Forms()->form()->get_field( $id );

        $value = $field->get_setting( 'shipping_cost' );

        switch( $field->get_setting( 'shipping_type' ) ){
            case 'single':

                return "<input class='widefat' name='fields[$id]' value='$value' />";

            case 'select':

                $options = '<option>--</option>';
                foreach( $field->get_setting( 'shipping_options' ) as $option ){
                    $selected = ( $value == $option[ 'value' ] ) ? "selected" : '';
                    $options .= "<option value='{$option[ 'value' ]}' $selected>{$option[ 'label' ]}</option>";
                }

                return "<select class='widefat' name='fields[$id]' id=''>$options</select>";

            default:
                return "";
        }
    }

    public function merge_tag_value( $value, $field )
    {
        if( isset( $field[ 'shipping_type' ] ) ){

            switch( $field[ 'shipping_type' ] ){
                case 'single':
                    $value = $field[ 'shipping_cost' ];
                    break;
                case 'select':
                    $value = $field[ 'shipping_options' ];
                    break;
            }
        }
        $value = preg_replace ('/[^\d,\.]/', '', $value );
        return $value;
    }
}
