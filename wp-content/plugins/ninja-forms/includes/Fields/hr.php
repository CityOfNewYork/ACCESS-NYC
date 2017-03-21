<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Hr
 */
class NF_Fields_Hr extends NF_Abstracts_Input
{
    protected $_name = 'hr';

    protected $_section = 'layout';

    protected $_icon = 'arrows-h';

    protected $_aliases = array( 'html' );

    protected $_type = 'hr';

    protected $_templates = 'hr';

    protected $_settings_only = array( 'classes' );

    public function __construct()
    {
        parent::__construct();

        $this->_settings[ 'classes' ][ 'group' ] = 'primary';

        $this->_nicename = __( 'Divider', 'ninja-forms' );
        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );

        unset( $this->_settings[ 'classes' ][ 'settings' ][ 'wrapper '] );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }

}
