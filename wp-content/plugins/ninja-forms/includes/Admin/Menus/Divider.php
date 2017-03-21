<?php if ( ! defined( 'ABSPATH' ) ) exit;

final class NF_Admin_Menus_Divider extends NF_Abstracts_Submenu
{
    public $parent_slug = 'ninja-forms';

    public $page_title = '';

    public $menu_title = '<span style="display:block;margin:1px 0 1px -5px;padding:0;height:1px;line-height:1px;background:#CCCCCC;"></span>';

    public $menu_slug = '#';

    public $priority = 9001;

    public function __construct()
    {
        if( ! defined( 'NF_DEV' ) || ! NF_DEV ) return;

        parent::__construct();

        // Reset Menu Slug
        $this->menu_slug = '#';
    }

    public function display()
    {
        // This method intentionally left blank.
    }

} // End Class NF_Admin_Divider
