<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * This class handles all the update-related stuff for extensions, including adding a license section to the license tab.
 * It accepts two args: Product Name and Version.
 *
 * @param $product_name string
 * @param $version string
 * @since 2.2.47
 * @return void
 */
class NF_Extension_Updater
{
    public $product_nice_name = '';
    public $product_name = '';
    public $version = '';
    public $store_url = 'https://ninjaforms.com';
    public $file = '';
    public $author = '';
    public $error = '';

    /**
     * Constructor function
     *
     * @since 2.2.47
     * @updated 3.0
     * @return void
     */
    public function __construct( $product_name, $version, $author, $file, $slug = '' )
    {
        $this->product_nice_name = $product_name;
        if ( $slug == '' ) {
            $this->product_name = strtolower( $product_name );
            $this->product_name = preg_replace( "/[^a-zA-Z]+/", "", $this->product_name );
        } else {
            $this->product_name = $slug;
        }

        $this->version = $version;
        $this->file = $file;
        $this->author = $author;

        $this->auto_update();
        
        add_filter( 'ninja_forms_settings_licenses_addons', array( $this, 'register' ) );
    }

    /**
     * Function that adds the license entry fields to the license tab.
     *
     * @updated 3.0
     * @param array $licenses
     * @return array $licenses
     */
    function register( $licenses ) {
        $licenses[] = $this;
        return $licenses;
    }

    /*
     *
     * Function that activates our license
     *
     * @since 2.2.47
     * @return void
     */
    function activate_license( $license_key ) {

        // data to send in our API request
        $api_params = array(
            'edd_action'=> 'activate_license',
            'license' 	=> $license_key,
            'item_name' => urlencode( $this->product_nice_name ) // the name of our product in EDD
        );

        // Call the custom API.
        $response = wp_remote_post( esc_url_raw( add_query_arg( $api_params, $this->store_url ) ) );

        $this->maybe_debug( $response );

        // make sure the response came back okay
        if ( is_wp_error( $response ) )
            return false;

        // decode the license data
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if ( 'invalid' == $license_data->license ) {
            $error = '<span style="color: red;">' . __( 'Could not activate license. Please verify your license key', 'ninja-forms' ) . '</span>';
        } else {
            $error = '';
        }

        Ninja_Forms()->update_setting( $this->product_name . '_license', $license_key );
        Ninja_Forms()->update_setting( $this->product_name . '_license_error', $error );
        Ninja_Forms()->update_setting( $this->product_name . '_license_status', $license_data->license );
    }

    /*
     *
     * Function that deactivates our license if the user clicks the "Deactivate License" button.
     *
     * @since 2.2.47
     * @return void
     */

    function deactivate_license() {

        $license = Ninja_Forms()->get_setting( $this->product_name . '_license' );

        // data to send in our API request
        $api_params = array(
            'edd_action'=> 'deactivate_license',
            'license' 	=> $license,
            'item_name' => urlencode( $this->product_nice_name ) // the name of our product in EDD
        );

        // Call the custom API.
        $response = wp_remote_post( esc_url_raw( add_query_arg( $api_params, $this->store_url ) ), array( 'timeout' => 15, 'sslverify' => false ) );

        $this->maybe_debug( $response );

        // make sure the response came back okay
        if ( is_wp_error( $response ) )
            return false;

        Ninja_Forms()->update_setting( $this->product_name.'_license_error', '' );
        Ninja_Forms()->update_setting( $this->product_name.'_license_status', 'invalid' );
        Ninja_Forms()->update_setting( $this->product_name.'_license', '' );
    }

    /**
     * Function that runs all of our auto-update functionality
     *
     * @since 2.2.47
     * @updates 3.0
     * @return void
     */
    function auto_update() {

        $edd_updater = new EDD_SL_Plugin_Updater( $this->store_url, $this->file, array(
                'author'    => $this->author,  // author of this plugin
                'version'   => $this->version, // current version number
                'item_name' => $this->product_nice_name,  // name of this plugin
                'license'   => Ninja_Forms()->get_setting( $this->product_name.'_license' ),  // license key
            )
        );
    } // function auto_update

    /**
     * Return whether or not this license is valid.
     *
     * @access public
     * @since 2.9
     * @return bool
     */
    public function is_valid() {
         return ( 'valid' == Ninja_Forms()->get_setting( $this->product_name.'_license_status' ) );
    }

    /**
     * Get any error messages for this license field.
     *
     * @access public
     * @since 2.9
     * @return string $error
     */
    public function get_error() {
        return Ninja_Forms()->get_setting( $this->product_name . '_license_error' );
    }

    private function maybe_debug( $data, $key = 'debug' )
    {
        if ( isset ( $_GET[ $key ] ) && 'true' == $_GET[ $key ] ) {
            echo '<pre>'; var_dump( $data ); echo '</pre>';
            die();
        }
    }

} // End Class NF_Extension_Updater
