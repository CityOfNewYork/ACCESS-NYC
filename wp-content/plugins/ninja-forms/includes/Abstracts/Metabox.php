<?php if ( ! defined( 'ABSPATH' ) ) exit;

abstract class NF_Abstracts_Metabox
{
    protected $_id = ''; // Dynamically set in constructor using the class name.

    protected $_title = ''; // Should be set (and translated) in the constructor.

    protected $_callback = 'render_metabox';

    protected $_post_types = array();

    protected $_context = 'side';

    protected $_priority = 'default';

    protected $_callback_args = array();

    protected $_capability = 'edit_post';

    public function __construct()
    {
        $this->_id = strtolower( get_class( $this ) );

        $this->_title = __( 'Metabox', 'ninja-forms' );

        add_action( 'save_post', array( $this, '_save_post' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            $this->_id,
            $this->_title,
            array( $this, $this->_callback ),
            $this->_post_types,
            $this->_context,
            $this->_priority,
            $this->_callback_args
        );
    }

    abstract public function render_metabox( $post, $metabox );

    public function _save_post( $post_id )
    {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        $this->save_post( $post_id );
    }

    protected function save_post( $post_id )
    {
        // This section intentionally left blank.
    }
}