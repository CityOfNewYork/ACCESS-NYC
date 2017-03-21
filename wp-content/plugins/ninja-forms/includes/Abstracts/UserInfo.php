<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_UserInfo
 */
abstract class NF_Abstracts_UserInfo extends NF_Fields_Textbox
{
    protected $_name = 'input';

    protected $_section = 'userinfo';

    protected $_type = 'textbox';

    public static $_base_template = 'textbox';

    public function __construct()
    {
        parent::__construct();

        $this->_settings = $this->load_settings(
            array( 'key', 'label', 'label_pos', 'required', 'default', 'placeholder', 'classes', 'admin_label', 'help', 'description' )
        );

        $this->_settings[ 'default' ][ 'settings' ][ 'default_type' ][ 'options' ][] = array(
            'label'  => __( 'User Meta (if logged in)', 'ninja-forms' ),
            'value' => 'user-meta'
        );

        $this->_settings[ 'default' ][ 'settings' ][ 'default_type' ][ 'value' ] = 'user-meta';

        add_filter( 'ninja_forms_render_default_value', array( $this, 'filter_default_value' ), 10, 3 );
    }

    abstract public function filter_default_value( $default_value, $field_class, $settings );
}
