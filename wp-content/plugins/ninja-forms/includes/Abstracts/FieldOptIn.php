<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_FieldOptIn
 *
 * Opt-In fields should extend this class.
 *
 * Supports:
 * - Checkbox value processing
 *
 * Planned Support:
 * - Single / Multiple Type Opt-In
 */
abstract class NF_Abstracts_FieldOptIn extends NF_Abstracts_Input
{
    protected $_name = 'optin';

    protected $_section = 'misc';

    protected $_parent_type = 'checkbox';

    protected $_templates = 'optin';

    protected $_settings = array( 'type', 'fieldset', 'checkbox_default_value' );

    protected $_settings_exclude = array( 'default', 'required', 'placeholder', 'input_limit_set', 'disable_input' );

    protected $_lists = array();

    public function __construct()
    {
        parent::__construct();

        /*
         * Setup 'type' options for the opt-in field.
         */
        $this->_settings[ 'type' ][ 'options' ] = array(
            array(
                'label'     => __( 'Single', 'ninja-forms' ),
                'value'     => 'single',
            ),
            array(
                'label'     => __( 'Multiple', 'ninja-forms' ),
                'value'     => 'multiple',
            ),
        );

        /*
         * Add a refresh extra for the groups fieldset.
         */
        $this->_settings[ 'fieldset' ][ 'label' ] = __( 'Lists', 'ninja-forms' ) . ' <a href="#"><small>' . __( 'refresh', 'ninja-forms' ) . '</small></a>';
        $this->_settings[ 'fieldset' ][ 'deps' ] = array( 'type' => 'multiple' );

        /*
         * Hide the 'type' and 'fieldset' ('groups') settings until they are ready for use.
         */
        $this->_settings[ 'type' ][ 'group' ] = '';
        $this->_settings[ 'fieldset' ][ 'group' ] = '';
    }

    protected function addList( $name, $label )
    {
        $this->_settings[ 'fieldset' ][ 'settings' ][] = array(
            'name' => $name,
            'type' => 'toggle',
            'label' => $label,
            'width' => 'full',
            'value' => ''
        );
    }

    protected function addLists( array $lists = array() )
    {
        if( empty( $lists ) ) return;

        foreach( $lists as $name => $label ){
            $this->addList( $name, $label );
        }
    }

    public function get_parent_type(){
        return $this->_parent_type;
    }

}