<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Address
 */
class NF_Fields_Address extends NF_Fields_Textbox
{
    protected $_name = 'address';
    protected $_type = 'address';

    protected $_nicename = 'Address';

    protected $_section = 'userinfo';

    protected $_icon = 'map-marker';

    protected $_templates = 'address';

    protected $_test_value = '123 Main Street';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Address', 'ninja-forms' );
    }
}
