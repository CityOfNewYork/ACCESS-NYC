<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Address2
 */
class NF_Fields_Address2 extends NF_Fields_Textbox
{
    protected $_name = 'address2';
    protected $type = 'address2';

    protected $_nicename = 'Address 2';

    protected $_icon = 'map-marker';

    protected $_section = '';

    protected $_templates = array( 'address2', 'address' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Address 2', 'ninja-forms' );
    }
}
