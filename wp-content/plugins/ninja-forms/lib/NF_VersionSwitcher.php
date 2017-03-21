<?php

final class NF_VersionSwitcher
{
    public function __construct()
    {
        $this->ajax_check();

        add_action( 'init', array( $this, 'version_bypass_check' ) );

        add_action( 'admin_init', array( $this, 'listener' )  );

        if( defined( 'NF_DEV' ) && NF_DEV ) {
            add_action('admin_bar_menu', array( $this, 'admin_bar_menu'), 999);
        }
    }

    public function ajax_check()
    {
        $nf2to3 = isset( $_POST[ 'nf2to3' ] );
        $doing_ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );
        if( $nf2to3 && ! $doing_ajax ){
            wp_die(
                __( 'You do not have permission.', 'ninja-forms' ),
                __( 'Permission Denied', 'ninja-forms' )
            );
        }
    }

    public function version_bypass_check()
    {
        if( ! isset( $_POST[ 'nf2to3' ] ) ) return TRUE;

        $capability = apply_filters( 'ninja_forms_admin_version_bypass_capabilities', 'manage_options' );
        $current_user_can = current_user_can( $capability );

        if( $current_user_can ) return TRUE;

        wp_die(
            __( 'You do not have permission.', 'ninja-forms' ),
            __( 'Permission Denied', 'ninja-forms' )
        );
    }

    public function listener()
    {
        if( ! current_user_can( apply_filters( 'ninja_forms_admin_version_switcher_capabilities', 'manage_options' ) ) ) return;

        if( isset( $_GET[ 'nf-switcher' ] ) ){

            switch( $_GET[ 'nf-switcher' ] ){
                case 'upgrade':
                    update_option( 'ninja_forms_load_deprecated', FALSE );
                    do_action( 'ninja_forms_upgrade' );
                    break;
                case 'rollback':
                    update_option( 'ninja_forms_load_deprecated', TRUE );
                    $this->rollback_activation();
                    do_action( 'ninja_forms_rollback' );
                    break;
            }

            header( 'Location: ' . admin_url( 'admin.php?page=ninja-forms' ) );
        }
    }

    public function admin_bar_menu( $wp_admin_bar )
    {
        $args = array(
            'id'    => 'nf',
            'title' => __( 'Ninja Forms Dev', 'ninja-forms' ),
            'href'  => '#',
        );
        $wp_admin_bar->add_node( $args );
        $args = array(
            'id' => 'nf_switcher',
            'href' => admin_url(),
            'parent' => 'nf'
        );
        if( ! get_option( 'ninja_forms_load_deprecated' ) ) {
            $args[ 'title' ] = __( 'DEBUG: Switch to 2.9.x', 'ninja-forms' );
            $args[ 'href' ] .= '?nf-switcher=rollback';
        } else {
            $args[ 'title' ] = __( 'DEBUG: Switch to 3.0.x', 'ninja-forms' );
            $args[ 'href' ] .= '?nf-switcher=upgrade';
        }
        $wp_admin_bar->add_node($args);
    }

    public function rollback_activation()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nf_objects';

        if( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s",  $table_name ) ) == $table_name ) return;

        if ( ! is_multisite() ) { // This is a single-site activation.

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            if( ! defined( 'NINJA_FORMS_FAV_FIELDS_TABLE_NAME' ) ){
                define( 'NINJA_FORMS_FAV_FIELDS_TABLE_NAME', $wpdb->prefix . 'ninja_forms_fav_fields' );
            }

            if( ! defined( 'NINJA_FORMS_FIELDS_TABLE_NAME' ) ){
                define( 'NINJA_FORMS_FIELDS_TABLE_NAME', $wpdb->prefix . 'ninja_forms_fields' );
            }

            if( ! defined( 'NF_OBJECT_META_TABLE_NAME' ) ){
                define( 'NF_OBJECT_META_TABLE_NAME', $wpdb->prefix .'nf_objectmeta' );
            }

            if( ! defined( 'NF_OBJECTS_TABLE_NAME' ) ){
                define( 'NF_OBJECTS_TABLE_NAME', $wpdb->prefix .'nf_objects' );
            }

            if( ! defined( 'NF_OBJECT_RELATIONSHIPS_TABLE_NAME' ) ){
                define( 'NF_OBJECT_RELATIONSHIPS_TABLE_NAME', $wpdb->prefix .'nf_relationships' );
            }

            if( ! defined( 'NF_PLUGIN_VERSION' ) ){
                define( 'NF_PLUGIN_VERSION', Ninja_Forms::VERSION );
            }

            $opt = get_option( 'ninja_forms_settings' );

            $sql = "CREATE TABLE IF NOT EXISTS ".NINJA_FORMS_FAV_FIELDS_TABLE_NAME." (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`row_type` int(11) NOT NULL,
		`type` varchar(255) CHARACTER SET utf8 NOT NULL,
		`order` int(11) NOT NULL,
		`data` longtext CHARACTER SET utf8 NOT NULL,
		`name` varchar(255) CHARACTER SET utf8 NOT NULL,
		PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

            dbDelta($sql);

            $state_dropdown = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".NINJA_FORMS_FAV_FIELDS_TABLE_NAME." WHERE name = %s AND row_type = 0", 'State Dropdown' ), ARRAY_A );
            if( !isset($state_dropdown['id']) ){
                $sql = 'INSERT INTO `'.NINJA_FORMS_FAV_FIELDS_TABLE_NAME.'` (`id`, `row_type`, `type`, `order`, `data`, `name`) VALUES
			(2, 0, \'_list\', 0, \'a:10:{s:5:\"label\";s:14:\"State Dropdown\";s:9:\"label_pos\";s:4:\"left\";s:9:\"list_type\";s:8:\"dropdown\";s:10:\"multi_size\";s:1:\"5\";s:15:\"list_show_value\";s:1:\"1\";s:4:\"list\";a:1:{s:7:\"options\";a:51:{i:0;a:3:{s:5:\"label\";s:7:\"Alabama\";s:5:\"value\";s:2:\"AL\";s:8:\"selected\";s:1:\"0\";}i:1;a:3:{s:5:\"label\";s:6:\"Alaska\";s:5:\"value\";s:2:\"AK\";s:8:\"selected\";s:1:\"0\";}i:2;a:3:{s:5:\"label\";s:7:\"Arizona\";s:5:\"value\";s:2:\"AZ\";s:8:\"selected\";s:1:\"0\";}i:3;a:3:{s:5:\"label\";s:8:\"Arkansas\";s:5:\"value\";s:2:\"AR\";s:8:\"selected\";s:1:\"0\";}i:4;a:3:{s:5:\"label\";s:10:\"California\";s:5:\"value\";s:2:\"CA\";s:8:\"selected\";s:1:\"0\";}i:5;a:3:{s:5:\"label\";s:8:\"Colorado\";s:5:\"value\";s:2:\"CO\";s:8:\"selected\";s:1:\"0\";}i:6;a:3:{s:5:\"label\";s:11:\"Connecticut\";s:5:\"value\";s:2:\"CT\";s:8:\"selected\";s:1:\"0\";}i:7;a:3:{s:5:\"label\";s:8:\"Delaware\";s:5:\"value\";s:2:\"DE\";s:8:\"selected\";s:1:\"0\";}i:8;a:3:{s:5:\"label\";s:20:\"District of Columbia\";s:5:\"value\";s:2:\"DC\";s:8:\"selected\";s:1:\"0\";}i:9;a:3:{s:5:\"label\";s:7:\"Florida\";s:5:\"value\";s:2:\"FL\";s:8:\"selected\";s:1:\"0\";}i:10;a:3:{s:5:\"label\";s:7:\"Georgia\";s:5:\"value\";s:2:\"GA\";s:8:\"selected\";s:1:\"0\";}i:11;a:3:{s:5:\"label\";s:6:\"Hawaii\";s:5:\"value\";s:2:\"HI\";s:8:\"selected\";s:1:\"0\";}i:12;a:3:{s:5:\"label\";s:5:\"Idaho\";s:5:\"value\";s:2:\"ID\";s:8:\"selected\";s:1:\"0\";}i:13;a:3:{s:5:\"label\";s:8:\"Illinois\";s:5:\"value\";s:2:\"IL\";s:8:\"selected\";s:1:\"0\";}i:14;a:3:{s:5:\"label\";s:7:\"Indiana\";s:5:\"value\";s:2:\"IN\";s:8:\"selected\";s:1:\"0\";}i:15;a:3:{s:5:\"label\";s:4:\"Iowa\";s:5:\"value\";s:2:\"IA\";s:8:\"selected\";s:1:\"0\";}i:16;a:3:{s:5:\"label\";s:6:\"Kansas\";s:5:\"value\";s:2:\"KS\";s:8:\"selected\";s:1:\"0\";}i:17;a:3:{s:5:\"label\";s:8:\"Kentucky\";s:5:\"value\";s:2:\"KY\";s:8:\"selected\";s:1:\"0\";}i:18;a:3:{s:5:\"label\";s:9:\"Louisiana\";s:5:\"value\";s:2:\"LA\";s:8:\"selected\";s:1:\"0\";}i:19;a:3:{s:5:\"label\";s:5:\"Maine\";s:5:\"value\";s:2:\"ME\";s:8:\"selected\";s:1:\"0\";}i:20;a:3:{s:5:\"label\";s:8:\"Maryland\";s:5:\"value\";s:2:\"MD\";s:8:\"selected\";s:1:\"0\";}i:21;a:3:{s:5:\"label\";s:13:\"Massachusetts\";s:5:\"value\";s:2:\"MA\";s:8:\"selected\";s:1:\"0\";}i:22;a:3:{s:5:\"label\";s:8:\"Michigan\";s:5:\"value\";s:2:\"MI\";s:8:\"selected\";s:1:\"0\";}i:23;a:3:{s:5:\"label\";s:9:\"Minnesota\";s:5:\"value\";s:2:\"MN\";s:8:\"selected\";s:1:\"0\";}i:24;a:3:{s:5:\"label\";s:11:\"Mississippi\";s:5:\"value\";s:2:\"MS\";s:8:\"selected\";s:1:\"0\";}i:25;a:3:{s:5:\"label\";s:8:\"Missouri\";s:5:\"value\";s:2:\"MO\";s:8:\"selected\";s:1:\"0\";}i:26;a:3:{s:5:\"label\";s:7:\"Montana\";s:5:\"value\";s:2:\"MT\";s:8:\"selected\";s:1:\"0\";}i:27;a:3:{s:5:\"label\";s:8:\"Nebraska\";s:5:\"value\";s:2:\"NE\";s:8:\"selected\";s:1:\"0\";}i:28;a:3:{s:5:\"label\";s:6:\"Nevada\";s:5:\"value\";s:2:\"NV\";s:8:\"selected\";s:1:\"0\";}i:29;a:3:{s:5:\"label\";s:13:\"New Hampshire\";s:5:\"value\";s:2:\"NH\";s:8:\"selected\";s:1:\"0\";}i:30;a:3:{s:5:\"label\";s:10:\"New Jersey\";s:5:\"value\";s:2:\"NJ\";s:8:\"selected\";s:1:\"0\";}i:31;a:3:{s:5:\"label\";s:10:\"New Mexico\";s:5:\"value\";s:2:\"NM\";s:8:\"selected\";s:1:\"0\";}i:32;a:3:{s:5:\"label\";s:8:\"New York\";s:5:\"value\";s:2:\"NY\";s:8:\"selected\";s:1:\"0\";}i:33;a:3:{s:5:\"label\";s:14:\"North Carolina\";s:5:\"value\";s:2:\"NC\";s:8:\"selected\";s:1:\"0\";}i:34;a:3:{s:5:\"label\";s:12:\"North Dakota\";s:5:\"value\";s:2:\"ND\";s:8:\"selected\";s:1:\"0\";}i:35;a:3:{s:5:\"label\";s:4:\"Ohio\";s:5:\"value\";s:2:\"OH\";s:8:\"selected\";s:1:\"0\";}i:36;a:3:{s:5:\"label\";s:8:\"Oklahoma\";s:5:\"value\";s:2:\"OK\";s:8:\"selected\";s:1:\"0\";}i:37;a:3:{s:5:\"label\";s:6:\"Oregon\";s:5:\"value\";s:2:\"OR\";s:8:\"selected\";s:1:\"0\";}i:38;a:3:{s:5:\"label\";s:12:\"Pennsylvania\";s:5:\"value\";s:2:\"PA\";s:8:\"selected\";s:1:\"0\";}i:39;a:3:{s:5:\"label\";s:12:\"Rhode Island\";s:5:\"value\";s:2:\"RI\";s:8:\"selected\";s:1:\"0\";}i:40;a:3:{s:5:\"label\";s:14:\"South Carolina\";s:5:\"value\";s:2:\"SC\";s:8:\"selected\";s:1:\"0\";}i:41;a:3:{s:5:\"label\";s:12:\"South Dakota\";s:5:\"value\";s:2:\"SD\";s:8:\"selected\";s:1:\"0\";}i:42;a:3:{s:5:\"label\";s:9:\"Tennessee\";s:5:\"value\";s:2:\"TN\";s:8:\"selected\";s:1:\"0\";}i:43;a:3:{s:5:\"label\";s:5:\"Texas\";s:5:\"value\";s:2:\"TX\";s:8:\"selected\";s:1:\"0\";}i:44;a:3:{s:5:\"label\";s:4:\"Utah\";s:5:\"value\";s:2:\"UT\";s:8:\"selected\";s:1:\"0\";}i:45;a:3:{s:5:\"label\";s:7:\"Vermont\";s:5:\"value\";s:2:\"VT\";s:8:\"selected\";s:1:\"0\";}i:46;a:3:{s:5:\"label\";s:8:\"Virginia\";s:5:\"value\";s:2:\"VA\";s:8:\"selected\";s:1:\"0\";}i:47;a:3:{s:5:\"label\";s:10:\"Washington\";s:5:\"value\";s:2:\"WA\";s:8:\"selected\";s:1:\"0\";}i:48;a:3:{s:5:\"label\";s:13:\"West Virginia\";s:5:\"value\";s:2:\"WV\";s:8:\"selected\";s:1:\"0\";}i:49;a:3:{s:5:\"label\";s:9:\"Wisconsin\";s:5:\"value\";s:2:\"WI\";s:8:\"selected\";s:1:\"0\";}i:50;a:3:{s:5:\"label\";s:7:\"Wyoming\";s:5:\"value\";s:2:\"WY\";s:8:\"selected\";s:1:\"0\";}}}s:3:\"req\";s:1:\"0\";s:5:\"class\";s:0:\"\";s:9:\"show_help\";s:1:\"0\";s:9:\"help_text\";s:0:\"\";}\', \'State Dropdown\')';
                $wpdb->query($sql);
            }

            $anti_spam = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".NINJA_FORMS_FAV_FIELDS_TABLE_NAME." WHERE name = %s AND row_type = 0", 'Anti-Spam' ), ARRAY_A );
            if( !isset($anti_spam['id']) ){
                $sql = 'INSERT INTO `'.NINJA_FORMS_FAV_FIELDS_TABLE_NAME.'` (`id`, `row_type`, `type`, `order`, `data`, `name`) VALUES
			(3, 0, \'_spam\', 0, \'a:6:{s:9:"label_pos";s:4:"left";s:5:"label";s:18:"Anti-Spam Question";s:6:"answer";s:16:"Anti-Spam Answer";s:5:"class";s:0:"";s:9:"show_help";s:1:"0";s:9:"help_text";s:0:"";}\', \'Anti-Spam\')';
                $wpdb->query($sql);
            }

            $submit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".NINJA_FORMS_FAV_FIELDS_TABLE_NAME." WHERE name = %s AND row_type = 0", 'Submit' ), ARRAY_A );
            if( !isset($submit['id']) ){
                $sql = 'INSERT INTO `'.NINJA_FORMS_FAV_FIELDS_TABLE_NAME.'` (`id`, `row_type`, `type`, `order`, `data`, `name`) VALUES
			(4, 0, \'_submit\', 0, \'a:4:{s:5:\"label\";s:6:\"Submit\";s:5:\"class\";s:0:\"\";s:9:\"show_help\";s:1:\"0\";s:9:\"help_text\";s:0:\"\";}\', \'Submit\');';
                $wpdb->query($sql);
            }

            $sql = "CREATE TABLE IF NOT EXISTS ".NINJA_FORMS_FIELDS_TABLE_NAME." (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `form_id` int(11) NOT NULL,
		  `type` varchar(255) CHARACTER SET utf8 NOT NULL,
		  `order` int(11) NOT NULL,
		  `data` longtext CHARACTER SET utf8 NOT NULL,
		  `fav_id` int(11) DEFAULT NULL,
		  `def_id` int(11) DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8 ;";

            dbDelta($sql);

            /**
             * Add our table structure for version 2.8.
             */

            // Create our object meta table
            $sql = "CREATE TABLE IF NOT EXISTS ". NF_OBJECT_META_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `object_id` bigint(20) NOT NULL,
		  `meta_key` varchar(255) NOT NULL,
		  `meta_value` longtext NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

            dbDelta( $sql );

            // Create our object table
            $sql = "CREATE TABLE IF NOT EXISTS " . NF_OBJECTS_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `type` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

            dbDelta( $sql );

            // Create our object relationships table

            $sql = "CREATE TABLE IF NOT EXISTS " . NF_OBJECT_RELATIONSHIPS_TABLE_NAME . " (
		  `id` bigint(20) NOT NULL AUTO_INCREMENT,
		  `child_id` bigint(20) NOT NULL,
		  `parent_id` bigint(20) NOT NULL,
		  `child_type` varchar(255) NOT NULL,
		  `parent_type` varchar(255) NOT NULL,
		  PRIMARY KEY (`id`)
		) DEFAULT CHARSET=utf8;";

            dbDelta( $sql );

            $title = apply_filters( 'ninja_forms_preview_page_title', 'ninja_forms_preview_page' );
            $preview_page = get_page_by_title( $title );
            if( !$preview_page ) {
                // Create preview page object
                $preview_post = array(
                    'post_title' => $title,
                    'post_content' => 'This is a preview of how this form will appear on your website',
                    'post_status' => 'draft',
                    'post_type' => 'page'
                );

                // Insert the page into the database
                $page_id = wp_insert_post( $preview_post );
            }else{
                $page_id = $preview_page->ID;
            }

            $opt['preview_id'] = $page_id;

            $current_settings = get_option( 'ninja_forms_settings', false );

            if ( ! $current_settings ) {
                update_option( 'nf_convert_notifications_complete', true );
                update_option( 'nf_convert_subs_step', 'complete' );
                update_option( 'nf_upgrade_notice', 'closed' );
                update_option( 'nf_update_email_settings_complete', true );
                update_option( 'nf_email_fav_updated', true );
                update_option( 'nf_convert_forms_complete', true );
                update_option( 'nf_database_migrations', true );
            }

            update_option( "ninja_forms_settings", $opt );
            update_option( 'ninja_forms_version', '2.9.56.2' );

        } else { // We're network activating.
            header( 'Location: ' . network_admin_url( 'plugins.php?deactivate=true&nf_action=network_activation_error' ) );
            exit;
        }

    }

}

new NF_VersionSwitcher();
