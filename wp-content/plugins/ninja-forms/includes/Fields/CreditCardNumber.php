<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CreditCardNumber
 */
class NF_Fields_CreditCardNumber extends NF_Abstracts_Input
{
    protected $_name = 'creditcardnumber';
    protected $_type = 'creditcardnumber';

    protected $_section = '';

    protected $_icon = 'credit-card';

    protected $_templates = 'textbox';

    protected $_test_value = '4242424242424242';

    protected $_settings_exclude = array( 'default', 'input_limit_set', 'disable_input' );
    
    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Credit Card Number', 'ninja-forms' );

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }
}
