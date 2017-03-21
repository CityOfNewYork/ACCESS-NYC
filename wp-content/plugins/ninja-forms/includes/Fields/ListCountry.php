<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CountryList
 */
class NF_Fields_ListCountry extends NF_Abstracts_List
{
    protected $_name = 'listcountry';

    protected $_type = 'listcountry';

    protected $_nicename = 'Country';

    protected $_section = 'userinfo';

    protected $_templates = array( 'listcountry', 'listselect' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Country', 'ninja-forms' );

        $this->_settings[ 'options' ][ 'group' ] = '';
//        $this->_settings[ 'options' ][ 'value' ] = $this->get_options();

        $this->_settings[ 'default' ] = array(
            'name' => 'default',
            'type' => 'select',
            'label' => __( 'Default Value', 'ninja-forms' ),
            'options' => $this->get_default_value_options(),
            'width' => 'one-half',
            'group' => 'primary',
            'value' => 'US',
        );

        add_filter( 'ninja_forms_custom_columns',                 array( $this, 'custom_columns'   ), 10, 2 );
        add_filter( 'ninja_forms_render_options_' . $this->_name, array( $this, 'filter_options'   ), 10, 2 );
        add_filter( 'ninja_forms_subs_export_pre_value',          array( $this, 'filter_csv_value' ), 10, 3 );
    }

    public function custom_columns( $value, $field )
    {
        if( $this->_name != $field->get_setting( 'type' ) ) return $value;

        foreach( Ninja_Forms()->config( 'CountryList' ) as $country => $abbr ){
            if( $value == $abbr ) return $country;
        }

        return $value;
    }

    public function filter_options( $options, $settings )
    {
        $default_value = ( isset( $settings[ 'default' ] ) ) ? $settings[ 'default' ] : '';

        $options = $this->get_options(); // Overwrite the default list options.
        foreach( $options as $key => $option ){
            if( $default_value != $option[ 'value' ] ) continue;
            $options[ $key ][ 'selected' ] = 1;
        }

        return $options;
    }

    public function filter_options_preview( $field_settings )
    {
        $field_settings[ 'settings' ][ 'options' ] = $this->get_options();

        foreach( $field_settings[ 'settings' ][ 'options' ] as $key => $option ){
            if( $field_settings[ 'settings' ][ 'default' ] != $option[ 'value' ] ) continue;
            $field_settings[ 'settings' ][ 'options' ][ $key ][ 'selected' ] = 1;
        }

        return $field_settings;
    }

    public function admin_form_element( $id, $value )
    {
        ob_start();
        echo "<select name='fields[$id]'>";
        foreach( Ninja_Forms()->config( 'CountryList' ) as $label => $abbr ){
            $selected = ( $value == $abbr ) ? ' selected' : '';
            echo "<option value='" . $abbr . "'" . $selected . ">" . $label . "</option>";
        }
        echo "</select>";
        return ob_get_clean();
    }

    private function get_default_value_options()
    {
        $options = array();
        foreach( Ninja_Forms()->config( 'CountryList' ) as $label => $value ){
            $options[] = array(
                'label'  => $label,
                'value' => $value,
            );
        }

        return $options;
    }

    private function get_options()
    {
        $order = 0;
        $options = array();
        foreach( Ninja_Forms()->config( 'CountryList' ) as $label => $value ){
            $options[] = array(
                'label'  => $label,
                'value' => $value,
                'calc' => '',
                'selected' => 0,
                'order' => $order
            );

            $order++;
        }

        return $options;
    }

    public function filter_csv_value( $field_value, $field_id, $form_id )
    {
        $field = Ninja_Forms()->form( $form_id )->get_field( $field_id );
        if( $this->_name == $field->get_setting( 'type' ) ){
            $lookup = array_flip( Ninja_Forms()->config( 'CountryList' ) );
            if( isset( $lookup[ $field_value ] ) ) $field_value = $lookup[ $field_value ];
        }
        return $field_value;
    }
}
