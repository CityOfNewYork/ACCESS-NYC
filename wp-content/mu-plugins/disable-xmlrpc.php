<?php

/**
 * Plugin Name: Disable XML-RPC
 * Description: Disable XML-RPC methods that require authentication. @link https://kinsta.com/blog/wordpress-xml-rpc/
 * Author: NYC Opportunity
 */

add_filter('xmlrpc_enabled', '__return_false');
