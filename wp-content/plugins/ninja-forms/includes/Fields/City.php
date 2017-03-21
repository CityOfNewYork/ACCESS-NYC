<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_City
 */
class NF_Fields_City extends NF_Fields_Textbox
{
    protected $_name = 'city';
    protected $_type = 'city';

    protected $_nicename = 'City';

    protected $_section = 'userinfo';

    protected $_icon = 'map-marker';

    protected $_templates = 'city';

    protected $_test_value = 'Cleveland';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'City', 'ninja-forms' );
    }
}
