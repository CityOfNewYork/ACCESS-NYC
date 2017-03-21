<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Field_Total
 */
class NF_Fields_Total extends NF_Abstracts_Input
{
    protected $_name = 'total';

    protected $_section = 'pricing';

    protected $_icon = 'money';

    protected $_aliases = array();

    protected $_type = 'total';

    protected $_templates = 'total';

    protected $_test_value = '0.00';

    protected $_settings_exclude = array( 'placeholder', 'default', 'input_limit_set', 'disable_input', 'required' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Total', 'ninja-forms' );
    }

    public function process( $total, $data )
    {
        $subtotal = 0;

        foreach( $data[ 'fields' ] as $key => $field ){

            if( isset ( $field[ 'type' ] ) && 'shipping' == $field[ 'type' ] ){
                $subtotal += $field[ 'shipping_cost' ];
            }
        }

        if( isset( $data[ 'product_totals' ] ) ){

            foreach( $data[ 'product_totals' ] as $product_total ){

                $subtotal += $product_total;
            }
        }

        $data[ 'new_total' ] = number_format( $subtotal, 2 );

        return $data;
    }
}
