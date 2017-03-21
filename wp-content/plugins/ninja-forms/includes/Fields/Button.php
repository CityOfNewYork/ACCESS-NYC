<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Button
 */
class NF_Fields_Button extends NF_Abstracts_Field
{
    protected $_name = 'button';

    protected $_nicename = 'Button';

    protected $_section = '';

    protected $_type = 'button';

    protected $_templates = 'button';

    protected $_settings_only = array( 'label' );

    public function __construct()
    {
        parent::__construct();

        $this->_settings[ 'label' ][ 'width' ] = 'full';

        $this->_nicename = __( 'Button', 'ninja-forms' );
    }
}
