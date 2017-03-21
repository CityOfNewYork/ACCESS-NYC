<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_Input
 */
abstract class NF_Abstracts_Input extends NF_Abstracts_Field
{
    protected $_name = 'input';

    protected $_section = 'common';

    protected $_type = 'text';

    protected $_settings_all_fields = array(
        'key', 'label', 'label_pos', 'required', 'placeholder', 'default', 'classes', 'input_limit_set' , 'manual_key', 'disable_input', 'admin_label', 'help', 'description'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function get_parent_type()
    {
        return parent::get_type();
    }
}
