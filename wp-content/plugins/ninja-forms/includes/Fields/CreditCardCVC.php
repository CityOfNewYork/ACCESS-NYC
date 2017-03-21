<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CreditCardCVC
 */
class NF_Fields_CreditCardCVC extends NF_Abstracts_Input
{
    protected $_name = 'creditcardcvc';
    protected $_type = 'creditcardcvc';

    protected $_section = '';

    protected $_icon = 'credit-card';

    protected $_templates = 'textbox';

    protected $_test_value = '1234';

    protected $_settings_exclude = array( 'input_limit_set', 'disable_input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Credit Card CVC', 'ninja-forms' );

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }
}
