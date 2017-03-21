<?php if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_List_Table' ) ){

    if( file_exists( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ) ) {

        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    } else {

        //TODO: Load local wp-list-table-class.php
    }
}

class NF_Admin_AllFormsTable extends WP_List_Table
{
    /** Class constructor */
    public function __construct() {

        parent::__construct( array(
            'singular' => __( 'Form', 'ninja-forms' ), //singular name of the listed records
            'plural'   => __( 'Forms', 'ninja-forms' ), //plural name of the listed records
            'ajax'     => false //should this table support ajax?
        ) );
    }

    public function no_items() {
        _e( 'No forms found.', 'ninja-forms' );
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'title'     => __( 'Form Title', 'ninja-forms' ),
            'shortcode' => __( 'Shortcode', 'ninja-forms' ),
            'date'      => __( 'Date Created', 'ninja-forms' )
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array(
            'title' => array( __( 'title', 'ninja-forms' ),   TRUE ),
            'date'  => array( __( 'updated', 'ninja-forms' ), TRUE ),
        );
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();

        $forms = Ninja_Forms()->form()->get_forms();

        foreach( $forms as $form ){

             $data[] = array(
                 'id'        => $form->get_id(),
                 'title'     => $form->get_setting( 'title' ),
                 'shortcode' => apply_filters ( 'ninja_forms_form_list_shortcode','[ninja_form id=' . $form->get_id() . ']', $form->get_id() ),
                 'date'      => $form->get_setting( 'created_at' )
             );
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'title':
            case 'shortcode':
            case 'date':
                return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'id';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }

    function column_cb( $item )
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }

    function column_title( $item )
    {
        $title = $item[ 'title' ];
        $edit_url = add_query_arg( 'form_id', $item[ 'id' ], admin_url( 'admin.php?page=ninja-forms') );
        $delete_url = add_query_arg( array( 'action' => 'delete', 'id' => $item[ 'id' ], '_wpnonce' => wp_create_nonce( 'nf_delete_form' )));
        $duplicate_url = add_query_arg( array( 'action' => 'duplicate', 'id' => $item[ 'id' ], '_wpnonce' => wp_create_nonce( 'nf_duplicate_form' )));
        $preview_url = add_query_arg( 'nf_preview_form', $item[ 'id' ], site_url() );
        $submissions_url = add_query_arg( 'form_id', $item[ 'id' ], admin_url( 'edit.php?post_status=all&post_type=nf_sub') );

        $form = Ninja_Forms()->form( $item[ 'id' ] )->get();
        $locked = $form->get_setting( 'lock' );

        Ninja_Forms::template( 'admin-menu-all-forms-column-title.html.php', compact( 'title', 'edit_url', 'delete_url', 'duplicate_url', 'preview_url', 'submissions_url', 'locked' ) );
    }

    public function single_row( $item )
    {
        $form = Ninja_Forms()->form( $item[ 'id' ] )->get();
        echo '<tr>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = array(
            'bulk-delete' => __( 'Delete', 'ninja-forms' )
        );

        return $actions;
    }

    public static function process_bulk_action()
    {
        if( ! isset( $_GET[ 'page' ] ) || 'ninja-forms' != $_GET[ 'page' ] ) return;

        if ( isset( $_REQUEST[ 'action' ] ) && 'duplicate' === $_REQUEST[ 'action' ] ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'nf_duplicate_form' ) ) {
                die( __( 'Go get a life, script kiddies', 'ninja-forms' ) );
            }
            else {
                NF_Database_Models_Form::duplicate( absint( $_GET['id'] ) );
            }

            wp_redirect( admin_url( 'admin.php?page=ninja-forms' ) );
            exit;
        }

        if ( isset( $_REQUEST[ 'action' ] ) && 'delete' === $_REQUEST[ 'action' ] ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'nf_delete_form' ) ) {
                die( __( 'Go get a life, script kiddies', 'ninja-forms' ) );
            }
            else {
                self::delete_item( absint( $_GET['id'] ) );
            }

            wp_redirect( admin_url( 'admin.php?page=ninja-forms' ) );
            exit;
        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'bulk-forms' ) ) {
                die( __( 'Go get a life, script kiddies', 'ninja-forms' ) );
            }

            if( isset( $_POST[ 'bulk-delete' ] ) ) {
                $delete_ids = esc_sql($_POST['bulk-delete']);

                // loop over the array of record IDs and delete them
                foreach ($delete_ids as $id) {

                    self::delete_item(absint($id));
                }
            }

            wp_redirect( admin_url( 'admin.php?page=ninja-forms' ) );
            exit;
        }
    }

    public static function delete_item( $id )
    {
        $form = Ninja_Forms()->form( $id )->get();
        $form->delete();
    }

} // END CLASS NF_Admin_AllFormsTable
