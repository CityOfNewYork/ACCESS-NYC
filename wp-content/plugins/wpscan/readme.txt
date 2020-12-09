=== WPScan - WordPress Security Scanner ===
Contributors: ethicalhack3r, xfirefartx, erwanlr
Tags: wpscan, wpvulndb, security, vulnerability, hack, scan, exploit, secure, alerts
Requires at least: 3.4
Tested up to: 5.5.3
Stable tag: 1.13.2
Requires PHP: 5.5
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl.html

WPScan WordPress Security Scanner - Scans your system for security vulnerabilities listed in the WPScan Vulnerability Database.

== Description ==

The WPScan WordPress Security Scanner plugin scans your system on a daily basis to find security vulnerabilities listed in the [WPScan WordPress Vulnerability Database](https://wpscan.com/). It shows an icon on the Admin Toolbar with the total number of security vulnerabilities found.

The [WPScan WordPress Vulnerability Database](https://wpscan.com/) is a WordPress vulnerability database, which includes WordPress core vulnerabilities, plugin vulnerabilities and theme vulnerabilities. The database is maintained by the WPScan Team, who are 100% focused on WordPress security.

To use the WPScan WordPress Security Plugin you will need to use a free API token by [registering here](https://wpscan.com/).

The WPScan WordPress Security Plugin will also check for other security issues, which do not require an API token, such as:

* Check for debug.log files
* Check for wp-config.php backup files
* Check if XML-RPC is enabled
* Check for code repository files
* Check if default secret keys are used
* Check for exported database files

= What does the plugin do? =

* Scans the WordPress core, plugins and themes for known security vulnerabilities;
* Does additional security checks;
* Shows an icon on the Admin Toolbar with the total number of security vulnerabilities found;
* Notifies you by mail when new security vulnerabilities are found.

= Further Reading =

* [WPScan WordPress Vulnerability Database](https://wpscan.com/)
* [WPScan WordPress Security Scanner](https://wpscan.com/wordpress-security-scanner)
* [WPScan Twitter](https://twitter.com/_wpscan_)

== Installation ==

1. Upload `wpscan.zip` content to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. [Register](https://wpscan.com/register) for a free API token
4. Save the API token to the WPScan settings page or within the wp-config.php file

== FAQ ==

* How many API calls are made?

  There is one API call made for the WordPress version, one call for each installed plugin and one for each theme. By default there is one scan per day. The number of daily scans can be configured when configuring notifications.

* How can I configure the API token in the wp-config.php file?

  To configure your API token in the wp-config.php file, use the following PHP code: `define( 'WPSCAN_API_TOKEN', '$your_api_token' );`

* How do I disable vulnerability scanning altogether?

  You can set the following PHP constant in the wp-config.php file to disable scanning; `define( 'WPSCAN_DISABLE_SCANNING_INTERVAL', true );`.

* Why is the "Summary" section and the "Run All" button not showing?

  The cron job did not run, which can be due to:
    - The DISABLE_WP_CRON constant is set to true in the wp-config.php file, but no system cron has been set (crontab -e).
    - A plugin's caching pages is enabled (see https://wordpress.stackexchange.com/questions/93570/wp-cron-doesnt-execute-when-time-elapses?answertab=active#tab-top).
    - The blog is unable to make a loopback request, see the Tools->Site Health for details.
  
  If the issue can not be solved with the above, putting `define('ALTERNATE_WP_CRON', true);` in the wp-config.php could help, however, will reduce the SEO of the blog.

== Screenshots ==

1. List of vulnerabilities and icon at Admin Bar.
2. Notification settings.
3. Site health page.

== Changelog ==

= 1.13.2 =

* Fix XML-RPC check false positive

= 1.13.1 =

* Fix potential WP_Error issue in XML-RPC check
* Add version to client side CSS and JS
* Work towards PHP WordPress coding standards

= 1.13 =
* Improve the XML-RPC security check
* No longer run a scan when adding an API token
* Other small improvements & bug fixes

= 1.12.3 =
* Improve WPScan API error handling
* Add status URL on WPScan API errors
* Delete doing_cron transient on plugin activation
* Replace the xmlrpc_encode_request() PHP function
* Blur API token setting input box

= 1.12.2 =
* Fix bug: case statement should 'break'

= 1.12.1 =
* Fix bug: Handle 404 API errors

= 1.12 =
* Code Refactoring
* Adds Security Check System
* Check for debug.log files
* Check for wp-config.php backups
* Check if XMLRPC is enabled
* Check if default keys are used in wp-config.php
* Check for code repo files .svn and .git
* Create a Vulnerabilities to Ignore meta-box
* Fixes Theme closed incorrect message and position in report
* Show message if API is not working
* Timeout cron jobs
* Fix 404 error in devtools

= 1.11 =
* Change references of wpvulndb to wpscan.com

= 1.10 =
* Add WPSCAN_DISABLE_SCANNING_INTERVAL constant to disable automated scanning
* Add an option in the settings to ignore items
* Add an option in the settings to set the scan time
* Show a not found in database message
* Other minor bug fixes

= 1.9 =
* Add scanning interval option to settings page
* Some other small improvements

= 1.8 =
* Show severity ratings for Enterprise users
* Show Plugin Closed label
* Add PDF report download
* Add account status meta box
* Add support for API token constant in wp-config.php file
* Show vulnerabilities in Site Health
* Update menu icon to monochrome

= 1.7 =
* Updated text and messages to reduce confusion
* Removed WPScan_JWT class as no longer required

= 1.6 =
* Use the new slug helper method on all items on the page

= 1.5 =
* Better slug detection before calling the API

= 1.4 =
* Prevent multiple tasks to run simultaneously
* Check Now Button disabled and Spinner icon displayed when a task is already running
* Results page automatically reloaded when Task is finished (checked every 10s)

= 1.3 =
* Use the /status API endpoint to determine if the Token is valid. As a result, a call is no longer consumed when setting/changing the API token.
* Trim and remove potential leading 'v' in versions when comparing then with the fixed_in values.

= 1.2 =
* Add notice about paid licenses

= 1.1 =
* Warn if API Limit was hit

= 1.0 =
* First release.
