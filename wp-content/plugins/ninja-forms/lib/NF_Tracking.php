<?php
/**
 * Tracking functions for reporting plugin usage to the Ninja Forms site for users that have opted in
 *
 * @package     Ninja Forms
 * @subpackage  Admin
 * @copyright   Copyright (c) 2016, The WP Ninjas
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.52
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Tracking
 */
final class NF_Tracking
{
    const OPT_IN = 1;
    const OPT_OUT = 0;
    const FLAG = 'ninja_forms_opt_in';

    /**
     * NF_Tracking constructor.
     */
    public function __construct()
    {
        if( isset( $_GET[ self::FLAG ] ) ){
            add_action( 'admin_init', array( $this, 'maybe_opt_in' ) );
        }

        add_filter( 'nf_admin_notices', array( $this, 'admin_notice' ) );

        add_action( 'ninja_forms_upgrade', array( $this, 'opt_in' ) );
    }

    /**
     * Check if an opt in/out action should be performed.
     *
     * @access public
     * @hook admin_init
     */
    public function maybe_opt_in()
    {
        if( $this->can_opt_in() ) {

            $opt_in_action = htmlspecialchars( $_GET[ self::FLAG ] );

            if( self::OPT_IN == $opt_in_action ){
                $this->opt_in();
            }

            if( self::OPT_OUT == $opt_in_action ){
                $this->opt_out();
            }
        }
        header( 'Location: ' . admin_url( 'admin.php?page=ninja-forms' ) );
    }

    /**
     * Register the Admin Notice for asking users to opt in to tracking
     *
     * @access public
     * @hook nf_admin_notices
     * @param array $notices
     * @return array $notices
     */
    public function admin_notice( $notices )
    {
        // Check if the user is allowed to opt in.
        if( ! $this->can_opt_in() ) return $notices;

        // Check if the user is already opted in/out.
        if( $this->is_opted_in() || $this->is_opted_out()  ) return $notices;

        $notices[ 'allow_tracking' ] = array(
            'title' => __( 'Please help us improve Ninja Forms!', 'ninja-forms' ),
            'msg' => implode( '<br />', array(
                __( 'If you opt-in, some data about your installation of Ninja Forms will be sent to NinjaForms.com (this does NOT include your submissions).', 'ninja-forms' ),
                __( 'If you skip this, that\'s okay! Ninja Forms will still work just fine.', 'ninja-forms' ),
            )),
            'link' => implode( ' ', array(
                sprintf( __( '%sAllow%s', 'ninja-forms' ), '<a href="' . $this->get_opt_in_url( admin_url( 'admin.php?page=ninja-forms' ) ) . '" class="button-primary" id="ninja-forms-allow-tracking">', '</a>' ),
                sprintf( __( '%sDo not allow%s', 'ninja-forms' ), '<a href="' . $this->get_opt_out_url( admin_url( 'admin.php?page=ninja-forms' ) ) . '" class="button-secondary" id="ninja-forms-do-not-allow-tracking">', '</a>' ),
            )),
            'int' => 0, // No delay
            'blacklist' => array(
                'ninja-forms-three'
            )
        );

        return $notices;
    }

    /**
     * Check if the current user is allowed to opt in on behalf of a site
     *
     * @return bool
     */
    private function can_opt_in()
    {
        return current_user_can( apply_filters( 'ninja_forms_admin_opt_in_capabilities', 'manage_options' ) );
    }

    /**
     * Check if a site is opted in
     *
     * @access public
     * @return bool
     */
    public function is_opted_in()
    {
        return (bool) get_option( 'ninja_forms_allow_tracking', $this->is_freemius_opted_in() );
    }

    private function is_freemius_opted_in()
    {
        $freemius = get_option( 'fs_accounts' );
        if( ! $freemius ) return false;
        if( ! isset( $freemius[ 'plugin_data' ] ) ) return false;
        if( ! isset( $freemius[ 'plugin_data' ][ 'ninja-forms' ] ) ) return false;
        if( ! isset( $freemius[ 'plugin_data' ][ 'ninja-forms' ][ 'activation_timestamp' ] ) ) return false;
        return true;
    }

    /**
     * Opt In a site for tracking
     *
     * @access private
     * @return null
     */
    public function opt_in()
    {
        update_option( 'ninja_forms_allow_tracking', true );
    }

    /**
     * Get the Opt In URL
     *
     * @access private
     * @param string $url
     * @return string $url
     */
    private function get_opt_in_url( $url )
    {
        return add_query_arg( 'ninja_forms_opt_in', self::OPT_IN, $url );
    }

    /**
     * Check if a site is opted out
     *
     * @access public
     * @return bool
     */
    public function is_opted_out()
    {
        return (bool) get_option( 'ninja_forms_do_not_allow_tracking', $this->is_freemius_opted_out() );
    }

    private function is_freemius_opted_out()
    {
        $freemius = get_option( 'fs_accounts' );
        if( ! $freemius ) return false;
        if( ! isset( $freemius[ 'plugin_data' ] ) ) return false;
        if( ! isset( $freemius[ 'plugin_data' ][ 'ninja-forms' ] ) ) return false;
        if( ! isset( $freemius[ 'plugin_data' ][ 'ninja-forms' ][ 'is_anonymous' ] ) ) return false;
        return true;
    }

    /**
     * Opt Out a site from tracking
     *
     * @access private
     * @return null
     */
    private function opt_out()
    {
        update_option( 'ninja_forms_do_not_allow_tracking', true );
    }

    /**
     * Get the Opt Out URL
     *
     * @access private
     * @param string $url
     * @return string $url
     */
    private function get_opt_out_url( $url )
    {
        return add_query_arg( 'ninja_forms_opt_in', self::OPT_OUT, $url );
    }

} // END CLASS NF_Tracking
