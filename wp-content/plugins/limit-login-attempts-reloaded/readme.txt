=== Limit Login Attempts Reloaded ===
Contributors: wpchefgadget
Donate link: https://www.paypal.com/donate?hosted_button_id=FKD4MYFCMNVQQ
Tags: brute force, login, security, firewall, protection
Requires at least: 3.0
Tested up to: 5.8
Stable tag: 2.23.2

Reloaded version of the original Limit Login Attempts plugin for Login Protection by a team of WordPress developers. GDPR compliant.

== Description ==

Limit Login Attempts Reloaded stops brute-force attacks and optimizes your site performance by limiting the number of login attempts that are possible through the normal login as well as XMLRPC, Woocommerce and custom login pages.

This plugin will block an Internet address (IP) and/or username from making further attempts after a specified limit on retries has been reached, making a brute-force attack difficult or impossible.

WordPress by default allows unlimited login attempts. This can lead to passwords being easily cracked via brute-force.

Limit Login Attempts Reloaded 
> <strong>Limit Login Attempts Reloaded Premium Cloud App</strong><br>
> Enables cloud protection for Limit Login Attempts Reloaded plugin. It comes with all the great features you'll need to stop hackers and bots from brute-force attacks. The cloud app <a href="https://www.limitloginattempts.com/features/?from=wp-details">offers several features</a> including advanced protection out of the box, and the ability for site admins and agencies to sync safelists/blocklists across multiple domains. <a href="https://www.limitloginattempts.com/features/?from=wp-details-cta">Click here to activate the cloud app for the best WordPress security plugin now!</a>

https://www.youtube.com/watch?v=wzmPXu55zLU

= Features: =
* Limit the number of retry attempts when logging in (per each IP). 
* Configurable lockout timings.
* Informs the user about the remaining retries or lockout time on the login page.
* Email notification of blocked attempts.
* Logging of blocked attempts.
* Safelist/Blocklist of IPs and Usernames (Support IP ranges).
* **Sucuri** compatibility.
* **Wordfence** compatibility.
* **XMLRPC** gateway protection.
* **Woocommerce** login page protection.
* **Multi-site** compatibility with extra MU settings.
* **GDPR** compliant.
* **Custom IP origins** support (Cloudflare, Sucuri, etc.)

= Features (Premium Cloud App): =
* **Performance Optimizer** - Brute-force attacks absorbed in the cloud (Up to 100k requests monthly). 
* **Throttling** - Longer lockout intervals each time a hacker/bot tries to login unsuccessfully.
* **Auto Backups of All Data**
* **Intelligent IP Blocking/Unblocking** - Make sure the legitimate IP’s are allowed automatically.
* **Synchronized Lockouts** - Lockouts can be shared between multiple domains.
* **Synchronized Safelist/Blocklist** - Safelist/Blocklist can be shared between multiple domains.
* **Premium Support** - Get answers within 24 hours in our support forum. 
* **Enhanced lockout logs** - A log of lockouts with extra features.
* **CSV Download of IP Data** 
* **Supports IPV6 Ranges For Safelist/Blocklist** 
* **Unlock The Locked Admin** - Easily unlock the locked admin through the cloud.

= Upgrading from the old Limit Login Attempts plugin? =
1. Go to the Plugins section in your site's backend.
1. Remove the Limit Login Attempts plugin.
1. Install the Limit Login Attempts Reloaded plugin.

All your settings will be kept intact!

Many languages are currently supported in the Limit Login Attempts Reloaded plugin but we welcome any additional ones.

Help us bring Limit Login Attempts Reloaded to even more countries.

Translations: Bulgarian, Brazilian Portuguese, Catalan, Chinese (Traditional), Czech, Dutch, Finnish, French, German, Hungarian, Norwegian, Persian, Romanian, Russian, Spanish, Swedish, Turkish

Plugin uses standard actions and filters only.

Based on the original code from Limit Login Attempts plugin by Johan Eenfeldt.

= Branding Guidelines =
Limit Login Attempts Reloaded™ is a trademark of Atlantic Silicon Inc. When writing about the plugin, please make sure to use Reloaded after Limit Login Attempts. Limit Login Attempts is the old plugin.
* Limit Login Attempts Reloaded (correct)
* Limit Login Attempts (incorrect)

