<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CreditCard
 */
class NF_Fields_CreditCard extends NF_Abstracts_Field
{
    protected $_name = 'creditcard';

    protected $_type = 'creditcard';

    protected $_section = 'pricing';

    protected $_icon = 'credit-card';

    protected $_templates = '';

    protected $_test_value = '';

    protected $_settings_exclude = array( 'input_limit_set', 'disable_input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Credit Card', 'ninja-forms' );
    }
}
