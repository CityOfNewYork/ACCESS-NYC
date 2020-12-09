<?php

/**
 * Classname: WPScan\Checks\xmlrpcEnabled
 */

namespace WPScan\Checks;

# Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


class xmlrpcEnabled extends Check
{
    /**
     * Title.
     * 
     * @since 1.0.0
     * @access public
     * @return string
     */
    public function title()
    {
        return __('XML-RPC Enabled', 'wpscan');
    }

    /**
     * Description.
     * 
     * @since 1.0.0
     * @access public
     * @return string
     */
    public function description()
    {
        return __('Check if the WordPress XML-RPC is enabled.', 'wpscan');
    }

    /**
     * Success message.
     * 
     * @since 1.0.0
     * @access public
     * @return string
     */
    public function success_message()
    {
        return __('XML-RPC was found to be disabled.', 'wpscan');
    }

    /**
     * Perform the check and save the results.
     * 
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function perform()
    {
        $vulnerabilities = $this->get_vulnerabilities();
        $url             = get_site_url() . '/xmlrpc.php';

        # First check if the xmlrpc.php file returns a 405 code.
        $is_available      = wp_remote_get( $url, ['timeout' => 5] );
        $is_available_code = wp_remote_retrieve_response_code($is_available);

        if ( 405 !== $is_available_code ) return;

        # Try an authenticated request.
        $authenticated_body          = '<?xml version="1.0" encoding="iso-8859-1"?><methodCall><methodName>wp.getUsers</methodName><params><param><value>1</value></param><param><value>username</value></param><param><value>password</value></param></params></methodCall>';
        $authenticated_response      = wp_remote_post( $url, array( 'body' => $authenticated_body ) );
        $authenticated_response_code = wp_remote_retrieve_response_code($authenticated_response);

        if ( !preg_match('/<string>XML-RPC services are disabled on this site.<\/string>/', $authenticated_response['body']) ) {
            $this->add_vulnerability(__('The XML-RPC interface is enabled. This significantly increases your site\'s attack surface.', 'wpscan'), 'medium', sanitize_title($url));
            return;
        } else {
            # Try an unauthenticated request.
            $unauthenticated_body = '<?xml version="1.0" encoding="iso-8859-1"?><methodCall><methodName>demo.sayHello</methodName><params><param></param></params></methodCall>';

             $unauthenticated_response = wp_remote_post( $url, array( 'body' => $unauthenticated_body ) );

            if ( preg_match('/<string>Hello!<\/string>/', $unauthenticated_response['body']) ) {
                $this->add_vulnerability(__('The XML-RPC interface is partly disabled, but still allows unauthenticated requests.', 'wpscan'), 'low', sanitize_title($url));
            }
        }
    }
}
