<?php
/*
Plugin Name: Disable XML-RPC
Plugin URI: http://www.philerb.com/wp-plugins/
Description: This plugin disables XML-RPC API in WordPress 3.5+, which is enabled by default.
Version: 1.0.1
Author: Philip Erb
Author URI: http://www.philerb.com
License: GPLv2
*/

/*  Copyright 2012  Philip Erb  (http://www.philerb.com/contact/)

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

add_filter( 'xmlrpc_enabled', '__return_false' );
?>