<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_StarRating
 */
class NF_Fields_StarRating extends NF_Abstracts_Input
{
    protected $_name = 'starrating';

    protected $_section = 'misc';

    protected $_icon = 'star-half-o';

    protected $_aliases = array( 'rating' );

    protected $_type = 'starrating';

    protected $_templates = 'starrating';

    protected $_settings_only = array( 'label', 'label_pos', 'default', 'required', 'classes' );

    public function __construct()
    {
        parent::__construct();

        $this->_settings[ 'default' ][ 'group' ] = 'primary';

        $this->_settings[ 'default' ][ 'label' ] = __( 'Number of Stars', 'ninja-forms' );

        $this->_settings[ 'default' ][ 'width' ] = 'one-half';

        $this->_settings[ 'default' ][ 'use_merge_tags' ] = FALSE;

        $this->_settings[ 'default' ][ 'value' ] = 5;

        $this->_nicename = __( 'Star Rating', 'ninja-forms' );
    }

}
