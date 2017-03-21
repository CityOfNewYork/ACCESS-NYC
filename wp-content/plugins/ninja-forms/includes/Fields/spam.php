<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Fields_Spam
 */
class NF_Fields_Spam extends NF_Abstracts_Input
{
    protected $_name = 'spam';

    protected $_type = 'spam';

    protected $_section = 'misc';

    protected $_icon = 'ban';

    protected $_templates = 'textbox';

    protected $_settings = array( 'spam_answer' );

    public function __construct()
    {
        parent::__construct();

        $this->_nicename = __( 'Anti-Spam', 'ninja-forms' );

        // Rename Label setting to Question
        $this->_settings[ 'label' ][ 'label' ] = __( 'Question', 'ninja-forms' );
        $this->_settings[ 'label_pos' ][ 'label' ] = __( 'Question Position', 'ninja-forms' );

        // Manually set Field Key and stop tracking.
        $this->_settings[ 'key' ][ 'value' ] = 'spam';
        $this->_settings[ 'manual_key' ][ 'value' ] = TRUE;


        // Default Required setting to TRUE and hide setting.
        $this->_settings[ 'required' ][ 'value' ] = 1;
        $this->_settings[ 'required' ][ 'group' ] = '';

        add_filter( 'nf_sub_hidden_field_types', array( $this, 'hide_field_type' ) );
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
        $errors = parent::validate( $field, $data );

        if(
            ( isset( $field[ 'spam_answer' ] ) && isset( $field[ 'value' ] ) )
            && ( $field[ 'spam_answer' ] != $field[ 'value' ] )
        ){
            $errors[] = __( 'Incorrect Answer', 'ninja-forms' );
        }

        return $errors;
    }

    public function get_parent_type()
    {
        return 'spam';
    }

    function hide_field_type( $field_types )
    {
        $field_types[] = $this->_name;

        return $field_types;
    }

}
