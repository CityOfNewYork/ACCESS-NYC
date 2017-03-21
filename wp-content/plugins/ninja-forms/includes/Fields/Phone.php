<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Phone
 */
class NF_Fields_Phone extends NF_Fields_Textbox
{
    protected $_name = 'phone';

    protected $_nicename = 'Phone';

    protected $_section = 'userinfo';

    protected $_icon = 'phone';

    protected $_type = 'textbox';

    protected $_templates = 'tel';

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Phone', 'ninja-forms' );
    }
}
