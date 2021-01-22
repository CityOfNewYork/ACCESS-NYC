=== Limit Login Attempts Reloaded ===
Contributors: wpchefgadget
Donate link: https://www.paypal.com/donate?hosted_button_id=FKD4MYFCMNVQQ
Tags: brute force, login, security, firewall, protection
Requires at least: 3.0
Tested up to: 5.6
Stable tag: 2.18.0

Reloaded version of the original Limit Login Attempts plugin for Login Protection by a team of WordPress developers. GDPR compliant.

== Description ==

Limit the number of login attempts that are possible through the normal login as well as XMLRPC, Woocommerce and custom login pages.

WordPress by default allows unlimited login attempts. This can lead to  passwords being easily cracked via brute-force.

Limit Login Attempts Reloaded blocks an Internet address (IP) from making further attempts after a specified limit on retries has been reached, making a brute-force attack difficult or impossible.

> <strong>Limit Login Attempts Reloaded Cloud App</strong><br>
> Enables cloud protection app for Limit Login Attempts Reloaded plugin. It comes with all the great features you'll need to stop hackers and bots from brute-force attacks. The cloud app <a href="https://www.limitloginattempts.com/features/">offers several features</a> including advanced protection out of the box, and the ability for site admins and agencies to sync allow/deny/pass lists across multiple domains. <a href="https://app.limitloginattempts.com/network/create">Click here to activate the cloud app for the best WordPress security plugin now!</a>

https://www.youtube.com/watch?v=IsotthPWCPA

= Features: =
* Limit the number of retry attempts when logging in (per each IP). This is fully customizable.
* Informs the user about the remaining retries or lockout time on the login page.
* Logging and optional email notification.
* It is possible to allow/deny IPs and Usernames.
* Sucuri Website Firewall compatibility.
* **XMLRPC** gateway protection.
* **Woocommerce** login page protection.
* **Multi-site** compatibility with extra MU settings.
* **GDPR** compliant.
* **Custom IP origins** support (Cloudflare, Sucuri, etc.)

= Features (Cloud app): =
* **Outsource the site load** - All calculations and database queries are done in the cloud
* **Throttling** - Longer lockout intervals each time a hacker/bot tries to login unsuccessfully
* **Auto backups of all data**
* **Autofix diverse origin IPs (e.g. Cloudflare)** - Securely trust certain popular IP origins out of the box
* **Synced lockout & deny/pass lists check** - Lockouts can be shared between sites of the same admin
* **Synchronized allow/deny/pass lists** - Allow/Deny/Pass lists can be shared between sites of the same admin
* **Premium forum support** - Get answers within 1-2 business days. 
* **Enhanced lockout logs** - A log of lockouts with extra features

= Upgrading from the old Limit Login Attempts plugin? =
1. Go to the Plugins section in your site's backend.
1. Remove the Limit Login Attempts plugin.
1. Install the Limit Login Attempts Reloaded plugin.

All your settings will be kept in tact!

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

1. Loginscreen after a failed login with remaining retries
2. Lockout loginscreen
3. Administration interface in WordPress 5.2.1

== Frequently Asked Questions ==

= What do I do if all users get blocked? =

If you are using contemporary hosting, it's likely your site uses a proxy domain service like CloudFlare, Sucuri, Nginx, etc. They replace your user's IP address with their own. If the server where your site runs is not configured properly (this happens a lot) all users will get the same IP address. This also applies to bots and hackers. Therefore, locking one user will lead to locking everybody else out. If the plugin is not using our <a href="https://www.limitloginattempts.com/">Cloud App</a>, this can be adjusted using the Trusted IP Origin setting. The cloud service intelligently recognizes the non-standard IP origins and handles them correctly, even if your hosting provider does not.

= What settings should I use In The Plugin? =

The settings are explained within the plugin in great detail. If you are unsure, use the default settings as they are the recommended ones.

= Can I share the allow/deny/pass lists throughout all of my sites?=

By default, you will need to copy and paste the lists to each site manually. For the <a href="https://www.limitloginattempts.com/features/">premium service</a>, sites are grouped within the same private cloud account. Each site within that group can be configured if it shares its lockouts and access lists with other group members. The setting is located in the plugin's interface. The default options are recommended.

= Where can I find answers to my Cloud App related questions? =

Please follow this link: <a href="https://www.limitloginattempts.com/resources/">https://www.limitloginattempts.com/resources/</a>

== Changelog ==

= 2.18.0 =
* 
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