== Screenshots ==

1. Login screen after a failed login with remaining retries
2. Lockout login screen
3. Administration interface in WordPress 5.2.1

== Frequently Asked Questions ==

= What do I do if all users get blocked? =

If you are using contemporary hosting, it's likely your site uses a proxy domain service like CloudFlare, Sucuri, Nginx, etc. They replace your user's IP address with their own. If the server where your site runs is not configured properly (this happens a lot) all users will get the same IP address. This also applies to bots and hackers. Therefore, locking one user will lead to locking everybody else out. If the plugin is not using our <a href="https://www.limitloginattempts.com/features/">Cloud App</a>, this can be adjusted using the Trusted IP Origin setting. The cloud service intelligently recognizes the non-standard IP origins and handles them correctly, even if your hosting provider does not.

= What settings should I use In the plugin? =

The settings are explained within the plugin in great detail. If you are unsure, use the default settings as they are the recommended ones.

= Can I share the safelist/blocklist throughout all of my sites?=

By default, you will need to copy and paste the lists to each site manually. For the <a href="https://www.limitloginattempts.com/features/">premium service</a>, sites are grouped within the same private cloud account. Each site within that group can be configured if it shares its lockouts and access lists with other group members. The setting is located in the plugin's interface. The default options are recommended.

= Where can I find answers to my Cloud App related questions? =

Please follow this link: <a href="https://www.limitloginattempts.com/resources/">https://www.limitloginattempts.com/resources/</a>

== Changelog ==

= 2.23.2 =
* Cloud: better unlock UX.
* Litle cleanup.

= 2.23.1 =
* Added infinite scroll for cloud logs.

= 2.23.0 =
* Reduced plugin size by removing obsolete translations.
* Cleaned up the dashboard.
* Cloud: added information about auto/manually-blocked IPs.
* GDPR: added an option to insert a link to a Privacy Policy page via a shortcode, clarified GDPR compliance.

= 2.22.1 =
* IP added to the email subject.

= 2.22.0 =
* Added support of CIDR notation for specifying IP ranges.
* Texts updated.
* Refactoring.

= 2.21.1 =
* Fixed: Uncaught Error: Call to a member function stats()
* Cloud API: added block by country.
* Refactoring.

= 2.21.0 =
* GDPR compliance: IPs obfuscation replaced with a customizable consent message on the login page.
* Cloud API: fixed removing of blocked IPs from the access lists under certain conditions.
* Cloud API: domain for Setup Code is taken from the WordPress settings now.

= 2.20.6 =
* Multisite tab links fixed.

= 2.20.5 =
* Option to show and hide the top-level menu item.

= 2.20.4 =
* Sucuri compatibility verified.
* Wordfence compatibility verified.
* Better menu navigation.
* Timezones fixed for the global chart.

= 2.20.3 =
* More clear wording.
* Cloud API: fixed double submit in the settings form.
* Better displaying of stats.

= 2.20.2 =
* Updated email text.

= 2.20.1 =
* New dashboard more clear stats.

= 2.20.0 =
* New dashboard with simple stats.

= 2.19.2 =
* Texts and links updated.

= 2.19.1 =
* Welcome page.
* Image and text updates.

= 2.19.0 =
* Refactoring.
* Feedback message location fixed.
* Text changes.

= 2.18.0 =
* Cloud API: usage chart added.
* Text changes.

= 2.17.4 =
* Missing jQuery images added.
* PHP 5 compatibility fixed.
* Custom App setup link replaced with setup code.

= 2.17.3 =
* Plugin pages message.

= 2.17.2 =
* Lockout notification refactored.

= 2.17.1 =
* CSS cache issue fixed.
* Notification text updated.

= 2.17.0 =
* Refactoring.
* Email text and notification updated.
* New links in the list of plugins.

= 2.16.0 =
* Custom Apps functionality implemented. More details: https://limitloginattempts.com/app/

= 2.15.2 =
* Alternative method of closing the feedback message.

= 2.15.1 =
* Refactoring.

= 2.15.0 =
* Reset password feature has been removed as unwanted.
* Small refactoring.

= 2.14.0 =
* BuddyPress login error compatibility implemented.
* UltimateMember compatibility implemented.
* A PHP warning fixed.

