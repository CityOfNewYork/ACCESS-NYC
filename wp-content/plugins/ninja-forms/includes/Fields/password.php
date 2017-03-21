<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Password
 */
class NF_Fields_Password extends NF_Abstracts_Input
{
    protected $_name = 'password';

    protected $_nicename = 'Password';

    protected $_section = '';

    protected $_type = 'password';

    protected $_templates = array( 'password', 'textbox', 'input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Password', 'ninja-forms' );
    }
}
