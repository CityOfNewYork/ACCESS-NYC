<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Hidden
 */
class NF_Fields_Hidden extends NF_Abstracts_Input
{
    protected $_name = 'hidden';

    protected $_nicename = 'Hidden';

    protected $_section = 'misc';

    protected $_icon = 'eye-slash';

    protected $_type = 'hidden';

    protected $_templates = 'hidden';

    protected $_wrap_template = 'wrap-no-label';

    protected $_settings_only = array(
        'key', 'label', 'default', 'admin_label'
    );

    protected $_use_merge_tags_include = array( 'calculations' );

    public function __construct()
    {
        parent::__construct();

        $use_merge_tags = array( 'include' => array( 'calculations' ) );

        $this->_settings[ 'label' ][ 'width' ] = 'full';
        $this->_settings[ 'default' ][ 'group' ] = 'primary';
        $this->_settings[ 'default' ][ 'user_merge_tags' ] = $use_merge_tags;

        $this->_nicename = __( 'Hidden', 'ninja-forms' );
    }
}
