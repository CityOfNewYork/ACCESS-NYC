<?php

namespace WPScan;

# Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class Dashboard
{
    /**
     * Class constructer.
     * 
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
    }

    /**
     * Add the widget
     * 
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function add_dashboard_widgets()
    {
        if ( ! current_user_can($this->parent->WPSCAN_ROLE) ) {
            return;
        }

        wp_add_dashboard_widget(
            $this->parent->WPSCAN_DASHBOARD,
            __('WPScan Status', 'wpscan'),
            [$this, 'dashboard_widget_content']
        );
    }

    /**
     * Render the widget
     * 
     * @since 1.0.0
     * @access public
     * @return string
     */
    public function dashboard_widget_content()
    {
        $report = $this->parent->get_report();

        if ( ! $this->parent->classes['settings']->api_token_set() ) {
            echo '<div>' . __( 'To use WPScan you have to setup your WPScan API Token.', 'wpscan' ) . '</div>';
            return;
        }

        if ( empty($report) ) {
            echo __( 'No Report available', 'wpscan' );
            return;
        }

        $vulns = $this->parent->classes['report']->get_all_vulnerabilities();

        if ( empty($vulns) ) {
            echo __( 'No vulnerabilities found', 'wpscan' );
        }

        echo '<div>';
        
        foreach( $vulns as $vuln ) {
            echo "<div><span class='dashicons dashicons-warning is-red'></span>&nbsp; " . esc_html($vuln) . "</div><br/>";
        }

        echo '</div>';
    }
}