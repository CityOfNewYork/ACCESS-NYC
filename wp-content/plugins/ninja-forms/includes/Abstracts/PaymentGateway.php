<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_PaymentGateway
 */
abstract class NF_Abstracts_PaymentGateway
{
    protected $_slug = '';

    protected $_name = '';

    protected $_settings = array();

    public function __construct()
    {
        add_filter( 'ninja_forms_collect_payment_process', array( $this, '_process' ) );
    }

    public function get_slug()
    {
        return $this->_slug;
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function get_settings()
    {
        return $this->_settings;
    }

    public function _process( $action_settings, $form_id, $data )
    {
        if( $this->_slug == $action_settings[ 'payment_gateway' ] ){
            return $this->process( $action_settings, $form_id, $data );
        }
    }

    abstract protected function process( $action_settings, $form_id, $data );
}
