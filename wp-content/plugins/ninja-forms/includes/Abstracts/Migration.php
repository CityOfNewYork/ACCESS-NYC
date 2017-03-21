<?php if ( ! defined( 'ABSPATH' ) ) exit;

require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

abstract class NF_Abstracts_Migration
{
    public $table_name = '';

    public $charset_collate = '';

    public $flag = '';

    public function __construct( $table_name, $flag )
    {
        global $wpdb;

        $this->table_name = $wpdb->prefix . $table_name;

        $this->charset_collate = $wpdb->get_charset_collate();
    }

    public function _run()
    {
        // Check the flag
        if( get_option( $this->flag, FALSE ) ) return;

        // Run the migration
        $this->run();

        // Set the Flag
        update_option( $this->flag, TRUE );
    }

    protected abstract function run();
}