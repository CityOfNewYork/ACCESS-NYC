<?php

class FacetWP_Upgrade
{
    function __construct() {
        $this->version = FACETWP_VERSION;
        $this->last_version = get_option( 'facetwp_version' );

        if ( version_compare( $this->last_version, $this->version, '<' ) ) {
            if ( version_compare( $this->last_version, '0.1.0', '<' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $this->clean_install();
            }
            else {
                $this->run_upgrade();
            }

            update_option( 'facetwp_version', $this->version );
        }
    }


    private function clean_install() {
        global $wpdb;

        $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}facetwp_index (
            id BIGINT unsigned not null auto_increment,
            post_id INT unsigned,
            facet_name VARCHAR(255),
            facet_source VARCHAR(255),
            facet_value TEXT,
            facet_display_value TEXT,
            term_id INT unsigned default '0',
            parent_id INT unsigned default '0',
            depth INT unsigned default '0',
            variation_id INT unsigned default '0',
            PRIMARY KEY (id),
            INDEX facet_name_idx (facet_name),
            INDEX facet_source_idx (facet_source)
        ) DEFAULT CHARSET=utf8";
        dbDelta( $sql );

        // Add default settings
        $settings = file_get_contents( FACETWP_DIR . '/assets/js/src/sample.json' );
        add_option( 'facetwp_settings', $settings );
    }


    private function run_upgrade() {
        global $wpdb;

        // Add "term_id" column
        if ( version_compare( $this->last_version, '1.9', '<' ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}facetwp_index ADD COLUMN term_id INT unsigned default '0' AFTER facet_display_value" );

            // Copy term IDs to the new column
            $wpdb->query( "UPDATE {$wpdb->prefix}facetwp_index SET term_id = facet_value WHERE LEFT(facet_source, 4) = 'tax/'" );
        }

        // Deactivate the proximity add-on
        if ( version_compare( $this->last_version, '2.2.3', '<' ) ) {
            deactivate_plugins( 'facetwp-proximity/facetwp-proximity.php' );
            deactivate_plugins( 'facetwp-proximity-master/facetwp-proximity.php' );
        }

        // Add "variation_id" column
        if ( version_compare( $this->last_version, '2.7', '<' ) ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}facetwp_index ADD COLUMN variation_id INT unsigned default '0' AFTER depth" );
        }
    }
}
