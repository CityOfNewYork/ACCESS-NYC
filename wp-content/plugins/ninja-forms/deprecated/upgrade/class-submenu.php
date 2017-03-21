<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_THREE_Submenu
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
    public $page_title = 'Ninja Forms THREE';

    /**
     * (required) The on-screen name text for the menu
     *
     * @var string
     */
    public $menu_title = 'Ninja Forms THREE';

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
    public $menu_slug = 'ninja-forms-three';

    /**
     * (optional) The function that displays the page content for the menu page.
     *
     * @var string
     */
    public $function = 'display';

    public $priority = 9001;

    /**
     * Constructor
     *
     * Translate text and add the 'admin_menu' action.
     */
    public function __construct()
    {
        $this->menu_title = __( 'Update', 'ninja-forms' );
        $this->page_title = __( 'Update to Ninja Forms THREE', 'ninja-forms' );

        $this->capability = apply_filters( 'submenu_' . $this->menu_slug . '_capability', $this->capability );

        add_action( 'admin_menu', array( $this, 'register' ), $this->priority );

        add_action( 'wp_ajax_ninja_forms_upgrade_check', array( $this, 'upgrade_check' ) );

        add_filter( 'nf_general_settings_advanced', array( $this, 'settings_upgrade_button' ) );
    }

    /**
     * Register the menu page.
     */
    public function register()
    {
        if( ! ninja_forms_three_calc_check() ) return;
        if( ! ninja_forms_three_addons_version_check() ) return;

        if( ! ninja_forms_three_addons_check() ){
            // Hide the submenu
            $this->parent_slug = '';
        }

        $function = ( $this->function ) ? array( $this, $this->function ) : NULL;

        add_submenu_page(
            $this->parent_slug,
            $this->page_title,
            $this->menu_title,
            $this->capability,
            $this->menu_slug,
            $function
        );
    }

    /**
     * Display the menu page.
     */
    public function display()
    {
        $all_forms = Ninja_Forms()->forms()->get_all();

        wp_enqueue_style( 'ninja-forms-three-upgrade-styles', plugin_dir_url(__FILE__) . 'upgrade.css' );

        wp_enqueue_script( 'ninja-forms-three-upgrade', plugin_dir_url(__FILE__) . 'upgrade.js', array( 'jquery', 'wp-util' ), '', TRUE );
        wp_localize_script( 'ninja-forms-three-upgrade', 'nfThreeUpgrade', array(
            'forms' => $all_forms,
            'redirectURL' => admin_url( 'admin.php?page=ninja-forms&nf-switcher=upgrade' ),
        ) );

        include plugin_dir_path( __FILE__ ) . 'tmpl-submenu.html.php';
    }

    public function upgrade_check()
    {
        if( ! isset( $_POST[ 'formID' ] ) ) $this->respond( array( 'error' => 'Form ID not found.' ) );

        $form_id = absint( $_POST[ 'formID' ] );

        $can_upgrade = TRUE;

        $fields = Ninja_Forms()->form( $form_id )->fields;
        $settings = Ninja_Forms()->form( $form_id )->get_all_settings();

        foreach( $fields as $field ){
            if( '_calc' == $field[ 'type' ] ){
                // $can_upgrade = FALSE;
            }
        }

        $this->respond( array(
            'id' => $form_id,
            'title' => $settings[ 'form_title' ],
            'canUpgrade' => $can_upgrade
        ) );
    }

    private function respond( $response =  array() )
    {
        echo wp_json_encode( $response );
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    public function settings_upgrade_button( $settings )
    {
        $settings['update_to_three'] = array(
            'name' => 'update_to_three',
            'type' => '',
            'label' => __('Ninja Forms THREE', 'ninja-forms'),
            'display_function' => array($this, 'settings_upgrade_button_display'),
            'desc' => __('Upgrade to the Ninja Forms THREE.', 'ninja-forms')
        );

        return $settings;
    }

    public function settings_upgrade_button_display()
    {
        include plugin_dir_path( __FILE__ ) . 'tmpl-settings-upgrade-button.html.php';
    }
}

new NF_THREE_Submenu();
