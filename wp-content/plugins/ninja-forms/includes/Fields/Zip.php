<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Zip
 */
class NF_Fields_Zip extends NF_Fields_Textbox
{
    protected $_name = 'zip';
    protected $_type = 'zip';

    protected $_nicename = 'Zip';

    protected $_section = 'userinfo';

    protected $_icon = 'map-marker';

    protected $_templates = array( 'zip', 'textbox', 'input' );

    protected $_test_value = '37312';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __('Zip', 'ninja-forms');
    }
}
