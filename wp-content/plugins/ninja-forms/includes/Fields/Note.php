<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Note
 */
class NF_Fields_Note extends NF_Fields_Hidden
{
    protected $_name = 'note';

    protected $_type = 'note';

    protected $_nicename = 'Note';

    protected $_section = '';

    protected $_icon = 'sticky-note-o';

    protected $_templates = 'null';

    protected $_aliases = array( 'notes', 'info' );

    protected $_settings_only = array(
        'label', 'default'
    );

    public function __construct()
    {
        parent::__construct();

        $this->_settings[ 'value_mirror' ] = array(
            'name' => 'value_mirror',
            'type' => 'html',
            'label' => __( 'HTML', 'ninja-forms'),
            'width' => 'full',
            'group' => 'primary',
            'mirror' => 'default',
        );

        $this->_settings[ 'label' ][ 'width' ] = 'full';
        $this->_settings[ 'label' ][ 'group' ] = 'advanced';

        $this->_settings[ 'default' ][ 'type' ] = 'rte';
        $this->_settings[ 'default' ][ 'group' ] = 'advanced';

        $this->_settings[ 'value_mirror' ][ 'value' ] = $this->_settings[ 'default' ][ 'value' ] = __( 'Note text can be edited in the note field\'s advanced settings below.' );

        $this->_nicename = __( 'Note', 'ninja-forms' );

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;
        return $field_types;
    }
}
