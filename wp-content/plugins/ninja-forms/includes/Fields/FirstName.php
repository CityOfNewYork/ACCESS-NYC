<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_FirstName
 */
class NF_Fields_FirstName extends NF_Abstracts_UserInfo
{
    protected $_name = 'firstname';
    protected $_type = 'firstname';

    protected $_nicename = 'First Name';

    protected $_section = 'userinfo';

    protected $_icon = 'user';

    protected $_templates = 'firstname';

    protected $_test_value = 'John';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'First Name', 'ninja-forms' );
    }

    public function filter_default_value( $default_value, $field_class, $settings )
    {
        if( ! isset( $settings[ 'default_type' ] ) ||
            'user-meta' != $settings[ 'default_type' ] ||
            $this->_name != $field_class->get_name()) return $default_value;

        $current_user = wp_get_current_user();

        if( $current_user ){
            $default_value = $current_user->user_firstname;
        }

        return $default_value;
    }
}