= 2.13.0 =
* Fixed incompatibility with PHP < 5.6.
* Settings page layout refactored.

= 2.12.3 =
* The feedback message is shown for admins only now, and it can also be closed even if the site has issues with AJAX.

= 2.12.2 =
* Fixed the feedback message not being shown, again.

= 2.12.1 =
* Fixed the feedback message not being shown.

= 2.12.0 =
* Small refactoring.
* get_message() - fixed error notices.
* This is the first time we are asking you for a feedback.

= 2.11.0 =
* Blacklisted usernames can't be registered anymore.

= 2.10.1 =
* Fixed: GDPR compliance option could not be selected on the multisite installations.

= 2.10.0 =
* Debug information has been added for better support.

= 2.9.0 =
* Trusted IP origins option has been added.

= 2.8.1 =
* Extra lockout options are back.

= 2.8.0 =
* The plugin doesn't trust any IP addresses other than _SERVER["REMOTE_ADDR"] anymore. Trusting other IP origins make protection useless b/c they can be easily faked. This new version provides a way of secure IP unlocking for those sites that use a reverse proxy coupled with misconfigurated servers that populate _SERVER["REMOTE_ADDR"] with wrong IPs which leads to mass blocking of users.

= 2.7.4 =
* The lockout alerts can be sent to a configurable email address now.

= 2.7.3 =
* Settings page is moved back to "Settings".

= 2.7.2 =
* Settings are moved to a separate page.
* Fixed: login error message. https://wordpress.org/support/topic/how-to-change-login-error-message/

= 2.7.1 =
* A security issue inherited from the ancestor plugin Limit Login Attempts has been fixed.

= 2.7.0 =
* GDPR compliance implemented.

* Fixed: ip_in_range() loop $ip overrides itself causing invalid results.
https://wordpress.org/support/topic/ip_in_range-loop-ip-overrides-itself-causing-invalid-results/

* Fixed: the plugin was locking out the same IP address multiple times, each with a different port.
https://wordpress.org/support/topic/same-ip-different-port/

= 2.6.3 =
* Added support of Sucuri Website Firewall.

= 2.6.2 =
* Fixed the issue with backslashes in usernames.

= 2.6.1 =
* Plugin returns the 403 Forbidden header after the limit of login attempts via XMLRPC is reached.

* Added support of IP ranges in white/black lists.

* Lockouts now can be released selectively.

* Fixed the issue with encoding of special symbols in email notifications.

= 2.5.0 =
* Added Multi-site Compatibility and additional MU settings. https://wordpress.org/support/topic/multisite-compatibility-47/

= 2.4.0 =
* Usernames and IP addresses can be white-listed and black-listed now. https://wordpress.org/support/topic/banning-specific-usernames/ https://wordpress.org/support/topic/good-831/
* The lockouts log has been inversed. https://wordpress.org/support/topic/inverse-log/

= 2.3.0 =
* IP addresses can be white-listed now. https://wordpress.org/support/topic/legal-user/
* A "Gateway" column is added to the lockouts log. It shows what endpoint an attacker was blocked from. https://wordpress.org/support/topic/xmlrpc-7/
* The "Undefined index: client_type" error is fixed. https://wordpress.org/support/topic/php-notice-when-updating-settings-page/

= 2.2.0 =
* Removed the "Handle cookie login" setting as they are now obsolete.
* Added bruteforce protection against Woocommerce login page attacks. https://wordpress.org/support/topic/how-to-integrate-with-woocommerce-2/
* Added bruteforce protection against XMLRPC attacks. https://wordpress.org/support/topic/xmlrpc-7/

= 2.1.0 =
* The site connection settings are now applied automatically and therefore have been removed from the admin interface.
* Now compatible with PHP 5.2 to support some older WP installations.

= 2.0.0 =
* fixed PHP Warning: Illegal offset type in isset or empty https://wordpress.org/support/topic/limit-login-attempts-generating-php-errors
* fixed the deprecated functions issue
https://wordpress.org/support/topic/using-deprecated-function
* Fixed error with function arguments: https://wordpress.org/support/topic/warning-missing-argument-2-5
* added time stamp to unsuccessful tries on the plugin configuration page.
* fixed .po translation files issue.
* code refactoring and optimization.
