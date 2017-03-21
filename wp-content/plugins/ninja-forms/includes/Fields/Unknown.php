<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Unknown
 */
class NF_Fields_Unknown extends NF_Fields_Hidden
{
    protected $_name = 'unknown';

    protected $_type = 'unknown';

    protected $_section = '';

    protected $_icon = 'question';

    protected $_templates = 'null';

    protected $_aliases = array();

    protected $_settings_only = array(
        'label', 'default'
    );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Unknown', 'ninja-forms' );

        $this->_settings[ 'message' ] = array(
            'name' => 'message',
            'type' => 'html',
            'label' => '',
            'width' => 'full',
            'group' => 'primary',
        );

        $this->_settings[ 'label' ][ 'group' ] = '';

        unset( $this->_settings[ 'default' ] ); // TODO: Seeing an error when removing default form the $_settings_only property, so just unsetting it here for now.

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    public function validate( $field, $data )
    {
        return array(); // Return empty array with no errors.
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;
        return $field_types;
    }

    public static function create( $field )
    {
        $unknown = Ninja_Forms()->form()->field()->get();
        if( is_object( $field ) ){
            $unknown->update_settings(array(
                'id'      => $field->get_id(),
                'label'   => $field->get_setting( 'label' ),
                'order'   => $field->get_setting( 'order' ),
                'key'     => $field->get_setting( 'key' ),
                'type'    => 'unknown',
                'message' => sprintf( __( 'Field type "%s" not found.', 'ninja-forms' ), $field->get_setting( 'type' ) )
            ));
        } elseif( isset( $field[ 'settings' ] ) ){
            $unknown->update_settings(array(
                'id'      => $field[ 'id' ],
                'label'   => $field[ 'settings' ][ 'label' ],
                'order'   => $field[ 'settings' ][ 'order' ],
                'key'     => $field[ 'settings' ][ 'key' ],
                'type'    => 'unknown',
                'message' => sprintf( __( 'Field type "%s" not found.', 'ninja-forms' ), $field[ 'settings' ][ 'type' ] )
            ));
        } else {
            $unknown->update_settings(array(
                'id'      => $field[ 'id' ],
                'label'   => $field[ 'label' ],
                'order'   => $field[ 'order' ],
                'key'     => $field[ 'key' ],
                'type'    => 'unknown',
                'message' => sprintf( __( 'Field type "%s" not found.', 'ninja-forms' ), $field[ 'type' ] )
            ));
        }
        return $unknown;
    }
}
