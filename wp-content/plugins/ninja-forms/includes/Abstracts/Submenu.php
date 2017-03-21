<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WordPress Menu Page Base Class
 */
abstract class NF_Abstracts_Submenu
{
    /**
     * (required) The slug name for the parent menu (or the file name of a standard WordPress admin page)
     *
     * @var string
     */
    public $parent_slug = '';

    /**
     * (required) The text to be displayed in the title tags of the page when the menu is selected
     *
     * @var string
     */
    public $page_title = '';

    /**
     * (required) The on-screen name text for the menu
     *
     * @var string
     */
    public $menu_title = '';

    /**
     * (required) The capability required for this menu to be displayed to the user.
     *
     * @var string
     */
    public $capability = 'manage_options';

    /**
     * (required) The slug name to refer to this menu by (should be unique for this menu).
     *
     * @var string
     */
    public $menu_slug = '';

    /**
     * (optional) The function that displays the page content for the menu page.
     *
     * @var string
     */
    public $function = 'display';

    public $priority = 10;

    /**
     * Constructor
     *
     * Translate text and add the 'admin_menu' action.
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'register' ), $this->priority );
    }

    /**
     * Register the menu page.
     */
    public function register()
    {
        $function = ( $this->function ) ? array( $this, $this->function ) : NULL;

        add_submenu_page(
            $this->parent_slug,
            $this->get_page_title(),
            $this->get_menu_title(),
            apply_filters( 'ninja_forms_submenu_' . $this->get_menu_slug() . '_capability', $this->get_capability() ),
            $this->get_menu_slug(),
            $function
        );

        add_filter( 'admin_body_class', array( $this, 'body_class' ) );
    }

    public function body_class( $classes )
    {
        if( isset( $_GET['page'] ) && $_GET['page'] == $this->menu_slug ) {
            $classes = "$classes ninja-forms-app";
        }

        return $classes;
    }

    public function get_page_title()
    {
        return $this->page_title;
    }

    public function get_menu_title()
    {
        return ( $this->menu_title ) ? $this->menu_title : $this->get_page_title();
    }

    public function get_menu_slug()
    {
        return ( $this->menu_slug ) ? $this->menu_slug : 'nf-' . strtolower( preg_replace( '/[^A-Za-z0-9-]+/', '-', $this->get_menu_title() ) );
    }

    public function get_capability()
    {
        return $this->capability;
    }

    /**
     * Display the menu page.
     */
    public abstract function display();


}