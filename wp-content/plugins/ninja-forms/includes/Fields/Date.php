<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Date
 */
class NF_Fields_Date extends NF_Fields_Textbox
{
    protected $_name = 'date';

    protected $_nicename = 'Date';

    protected $_section = 'common';

    protected $_icon = 'calendar';

    protected $_type = 'date';

    protected $_templates = 'date';

    protected $_test_value = '12/12/2022';

    protected $_settings = array( 'date_default', 'date_format', 'year_range' );

    protected $_settings_exclude = array( 'default', 'placeholder', 'input_limit_set', 'disable_input' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Date', 'ninja-forms' );
    }

    public function process( $field, $data )
    {
        return $data;
    }

    private function get_format( $format )
    {
        $lookup = array(
            'MM/DD/YYYY' => __( 'm/d/Y', 'ninja-forms' ),
            'MM-DD-YYYY' => __( 'm-d-Y', 'ninja-forms' ),
            'MM.DD.YYYY' => __( 'm.d.Y', 'ninja-forms' ),
            'DD/MM/YYYY' => __( 'm/d/Y', 'ninja-forms' ),
            'DD-MM-YYYY' => __( 'd-m-Y', 'ninja-forms' ),
            'DD.MM.YYYY' => __( 'd.m.Y', 'ninja-forms' ),
            'YYYY-MM-DD' => __( 'Y-m-d', 'ninja-forms' ),
            'YYYY/MM/DD' => __( 'Y/m/d', 'ninja-forms' ),
            'YYYY.MM.DD' => __( 'Y.m.d', 'ninja-forms' ),
            'dddd, MMMM D YYYY' => __( 'l, F d Y', 'ninja-forms' ),
            
            
            
            
        );

        return ( isset( $lookup[ $format ] ) ) ? $lookup[ $format ] : $format;
    }

}
