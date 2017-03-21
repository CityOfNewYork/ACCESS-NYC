<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Admin_CPT_Submission
 */
class NF_Admin_CPT_Submission
{
    protected $cpt_slug = 'nf_sub';

    public $screen_options;
    /**
     * Constructor
     */
    public function __construct()
    {
        // Register our submission custom post type.
        add_action( 'init', array( $this, 'custom_post_type' ), 5 );

        add_action( 'admin_print_styles', array( $this, 'enqueue_scripts' ) );

        // Filter Post Row Actions
        add_filter( 'post_row_actions', array( $this, 'post_row_actions' ) );

        // Change our submission columns.
        add_filter( 'manage_nf_sub_posts_columns', array( $this, 'change_columns' ) );

        // Add the appropriate data for our custom columns.
        add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );

        // Save our metabox values
        add_action( 'save_post', array( $this, 'save_nf_sub' ), 10, 2 );

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ) );

        // Filter our submission capabilities
        add_filter( 'user_has_cap', array( $this, 'cap_filter' ), 10, 3 );

        // Filter our hidden columns by form ID.
        add_action( 'wp', array( $this, 'filter_hidden_columns' ) );

        // Save our hidden columns by form id.
        add_action( 'wp_ajax_nf_hide_columns', array( $this, 'hide_columns' ) );
    }

    /**
     * Custom Post Type
     */
    function custom_post_type() {

        $labels = array(
            'name'                => _x( 'Submissions', 'Post Type General Name', 'ninja_forms' ),
            'singular_name'       => _x( 'Submission', 'Post Type Singular Name', 'ninja_forms' ),
            'menu_name'           => __( 'Submissions', 'ninja_forms' ),
            'name_admin_bar'      => __( 'Submissions', 'ninja_forms' ),
            'parent_item_colon'   => __( 'Parent Item:', 'ninja_forms' ),
            'all_items'           => __( 'All Items', 'ninja_forms' ),
            'add_new_item'        => __( 'Add New Item', 'ninja_forms' ),
            'add_new'             => __( 'Add New', 'ninja_forms' ),
            'new_item'            => __( 'New Item', 'ninja_forms' ),
            'edit_item'           => __( 'Edit Item', 'ninja_forms' ),
            'update_item'         => __( 'Update Item', 'ninja_forms' ),
            'view_item'           => __( 'View Item', 'ninja_forms' ),
            'search_items'        => __( 'Search Item', 'ninja_forms' ),
            'not_found'           => $this->not_found_message(),
            'not_found_in_trash'  => __( 'Not found in Trash', 'ninja_forms' ),
        );
        $args = array(
            'label'               => __( 'Submission', 'ninja_forms' ),
            'description'         => __( 'Form Submissions', 'ninja_forms' ),
            'labels'              => $labels,
            'supports'            => false,
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'menu_position'       => 5,
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => true,
            'capability_type' => 'nf_sub',
            'capabilities' => array(
                'publish_posts' => 'nf_sub',
                'edit_posts' => 'nf_sub',
                'edit_others_posts' => 'nf_sub',
                'delete_posts' => 'nf_sub',
                'delete_others_posts' => 'nf_sub',
                'read_private_posts' => 'nf_sub',
                'edit_post' => 'nf_sub',
                'delete_post' => 'nf_sub',
                'read_post' => 'nf_sub',
            ),
        );
        register_post_type( $this->cpt_slug, $args );
    }

    public function enqueue_scripts()
    {
        global $pagenow, $typenow;
        // Bail if we aren't on the edit.php page or we aren't editing our custom post type.
        if ( ( $pagenow != 'edit.php' && $pagenow != 'post.php' ) || $typenow != 'nf_sub' )
            return false;

        $form_id = isset ( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : '';

        wp_enqueue_script( 'subs-cpt',
            Ninja_Forms::$url . 'deprecated/assets/js/min/subs-cpt.min.js',
            array( 'jquery', 'jquery-ui-datepicker' ) );

        wp_localize_script( 'subs-cpt', 'nf_sub', array( 'form_id' => $form_id ) );
    }

    public function post_row_actions( $actions )
    {
        if( $this->cpt_slug == get_post_type() ){
            unset( $actions[ 'view' ] );
            unset( $actions[ 'inline hide-if-no-js' ] );
        }
        return $actions;
    }

    public function change_columns( $columns )
    {
        if( ! isset( $_GET[ 'form_id' ] ) ) return $columns;

        $form_id = absint( $_GET[ 'form_id' ] );

        $columns = array(
            'cb'    => '<input type="checkbox" />',
            'id' => __( '#', 'ninja-forms' ),
        );

        $form_cache = get_option( 'nf_form_' . $form_id );

        $form_fields = $form_cache[ 'fields' ];
        if( empty( $form_fields ) ) $form_fields = Ninja_Forms()->form( $form_id )->get_fields();

        foreach( $form_fields as $field ) {

            if( is_object( $field ) ) {
                $field = array(
                    'id' => $field->get_id(),
                    'settings' => $field->get_settings()
                );
            }

            $hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );
            if( in_array( $field[ 'settings' ][ 'type' ], array_values( $hidden_field_types ) ) ) continue;

            $id = $field[ 'id' ];
            $label = $field[ 'settings' ][ 'label' ];
            $columns[ $id ] = ( isset( $field[ 'settings' ][ 'admin_label' ] ) && $field[ 'settings' ][ 'admin_label' ] ) ? $field[ 'settings' ][ 'admin_label' ] : $label;
        }

        $columns['sub_date'] = __( 'Date', 'ninja-forms' );

        return $columns;
    }

    public function custom_columns( $column, $sub_id )
    {
        if( 'nf_sub' != get_post_type() ) {
            return;
        }

        $sub = Ninja_Forms()->form()->get_sub( $sub_id );

        if( 'id' == $column ) {
            echo apply_filters( 'nf_sub_table_seq_num', $sub->get_seq_num(), $sub_id, $column );
        }

        if( is_numeric( $column ) ){
            $value = $sub->get_field_value( $column );
            $field = Ninja_Forms()->form()->get_field( $column );
            echo apply_filters( 'ninja_forms_custom_columns', $value, $field, $sub_id );
        }

    }

    public function save_nf_sub( $nf_sub_id, $nf_sub )
    {
        global $pagenow;

        if ( ! isset ( $_POST['nf_edit_sub'] ) || $_POST['nf_edit_sub'] != 1 )
            return $nf_sub_id;

        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $nf_sub_id;

        if ( $pagenow != 'post.php' )
            return $nf_sub_id;

        if ( $nf_sub->post_type != 'nf_sub' )
            return $nf_sub_id;

        /* Get the post type object. */
        $post_type = get_post_type_object( $nf_sub->post_type );

        /* Check if the current user has permission to edit the post. */
        if ( !current_user_can( $post_type->cap->edit_post, $nf_sub_id ) )
            return $nf_sub_id;

        $sub = Ninja_Forms()->form()->sub( $nf_sub_id )->get();

        foreach ( $_POST['fields'] as $field_id => $user_value ) {
            $user_value = apply_filters( 'nf_edit_sub_user_value', $user_value, $field_id, $nf_sub_id );
            $sub->update_field_value( $field_id, $user_value );
        }

        $sub->save();
    }

    /**
     * Meta Boxes
     */
    public function add_meta_boxes( $post_type, $post )
    {
        add_meta_box(
            'nf_sub_fields',
            __( 'User Submitted Values', 'ninja-forms' ),
            array( $this, 'fields_meta_box' ),
            'nf_sub',
            'normal',
            'default'
        );

        add_meta_box(
            'nf_sub_info',
            __( 'Submission Info', 'ninja-forms' ),
            array( $this, 'info_meta_box' ),
            'nf_sub',
            'side',
            'default'
        );
    }

    /**
     * Fields Meta Box
     *
     * @param $post
     */
    public function fields_meta_box( $post )
    {
        $form_id = get_post_meta( $post->ID, '_form_id', TRUE );

        $sub = Ninja_Forms()->form()->get_sub( $post->ID );

        $fields = Ninja_Forms()->form( $form_id )->get_fields();

        usort( $fields, array( $this, 'sort_fields' ) );

        $hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );

        Ninja_Forms::template( 'admin-metabox-sub-fields.html.php', compact( 'fields', 'sub', 'hidden_field_types' ) );
    }

    public static function sort_fields( $a, $b )
    {
        if ( $a->get_setting( 'order' ) == $b->get_setting( 'order' ) ) {
            return 0;
        }
        return ( $a->get_setting( 'order' ) < $b->get_setting( 'order' ) ) ? -1 : 1;
    }

    /**
     * Info Meta Box
     *
     * @param $post
     */
    public function info_meta_box( $post )
    {
        $sub = Ninja_Forms()->form()->sub( $post->ID )->get();

        $seq_num = $sub->get_seq_num();

        $status = ucwords( $sub->get_status() );

        $user = apply_filters( 'nf_edit_sub_username', $sub->get_user()->data->user_nicename, $post->post_author );

        $form_title = $sub->get_form_title();

        $sub_date = apply_filters( 'nf_edit_sub_date_submitted', $sub->get_sub_date( 'm/d/Y H:i' ), $post->ID );

        $mod_date = apply_filters( 'nf_edit_sub_date_modified', $sub->get_mod_date( 'm/d/Y H:i' ), $post->ID );

        Ninja_Forms::template( 'admin-metabox-sub-info.html.php', compact( 'post', 'seq_num', 'status', 'user', 'form_title', 'sub_date', 'mod_date' ) );
    }

    /**
     * Remove Meta Boxes
     */
    public function remove_meta_boxes()
    {
        // Remove the default Publish metabox
        remove_meta_box( 'submitdiv', 'nf_sub', 'side' );
    }

    public function cap_filter( $allcaps, $cap, $args )
    {
        $sub_cap = apply_filters('ninja_forms_admin_submissions_capabilities', 'manage_options');
        if (!empty($allcaps[$sub_cap])) {
            $allcaps['nf_sub'] = true;
        }
        return $allcaps;
    }

    /**
     * Filter our hidden columns so that they are handled on a per-form basis.
     *
     * @access public
     * @since 2.7
     * @return void
     */
    public function filter_hidden_columns() {
        global $pagenow;
        // Bail if we aren't on the edit.php page, we aren't editing our custom post type, or we don't have a form_id set.
        if ( $pagenow != 'edit.php' || ! isset ( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] != 'nf_sub' || ! isset ( $_REQUEST['form_id'] ) )
            return false;
        // Grab our current user.
        $user = wp_get_current_user();
        // Grab our form id.
        $form_id = absint( $_REQUEST['form_id'] );
        // Get the columns that should be hidden for this form ID.
        $hidden_columns = get_user_option( 'manageedit-nf_subcolumnshidden-form-' . $form_id );
        if ( $hidden_columns === false ) {
            // If we don't have custom hidden columns set up for this form, then only show the first five columns.
            // Get our column headers
            $columns = get_column_headers( 'edit-nf_sub' );
            $hidden_columns = array();
            $x = 0;
            foreach ( $columns as $slug => $name ) {
                if ( $x > 5 ) {
                    if ( $slug != 'sub_date' )
                        $hidden_columns[] = $slug;
                }
                $x++;
            }
        }
        update_user_option( $user->ID, 'manageedit-nf_subcolumnshidden', $hidden_columns, true );
    }
    /**
     * Save our hidden columns per form id.
     *
     * @access public
     * @since 2.7
     * @return void
     */
    public function hide_columns() {
        // Grab our current user.
        $user = wp_get_current_user();
        // Grab our form id.
        $form_id = absint( $_REQUEST['form_id'] );
        $hidden = isset( $_POST['hidden'] ) ? explode( ',', esc_html( $_POST['hidden'] ) ) : array();
        $hidden = array_filter( $hidden );
        update_user_option( $user->ID, 'manageedit-nf_subcolumnshidden-form-' . $form_id, $hidden, true );
        die();
    }

    /*
     * PRIVATE METHODS
     */

    private function not_found_message()
    {
        if ( ! isset ( $_REQUEST['form_id'] ) || empty( $_REQUEST['form_id'] ) ) {
            return __( 'Please select a form to view submissions', 'ninja-forms' );
        } else {
            return __( 'No Submissions Found', 'ninja-forms' );
        }
    }

}

