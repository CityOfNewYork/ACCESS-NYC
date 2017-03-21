<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_FirstName
 */
class NF_Fields_LastName extends NF_Abstracts_UserInfo
{
    protected $_name = 'lastname';
    protected $_type = 'lastname';

    protected $_nicename = 'Last Name';

    protected $_section = 'userinfo';

    protected $_icon = 'user';

    protected $_templates = 'lastname';

    protected $_test_value = 'Doe';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Last Name', 'ninja-forms' );
    }

    public function filter_default_value( $default_value, $field_class, $settings )
    {
        if( ! isset( $settings[ 'default_type' ] ) ||
            'user-meta' != $settings[ 'default_type' ] ||
            $this->_name != $field_class->get_name()) return $default_value;

        $current_user = wp_get_current_user();

        if( $current_user ){
            $default_value = $current_user->user_lastname;
        }

        return $default_value;
    }
}
