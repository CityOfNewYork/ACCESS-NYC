<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_Field
 */
abstract class NF_Abstracts_Field
{
    /**
    * @var string
    */
    protected $_name  = '';

    /**
     * @var string
     */
    protected $_nicename = '';

    /**
    * @var string
    */
    protected $_section = '';

    /**
    * @var string
    */
    protected $_icon = 'square-o';

    /**
     * @var array
     */
    protected $_aliases = array();

    /**
     * @var array
     */
    protected $_settings = array();

    /**
     * @var array
     */
    protected $_settings_all_fields = array();

    /**
     * @var array
     */
    protected $_settings_exclude = array();

    /**
     * @var array
     */
    protected $_settings_only = array();

    /**
     * @var array
     */
    protected $_use_merge_tags = array( 'user', 'post', 'system', 'fields' );

    /**
     * @var array
     */
    protected $_use_merge_tags_include = array();

    /**
     * @var array
     */
    protected $_use_merge_tags_exclude = array();

    /**
     * @var string
     */
    protected $_test_value = 'test';

    /**
     * @var string
     */
    protected $_attr = '';

    /**
     * @var string
     */
    protected $_type = '';

    /**
     * @var string
     */
    protected $_parent_type = '';

    /**
     * @var string
     */
    public static $_base_template = 'input';

    /**
     * @var array
     */
    protected $_templates = array();

    /**
     * @var string
     */
    protected $_wrap_template = 'wrap';

    /**
     * @var array
     */
    protected $_old_classname = '';

    //-----------------------------------------------------
    // Public Methods
    //-----------------------------------------------------

    /**
     * Constructor
     */
    public function __construct()
    {
        if( ! empty( $this->_settings_only ) ){

            $this->_settings = array_merge( $this->_settings, $this->_settings_only );
        } else {

            $this->_settings = array_merge( $this->_settings_all_fields, $this->_settings );
            $this->_settings = array_diff( $this->_settings, $this->_settings_exclude );
        }

        $this->_settings = $this->load_settings( $this->_settings );

        $this->_test_value = apply_filters( 'ninja_forms_field_' . $this->_name . '_test_value', $this->_test_value );

        add_filter( 'ninja_forms_localize_field_settings_' . $this->_type, array( $this, 'localize_settings' ), 10, 2 );
    }

    /**
     * Validate
     *
     * @param $field
     * @param $data
     * @return array $errors
     */
    public function validate( $field, $data )
    {
        $errors = array();
        // Required check.

        if( is_array( $field[ 'value' ] ) ){
            $field[ 'value' ] = implode( '', $field[ 'value' ] );
        }

        if( isset( $field['required'] ) && 1 == $field['required'] && is_null( trim( $field['value'] ) ) ){
            $errors[] = 'Field is required.';
        }
        return $errors;
    }

    public function process( $field, $data )
    {
        return $data;
    }

    /**
     * Admin Form Element
     *
     * Returns the output for editing fields in a submission.
     *
     * @param $id
     * @param $value
     * @return string
     */
    public function admin_form_element( $id, $value )
    {
        return "<input class='widefat' name='fields[$id]' value='$value' />";
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function get_nicename()
    {
        return $this->_nicename;
    }

    public function get_section()
    {
        return $this->_section;
    }

    public function get_icon()
    {
        return $this->_icon;
    }

    public function get_aliases()
    {
        return $this->_aliases;
    }

    public function get_type()
    {
        return $this->_type;
    }

    public function get_parent_type()
    {
        if( $this->_parent_type ){
            return $this->_parent_type;
        }
        // If a type is not set, return 'textbox'
        return ( get_parent_class() ) ? parent::$_type : 'textbox';
    }

    public function get_settings()
    {
        return $this->_settings;
    }

    public function use_merge_tags()
    {
        $use_merge_tags = array_merge( $this->_use_merge_tags, $this->_use_merge_tags_include );
        $use_merge_tags = array_diff( $use_merge_tags, $this->_use_merge_tags_exclude );

        return $use_merge_tags;
    }

    public function get_test_value()
    {
        return $this->_test_value;
    }

    public function get_templates()
    {
        $templates = (array) $this->_templates;

        // Create a reflection for examining the parent
        $reflection = new ReflectionClass( $this );
        $parent_class = $reflection->getParentClass();

        if ( $parent_class->isAbstract() ) {

            $parent_class_name = $parent_class->getName();
            $parent_templates = call_user_func( $parent_class_name . '::get_base_template' ); // Parent Class' Static Property
            return array_merge( $templates, (array) $parent_templates );
        }

        $parent_class_name = strtolower( str_replace('NF_Fields_', '', $parent_class->getName() ) );

        if( ! isset( Ninja_Forms()->fields[ $parent_class_name ] ) ) return $templates;

        $parent = Ninja_Forms()->fields[ $parent_class_name ];
        return array_merge($templates, $parent->get_templates());

    }

    public function get_wrap_template()
    {
        return $this->_wrap_template;
    }

    public function get_old_classname()
    {
        return $this->_old_classname;
    }

    protected function load_settings( $only_settings = array() )
    {
        $settings = array();

        // Loads a settings array from the FieldSettings configuration file.
        $all_settings = Ninja_Forms::config( 'FieldSettings' );

        foreach( $only_settings as $setting ){

            if( isset( $all_settings[ $setting ]) ){

                $settings[ $setting ] = $all_settings[ $setting ];
            }
        }

        return $settings = apply_filters( 'ninja_forms_field_load_settings', $settings, $this->_name, $this->get_parent_type() );
    }

    public static function get_base_template()
    {
        return self::$_base_template;
    }

    public static function sort_by_order( $a, $b )
    {
        return strcmp( $a->get_setting( 'order' ), $b->get_setting( 'order' ) );
    }

    public function localize_settings( $settings, $form_id ) {
        return $settings;
    }

}
