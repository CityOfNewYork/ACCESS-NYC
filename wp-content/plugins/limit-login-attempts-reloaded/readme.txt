=== Limit Login Attempts Reloaded ===
Contributors: wpchefgadget
Tags: login, security, authentication, Limit Login Attempts, GDPR, brute-force attack, brute force, login abuse, ddos protection
Requires at least: 3.0
Tested up to: 4.9.7
Stable tag: 2.7.1

Reloaded version of the original Limit Login Attempts plugin for Login Protection by a team of WordPress developers. GDPR compliant.

== Description ==

Limit the number of login attempts that possible both through the normal login as well as using the auth cookies.
WordPress by default allows unlimited login attempts either through the login page or by sending special cookies. This allows passwords (or hashes) to be cracked via brute-force relatively easily.
Limit Login Attempts Reloaded blocks an Internet address from making further attempts after a specified limit on retries has been reached, making a brute-force attack difficult or impossible.

Features:

* Limit the number of retry attempts when logging in (per each IP). This is fully customizable.
* Limit the number of attempts to log in using authorization cookies in the same way.
* Informs the user about the remaining retries or lockout time on the login page.
* Optional logging and optional email notification.
* Handles server behind the reverse proxy.
* It is possible to whitelist/blacklist IPs and Usernames.
* Sucuri Website Firewall compatibility.
* **XMLRPC** gateway protection.
* **Woocommerce** login page protection.
* **Multi-site** compatibility with extra MU settings.
* **GDPR** compliant. With this feature turned on, all logged IPs get obfuscated (md5-hashed).

= Upgrading from the old Limit Login Attempts plugin =
1. Go to the Plugins section in your site's backend.
1. Remove the Limit Login Attempts plugin.
1. Install the Limit Login Attempts Reloaded plugin.

All your settings will be kept in tact!

Many languages are currently supported in Limit Login Attempts Reloaded plugin but we welcome any additional ones.
Help us bring Limit Login Attempts Reloaded to even more cultures.

Translations: Bulgarian, Brazilian Portuguese, Catalan, Chinese (Traditional), Czech, Dutch, Finnish, French, German, Hungarian, Norwegian, Persian, Romanian, Russian, Spanish, Swedish, Turkish

Plugin uses standard actions and filters only.

Based on the original code from Limit Login Attemps plugin by Johan Eenfeldt.

== Screenshots ==

1. Loginscreen after a failed login with remaining retries
2. Lockout loginscreen
3. Administration interface in WordPress 4.5.3

== Changelog ==

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