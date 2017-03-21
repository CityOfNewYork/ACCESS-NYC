<?php if ( ! defined( 'ABSPATH' ) ) exit;

abstract class NF_Abstracts_Controller
{
    /**
     * Data (Misc.) passed back to the client in the Response.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Errors passed back to the client in the Response.
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * Debug Messages passed back to the client in the Response.
     *
     * @var array
     */
    protected $_debug = array();

    /*
     * PUBLIC METHODS
     */

    /**
     * NF_Abstracts_Controller constructor.
     */
    public function __construct()
    {
        //This section intentionally left blank.
    }


    /*
     * PROTECTED METHODS
     */

    /**
     * Respond
     *
     * A wrapper for the WordPress AJAX response pattern.
     */
    protected function _respond( $data = array() )
    {
        if( empty( $data ) ){
            $data = $this->_data;
        }

        if( isset( $this->_data['debug'] ) ) {
            $this->_debug = array_merge( $this->_debug, $this->_data[ 'debug' ] );
        }

        if( isset( $this->_data['errors'] ) && $this->_data[ 'errors' ] ) {
            $this->_errors = array_merge( $this->_errors, $this->_data[ 'errors' ] );
        }

        $response = array( 'data' => $data, 'errors' => $this->_errors, 'debug' => $this->_debug );

        echo wp_json_encode( $response );

        wp_die(); // this is required to terminate immediately and return a proper response
    }
}