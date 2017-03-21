<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_MockData extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $page_title = 'Mock Data';

    public $priority = 9002;

    public function __construct()
    {
        if( ! defined( 'NF_DEV' ) || ! NF_DEV ) return;

        parent::__construct();

        if( isset( $_GET[ 'page' ] ) && 'nf-mock-data' == $_GET[ 'page' ] ) {
            add_action('admin_init', array($this, 'mock_and_redirect'));
        }
    }

    public function mock_and_redirect()
    {
        $this->mock_data();
        wp_redirect( admin_url( 'admin.php?page=ninja-forms' ) );
        exit;
    }

    public function display()
    {
        // Fallback if not redirected.
        $this->mock_data();
        echo '<div class="wrap">' . __( 'Migrations and Mock Data complete. ', 'ninja-forms' ) . '<a href="' .
            admin_url( "admin.php?page=ninja-forms" ) . '">' . __( 'View Forms', 'ninja-forms' ) . '</a></div>';
    }

    private function mock_data()
    {
        $mock_data = new NF_Database_MockData();

        $mock_data->saved_fields();
        $mock_data->form_blank_form();
        $mock_data->form_contact_form_1();
        $mock_data->form_contact_form_2();
        $mock_data->form_product_1();
        $mock_data->form_product_2();
        $mock_data->form_product_3();
        $mock_data->form_email_submission();
        $mock_data->form_long_form( 100 );
        $mock_data->form_long_form( 300 );
        $mock_data->form_long_form( 500 );
        $mock_data->form_kitchen_sink();
        $mock_data->form_bathroom_sink();
        $mock_data->form_calc_form();
    }

} // End Class NF_Admin_Settings
