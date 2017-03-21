<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Email
 */
class NF_Fields_Email extends NF_Abstracts_UserInfo
{
    protected $_name = 'email';

    protected $_nicename = 'Email';

    protected $_type = 'email';

    protected $_section = 'userinfo';

    protected $_icon = 'envelope-o';

    protected $_templates = 'email';

    protected  $_test_value = 'foo@bar.dev';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Email', 'ninja-forms' );

    }

    public function filter_default_value( $default_value, $field_class, $settings )
    {
        if( ! isset( $settings[ 'default_type' ] ) ||
            'user-meta' != $settings[ 'default_type' ] ||
            $this->_name != $field_class->get_name()) return $default_value;

        $current_user = wp_get_current_user();

        if( $current_user ){
            $default_value = $current_user->user_email;
        }

        return $default_value;
    }
}
