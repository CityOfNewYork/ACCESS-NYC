<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Action_Email
 */
final class NF_Actions_Email extends NF_Abstracts_Action
{
    /**
    * @var string
    */
    protected $_name  = 'email';

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

        $this->_nicename = __( 'Email', 'ninja-forms' );

        $settings = Ninja_Forms::config( 'ActionEmailSettings' );

        $this->_settings = array_merge( $this->_settings, $settings );

        $this->_backwards_compatibility();
    }

    /*
    * PUBLIC METHODS
    */

    public function process( $action_settings, $form_id, $data )
    {
        $headers = $this->_get_headers( $action_settings );

        $attachments = $this->_get_attachments( $action_settings, $data );

        if( 'html' == $action_settings[ 'email_format' ] ) {
            $message = $action_settings['email_message'];
        } else {
            $message = $this->format_plain_text_message( $action_settings[ 'email_message_plain' ] );
        }

        $message = apply_filters( 'ninja_forms_action_email_message', $message, $data, $action_settings );

        $sent = wp_mail(
            $action_settings['to'],
            $action_settings['email_subject'],
            $message,
            $headers,
            $attachments
        );

        $data[ 'actions' ][ 'email' ][ 'to' ] = $action_settings['to'];
        $data[ 'actions' ][ 'email' ][ 'sent' ] = $sent;
        $data[ 'actions' ][ 'email' ][ 'headers' ] = $headers;
        $data[ 'actions' ][ 'email' ][ 'attachments' ] = $attachments;

        return $data;
    }

    private function _get_headers( $settings )
    {
        $headers = array();

        $headers[] = 'Content-Type: text/' . $settings[ 'email_format' ];
        $headers[] = 'charset=UTF-8';

        $headers[] = $this->_format_from( $settings );

        $headers = array_merge( $headers, $this->_format_recipients( $settings ) );

        return $headers;
    }

    private function _get_attachments( $settings, $data )
    {
        $attachments = array();

        if( 1 == $settings[ 'attach_csv' ] ){
            $attachments[] = $this->_create_csv( $data[ 'fields' ] );
        }

        if( ! isset( $settings[ 'id' ] ) ) $settings[ 'id' ] = '';

        $attachments = apply_filters( 'ninja_forms_action_email_attachments', $attachments, $data, $settings );

        return $attachments;
    }

    private function _format_from( $settings )
    {
        $from_name = get_bloginfo( 'name', 'raw' );
        $from_name = apply_filters( 'ninja_forms_action_email_from_name', $from_name );
        $from_name = ( $settings[ 'from_name' ] ) ? $settings[ 'from_name' ] : $from_name;

        $from_address = get_bloginfo( 'admin_email' );
        $from_address = apply_filters( 'ninja_forms_action_email_from_address', $from_address );
        $from_address = ( $settings[ 'from_address' ] ) ? $settings[ 'from_address' ] : $from_address;

        return $this->_format_recipient( 'from', $from_address, $from_name );
    }

    private function _format_recipients( $settings )
    {
        $headers = array();

        $recipient_settings = array(
            'Cc' => $settings[ 'cc' ],
            'Bcc' => $settings[ 'bcc' ],
            'Reply-to' => $settings[ 'reply_to' ],
        );

        foreach( $recipient_settings as $type => $emails ){

            $emails = explode( ',', $emails );

            foreach( $emails as $email ) {

                if( ! $email ) continue;

                $headers[] = $this->_format_recipient($type, $email);
            }
        }

        return $headers;
    }

    private function _format_recipient( $type, $email, $name = '' )
    {
        $type = ucfirst( $type );

        if( ! $name ) $name = $email;

        $recipient = "$type: $name <$email>";

        return $recipient;
    }

    private function _create_csv( $fields )
    {
        $csv_array = array();

        foreach( $fields as $field ){

            if( ! isset( $field[ 'label' ] ) ) continue;

            $csv_array[ 0 ][] = $field[ 'label' ];
            $csv_array[ 1 ][] = WPN_Helper::stripslashes( $field[ 'value' ] );
        }

        $csv_content = WPN_Helper::str_putcsv( $csv_array,
            apply_filters( 'ninja_forms_sub_csv_delimiter', ',' ),
            apply_filters( 'ninja_forms_sub_csv_enclosure', '"' ),
            apply_filters( 'ninja_forms_sub_csv_terminator', "\n" )
        );

        $upload_dir = wp_upload_dir();
        $path = trailingslashit( $upload_dir['path'] );

        // create temporary file
        $path = tempnam( $path, 'Sub' );
        $temp_file = fopen( $path, 'r+' );

        // write to temp file
        fwrite( $temp_file, $csv_content );
        fclose( $temp_file );

        // find the directory we will be using for the final file
        $path = pathinfo( $path );
        $dir = $path['dirname'];
        $basename = $path['basename'];

        // create name for file
        $new_name = apply_filters( 'ninja_forms_submission_csv_name', 'ninja-forms-submission' );

        // remove a file if it already exists
        if( file_exists( $dir.'/'.$new_name.'.csv' ) ) {
            unlink( $dir.'/'.$new_name.'.csv' );
        }

        // move file
        rename( $dir.'/'.$basename, $dir.'/'.$new_name.'.csv' );
        return $dir.'/'.$new_name.'.csv';
    }

    /*
     * Backwards Compatibility
     */

    private function _backwards_compatibility()
    {
        add_filter( 'ninja_forms_sub_csv_delimiter',        array( $this, 'ninja_forms_sub_csv_delimiter'        ), 10, 1 );
        add_filter( 'ninja_sub_csv_enclosure',              array( $this, 'ninja_sub_csv_enclosure'              ), 10, 1 );
        add_filter( 'ninja_sub_csv_terminator',             array( $this, 'ninja_sub_csv_terminator'             ), 10, 1 );
        add_filter( 'ninja_forms_action_email_attachments', array( $this, 'ninja_forms_action_email_attachments' ), 10, 3 );
    }

    public function ninja_forms_sub_csv_delimiter( $delimiter )
    {
        return apply_filters( 'nf_sub_csv_delimiter', $delimiter );
    }

    public function ninja_sub_csv_enclosure( $enclosure )
    {
        return apply_filters( 'nf_sub_csv_enclosure', $enclosure );
    }

    public function ninja_sub_csv_terminator( $terminator )
    {
        return apply_filters( 'nf_sub_csv_terminator', $terminator );
    }

    public function ninja_forms_action_email_attachments( $attachments, $form_data, $action_settings )
    {
        return apply_filters( 'nf_email_notification_attachments', $attachments, $action_settings[ 'id' ] );
    }

    private function format_plain_text_message( $message )
    {
        $message =  str_replace( array( '<table>', '</table>', '<tr><td>', '' ), '', $message );
        $message =  str_replace( '</td><td>', ' ', $message );
        $message =  str_replace( '</td></tr>', "\r\n", $message );
        return strip_tags( $message );
    }
}
