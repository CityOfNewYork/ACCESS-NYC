<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_ImportExport extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $menu_slug = 'nf-import-export';

    public function __construct()
    {
        add_action( 'init', array( $this, 'import_form_listener' ), 0 );
        add_action( 'init', array( $this, 'export_form_listener' ), 0 );

        add_action( 'init', array( $this, 'import_fields_listener' ), 0 );
        add_action( 'init', array( $this, 'export_fields_listener' ), 0 );

        add_filter( 'ninja_forms_before_import_fields', array( $this, 'import_fields_backwards_compatibility' ) );

        parent::__construct();
    }

    public function get_page_title()
    {
        return __( 'Import / Export', 'ninja-forms' );
    }

    public function import_form_listener()
    {
        $capability = apply_filters( 'ninja_forms_admin_import_export_capabilities', 'manage_options' );
        $capability = apply_filters( 'ninja_forms_admin_import_form_capabilities',   $capability      );
        if( ! current_user_can( $capability ) ) return;

        if( ! isset( $_FILES[ 'nf_import_form' ] ) || ! $_FILES[ 'nf_import_form' ] ) return;

        $this->upload_error_check( $_FILES[ 'nf_import_form' ] );

        $data = file_get_contents( $_FILES[ 'nf_import_form' ][ 'tmp_name' ] );

        $import = Ninja_Forms()->form()->import_form( $data );

        if( ! $import ){

            wp_die(
                __( 'There uploaded file is not a valid format.', 'ninja-forms' ) . ' ' . ( function_exists( 'json_last_error' ) ) ? json_last_error_msg() : '',
                __( 'Invalid Form Upload.', 'ninja-forms' )
            );
        }
    }

    public function export_form_listener()
    {
        $capability = apply_filters( 'ninja_forms_admin_import_export_capabilities', 'manage_options' );
        $capability = apply_filters( 'ninja_forms_admin_export_form_capabilities',   $capability      );
        if( ! current_user_can( $capability ) ) return;

        if( isset( $_REQUEST[ 'nf_export_form' ] ) && $_REQUEST[ 'nf_export_form' ] ){
            $form_id = $_REQUEST[ 'nf_export_form' ];
            Ninja_Forms()->form( $form_id )->export_form();
        }
    }

    public function import_fields_listener()
    {
        if( ! current_user_can( apply_filters( 'ninja_forms_admin_import_fields_capabilities', 'manage_options' ) ) ) return;

        if( ! isset( $_FILES[ 'nf_import_fields' ] ) || ! $_FILES[ 'nf_import_fields' ] ) return;

        $this->upload_error_check( $_FILES[ 'nf_import_fields' ] );

        $import = file_get_contents( $_FILES[ 'nf_import_fields' ][ 'tmp_name' ] );

        $fields = unserialize( $import );

        foreach( $fields as $settings ){
            Ninja_Forms()->form()->import_field( $settings );
        }
    }

    public function export_fields_listener()
    {
        if( ! current_user_can( apply_filters( 'ninja_forms_admin_export_fields_capabilities', 'manage_options' ) ) ) return;

        if( isset( $_REQUEST[ 'nf_export_fields' ] ) && $_REQUEST[ 'nf_export_fields' ] ){
            $field_ids = $_REQUEST[ 'nf_export_fields' ];

            $fields = array();
            foreach( $field_ids as $field_id ){
                $field = Ninja_Forms()->form()->field( $field_id )->get();

                $fields[] = $field->get_settings();
            }

            header("Content-type: application/csv");
            header("Content-Disposition: attachment; filename=favorites-" . time() . ".nff");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo serialize( $fields );

            die();
        }
    }


    public function display()
    {
        $tabs = apply_filters( 'ninja_forms_import_export_tabs', array(
            'forms' => __( 'Form', 'ninja-forms' ),
            'favorite_fields' => __( 'Favorite Fields', 'ninja-forms' )
            )
        );

        $tab_keys = array_keys( $tabs );
        $active_tab = ( isset( $_GET[ 'tab' ] ) ) ? $_GET[ 'tab' ] : reset( $tab_keys );

        $this->add_meta_boxes();

        wp_enqueue_script('postbox');
        wp_enqueue_script('jquery-ui-draggable');

        wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
        wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );

        Ninja_Forms::template( 'admin-menu-import-export.html.php', compact( 'tabs', 'active_tab' ) );
    }

    public function add_meta_boxes()
    {
        /*
         * Forms
         */
        add_meta_box(
            'nf_import_export_forms_import',
            __( 'Import Forms', 'ninja-forms' ),
            array( $this, 'template_import_forms' ),
            'nf_import_export_forms'
        );

        add_meta_box(
            'nf_import_export_forms_export',
            __( 'Export Forms', 'ninja-forms' ),
            array( $this, 'template_export_forms' ),
            'nf_import_export_forms'
        );

        /*
         * FAVORITE FIELDS
         */
        add_meta_box(
            'nf_import_export_favorite_fields_import',
            __( 'Import Favorite Fields', 'ninja-forms' ),
            array( $this, 'template_import_favorite_fields' ),
            'nf_import_export_favorite_fields'
        );

        add_meta_box(
            'nf_import_export_favorite_fields_export',
            __( 'Export Favorite Fields', 'ninja-forms' ),
            array( $this, 'template_export_favorite_fields' ),
            'nf_import_export_favorite_fields'
        );
    }

    public function template_import_forms()
    {
        Ninja_Forms::template( 'admin-metabox-import-export-forms-import.html.php' );
    }

    public function template_export_forms()
    {
        $forms = Ninja_Forms()->form()->get_forms();
        Ninja_Forms::template( 'admin-metabox-import-export-forms-export.html.php', compact( 'forms' ) );
    }

    public function template_import_favorite_fields()
    {
        Ninja_Forms::template( 'admin-metabox-import-export-favorite-fields-import.html.php' );
    }

    public function template_export_favorite_fields()
    {
        $fields = Ninja_Forms()->form()->get_fields( array( 'saved' => 1) );
        Ninja_Forms::template( 'admin-metabox-import-export-favorite-fields-export.html.php', compact( 'fields' ) );
    }

    /*
    |--------------------------------------------------------------------------
    | Backwards Compatibility
    |--------------------------------------------------------------------------
    */

    public function import_fields_backwards_compatibility( $field )
    {
        //TODO: This was copied over. Instead need to abstract backwards compatibility for re-use.
        // Flatten field settings array
        if( isset( $field[ 'data' ] ) ){
            $field = array_merge( $field, $field[ 'data' ] );
            unset( $field[ 'data' ] );
        }

        // Drop form_id in favor of parent_id, which is set by the form.
        if( isset( $field[ 'form_id' ] ) ){
            unset( $field[ 'form_id' ] );
        }

        // Remove `_` prefix from type setting
        $field[ 'type' ] = ltrim( $field[ 'type' ], '_' );

        // Type: `text` -> `textbox`
        if( 'text' == $field[ 'type' ] ){
            $field[ 'type' ] = 'textbox';
        }

        if( 'submit' == $field[ 'type' ] ){
            $field[ 'processing_label' ] = 'Processing';
        }

        if( 'calc' == $field[ 'type' ] ){
            $field[ 'type' ] = 'note';

            if( isset( $field[ 'calc_method' ] ) ) {

                switch( $field[ 'calc_method' ] ){
                    case 'eq':
                        $method = __( 'Equation (Advanced)', 'ninja-forms' );
                        break;
                    case 'fields':
                        $method = __( 'Operations and Fields (Advanced)', 'ninja-forms' );
                        break;
                    case 'auto':
                        $method = __( 'Auto-Total Fields', 'ninja-forms' );
                        break;
                    default:
                        $method = '';
                }
                $field['default'] = $method . "\r\n";

                if ('eq' == $field['calc_method'] && isset( $field['calc_eq'] ) ) {
                    $field['default'] .= $field['calc_eq'];
                }

                if ('fields' == $field['calc_method'] && isset( $field['calc'] ) ) {
                    // TODO: Support 'operations and fields (advanced)' calculations.
                }

                if ('auto' == $field['calc_method'] && isset( $field['calc'] ) ) {
                    // TODO: Support 'auto-totaling' calculations.
                }
            }

            unset( $field[ 'calc' ] );
            unset( $field[ 'calc_eq' ] );
            unset( $field[ 'calc_method' ] );
        }

        if( isset( $field[ 'email' ] ) ){

            if( 'textbox' == $field[ 'type' ] && $field[ 'email' ] ) {
                $field['type'] = 'email';
            }
            unset( $field[ 'email' ] );
        }

        if( isset( $field[ 'class' ] ) ){
            $field[ 'element_class' ] = $field[ 'class' ];
            unset( $field[ 'class' ] );
        }

        if( isset( $field[ 'req' ] ) ){
            $field[ 'required' ] = $field[ 'req' ];
            unset( $field[ 'req' ] );
        }

        if( isset( $field[ 'default_value_type' ] ) ){

            /* User Data */
            if( '_user_id' == $field[ 'default_value_type' ] )           $field[ 'default' ] = '{user:id}';
            if( '_user_email' == $field[ 'default_value_type' ] )        $field[ 'default' ] = '{user:email}';
            if( '_user_lastname' == $field[ 'default_value_type' ] )     $field[ 'default' ] = '{user:last_name}';
            if( '_user_firstname' == $field[ 'default_value_type' ] )    $field[ 'default' ] = '{user:first_name}';
            if( '_user_display_name' == $field[ 'default_value_type' ] ) $field[ 'default' ] = '{user:display_name}';

            /* Post Data */
            if( 'post_id' == $field[ 'default_value_type' ] )    $field[ 'default' ] = '{post:id}';
            if( 'post_url' == $field[ 'default_value_type' ] )   $field[ 'default' ] = '{post:url}';
            if( 'post_title' == $field[ 'default_value_type' ] ) $field[ 'default' ] = '{post:title}';

            /* System Data */
            if( 'today' == $field[ 'default_value_type' ] ) $field[ 'default' ] = '{system:date}';

            /* Miscellaneous */
            if( '_custom' == $field[ 'default_value_type' ] && isset( $field[ 'default_value' ] ) ){
                $field[ 'default' ] = $field[ 'default_value' ];
            }
            if( 'querystring' == $field[ 'default_value_type' ] && isset( $field[ 'default_value' ] ) ){
                $field[ 'default' ] = '{' . $field[ 'default_value' ] . '}';
            }

            unset( $field[ 'default_value' ] );
            unset( $field[ 'default_value_type' ] );
        }

        if( 'list' == $field[ 'type' ] ) {

            if ( isset( $field[ 'list_type' ] ) ) {

                if ('dropdown' == $field['list_type']) {
                    $field['type'] = 'listselect';
                }
                if ('radio' == $field['list_type']) {
                    $field['type'] = 'listradio';
                }
                if ('checkbox' == $field['list_type']) {
                    $field['type'] = 'listcheckbox';
                }
                if ('multi' == $field['list_type']) {
                    $field['type'] = 'listmultiselect';
                }
            }

            if( isset( $field[ 'list' ][ 'options' ] ) ) {
                $field[ 'options' ] = $field[ 'list' ][ 'options' ];
                unset( $field[ 'list' ][ 'options' ] );
            }
        }

        // Convert `textbox` to other field types
        foreach( array( 'fist_name', 'last_name', 'user_zip', 'user_city', 'user_phone', 'user_email', 'user_address_1', 'user_address_2', 'datepicker' ) as $item ) {
            if ( isset( $field[ $item ] ) && $field[ $item ] ) {
                $field[ 'type' ] = str_replace( array( '_', 'user', '1', '2', 'picker' ), '', $item );

                unset( $field[ $item ] );
            }
        }

        if( 'timed_submit' == $field[ 'type' ] ) {
            $field[ 'type' ] = 'submit';
        }

        return $field;
    }

    private function upload_error_check( $file )
    {
        if( ! $file[ 'error' ] ) return;

        switch ( $file[ 'error' ] ) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = __( 'The uploaded file was only partially uploaded.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = __( 'No file was uploaded.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = __( 'Missing a temporary folder.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = __( 'Failed to write file to disk.', 'ninja-forms' );
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = __( 'File upload stopped by extension.', 'ninja-forms' );
                break;
            default:
                $error_message = __( 'Unknown upload error.', 'ninja-forms' );
                break;
        }

        $args = array(
            'title' => __( 'File Upload Error', 'ninja-forms' ),
            'message' => $error_message,
            'debug' => $file,
        );
        $message = Ninja_Forms()->template( 'admin-wp-die.html.php', $args );
        wp_die( $message, $args[ 'title' ], array( 'back_link' => TRUE ) );
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_import_export_capabilities', $this->capability );
    }
}
