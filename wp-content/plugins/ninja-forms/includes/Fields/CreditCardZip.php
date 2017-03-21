<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_CreditCardZip
 */
class NF_Fields_CreditCardZip extends NF_Fields_Zip
{
    protected $_name = 'creditcardzip';
    protected $_type = 'creditcardzip';

    protected $_section = '';

    protected $_icon = 'credit-card';

    protected $_templates = array( 'zip', 'textbox' );

    protected $_settings_exclude = array( 'disable_input', 'input_limit_set' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Credit Card Zip', 'ninja-forms' );

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }
}
