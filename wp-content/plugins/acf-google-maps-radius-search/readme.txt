=== ACF Google Maps Radius Search ===
Contributors: raiserweb
Donate link: http://raiserweb.com
Tags: ACF, advanced custom fields address search
Requires at least: 3.0.1
Tested up to: 4.5
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Turns ACF address field into a distance radius search on a search results page. Useful for developers.

== Description ==

This plug-in allows search results to be ordered by distance from a specified location.

The plugin only works when the posts have been given an address via the Advanced Custom Field Type 'Google Map', and the search results page url contains longitude ('lng') and latitude ('lat') $_GET variables.
More technical information is contained in the FAQ section.

This plugin is made to help developers create a radius search result, in conjungtion with Advanced Custom Field Type 'Google Map'.

Note - this plugin works in conjunction with the Advanced Custom Field Type 'Google Map'.

== Frequently Asked Questions ==

= What is this plugin for? =
This plugin is for when you are using the Advanced Custom Fields Type 'Google Map' on posts, and you wish to display a distance ordered search results page on your site.

= Why do I need this plugin? =
Unfortunately, the Advanced Custom Fields Type 'Google Map' does not make it easy to query posts based on a radius distance search. This plugin makes this possible.

= How do I use this plugin? =
To use this plugin, first install the plugin as normal. The plugin will then work whenever you add 'lng' and 'lat' variables into a search results page url. For example: example.com/search/?lng=51.023232&lat=12.232323

= How do I add lng and lat values to the search page URL? =
The best way to do this, is to use the Google Places Autocomplete API to allow users to select a location in your search form. Upon selecting a location, you will be able to access the lng and lat values provided by Google. These can then be populated into the search result URL.

= Anything else I should know? =
Yes, your Advanced Custom Field Type 'Google Map' will need to be named 'address'.


== Installation ==

1. Upload this plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Manage competition forms, see and export entries, and select winners via the Competitions menu


== Changelog ==

= 1.0 =
* first release

