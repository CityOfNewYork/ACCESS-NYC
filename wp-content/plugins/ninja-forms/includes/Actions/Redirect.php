<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Action_Redirect
 */
final class NF_Actions_Redirect extends NF_Abstracts_Action
{
    /**
    * @var string
    */
    protected $_name  = 'redirect';

    /**
    * @var array
    */
    protected $_tags = array();

    /**
    * @var string
    */
    protected $_timing = 'late';

    /**
    * @var int
    */
    protected $_priority = 10;

    /**
    * Constructor
    */
    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Redirect', 'ninja-forms' );

        $settings = Ninja_Forms::config( 'ActionRedirectSettings' );

        $this->_settings = array_merge( $this->_settings, $settings );
    }

    /*
    * PUBLIC METHODS
    */

    public function save( $action_settings )
    {

    }

    public function process( $action_settings, $form_id, $data )
    {
        $data[ 'actions' ][ 'redirect' ] = $action_settings[ 'redirect_url' ];

        return $data;
    }
}
