<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_AddNew extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $page_title = 'Add New';

    public $menu_slug = 'admin.php?page=ninja-forms&form_id=new';

    public $priority = 2;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_page_title()
    {
        return __( 'Add New', 'ninja-forms' );
    }

    public function get_capability()
    {
        return apply_filters( 'ninja_forms_admin_add_new_capabilities', $this->capability );
    }

    public function display()
    {
        // This section intentionally left blank.
    }

} // End Class NF_Admin_Settings
