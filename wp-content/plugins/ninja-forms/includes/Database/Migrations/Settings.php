<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Database_Migrations_Settings extends NF_Abstracts_Migration
{
    protected $_defaults = array();

    public function __construct()
    {
        $this->_defaults = Ninja_Forms()->config( 'PluginSettingsDefaults' );
    }

    public function run()
    {
        $settings = Ninja_Forms()->get_settings();

        $settings = array_merge( $this->_defaults, $settings );

        Ninja_Forms()->update_settings( $settings );
    }
}