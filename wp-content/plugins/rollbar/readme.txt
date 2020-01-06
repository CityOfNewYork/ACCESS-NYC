=== Rollbar ===
Contributors: arturmoczulski
Tags: rollbar, full stack, error, tracking, error tracking, error reporting, reporting, debug
Requires at least: 3.5.1
Tested up to: 5.1.1
Stable tag: 2.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Official Rollbar full-stack error tracking for WordPress supported by Rollbar, Inc.

== Description ==
Rollbar collects errors that happen in your application, notifies you, and analyzes them so you can debug and fix them.

This plugin integrates Rollbar into your WordPress installation.

Find out [how Rollbar can help you decrease development and maintenance costs](https://rollbar.com/features/).

See [real companies improving their development workflow thanks to Rollbar](https://rollbar.com/customers/).

= Features =

*   PHP & Javascript error logging
*   Define an environment for each single WordPress installation or Multisite blog
*   Specify your desired logging level
*   Regular updates and improvements!

> <strong>Please note</strong><br>
> In order to use this plugin, a [Rollbar account](https://rollbar.com/) is required.

= Support =

* Browse [issue tracker](https://github.com/rollbar/rollbar-php-wordpress/issues) on GitHub and report new issues
* If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)
* For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php-wordpress/issues/new).

= You like it? =

You can at least support the plugin development and rate it up!

= Disclaimer =

This plugin is a community-driven contribution. All rights reserved to [Rollbar](https://rollbar.com/).

== Installation ==

The installation and configuration of the plugin are as simple as it can be.

= Through [WordPress Plugin directory](https://wordpress.org/plugins/rollbar/) =

The easiest way to install the plugin is from the WordPress Plugin directory. If you have an existing WordPress installation and you want to add Rollbar:

1. In your WordPress administration panel go to `Plugins` → `Add New`.
2. Search for "Rollbar" and find `Rollbar` by Rollbar in the search results.
3. Click `Install Now` next to the `Rollbar` plugin.
4. In `Plugins` → `Installed plugins` find `Rollbar` and click `activate` underneath.
5. Log into your [Rollbar account dashboard](https://rollbar.com/login/).
6. Go to `Settings` → `Project Access Tokens`.
7. Copy the token value under `post_client_item` and `post_server_item`.
8. Navigate to `Tools` → `Rollbar`.
9. Enable `PHP error logging` and/or `Javascript error logging` depending on your needs.
10. Paste the tokens you copied in step 7 in `Access Token` section.
11. Provide the name of your environment in `Environment`. By default, the environment will be taken from `WP_ENV` environment variable if it's set otherwise it's blank. We recommend to fill this out either with `development` or `production`.
12. Pick a minimum logging level. Only errors at that or higher level will be reported. For reference: [PHP Manual: Predefined Error Constants](http://php.net/manual/en/errorfunc.constants.php).
13. Click `Save Changes`.

**Warning**: This installation method might not be suitable for complex WordPress projects. The plugin installed this way will be self-contained and include all of the required dependencies for itself and `rollbar/rollbar-php` library. In complex projects, this might lead to version conflicts between dependencies and other plugins/packages. If this is an issue in your project, we recommend the "Advanced" installation method. For more information why this might be important for you, read [Using Composer with WordPress]().

= Through [wpackagist](https://wpackagist.org/) (if you manage your project with Composer) *recommended* =

This is a recommended way to install Rollbar plugin for advanced projects. This way ensures the plugin and all of its' dependencies are managed by Composer.

1. If your WordPress project is not managed with Composer yet, we suggest looking into upgrading your WordPress: [Using Composer with WordPress]().
2. In your `composer.json` add `wpackagist-plugin/rollbar` to your `require` section, i.e.:
```
  "require": {
    "php": ">=5.5",
    ...,
    "wpackagist-plugin/rollbar": "*"
  }
```
3. Issue command `composer install` in the root directory of your WordPress project.
4. In `Plugins` → `Installed plugins` find `Rollbar` and click `Activate` underneath.
5. Log into your [Rollbar account dashboard](https://rollbar.com/login/).
6. Go to `Settings` → `Project Access Tokens`.
7. Copy the token value under `post_client_item` and `post_server_item`.
8. Navigate to `Tools` → `Rollbar`.
9. Enable `PHP error logging` and/or `Javascript error logging` depending on your needs.
10. Paste the tokens you copied in step 7 in `Access Token` section.
11. Provide the name of your environment in `Environment`. By default, the environment will be taken from `WP_ENV` environment variable if it's set otherwise it's blank.
12. Pick a minimum logging level. Only errors at that or higher level will be reported. For reference: [PHP Manual: Predefined Error Constants](http://php.net/manual/en/errorfunc.constants.php).
13. Click `Save Changes`.

== Frequently Asked Questions ==

= Multisite supported? =
Yes of course. Additionally, you can assign different environments to each of your blogs.

= I have a complex WordPress project and use composer for managing dependencies. Is your plugin composer friendly? =
Yes. It's actually the recommended method of installation.

== Screenshots ==

1. Settings page

== Changelog ==

= Version 2.6.1 (December 27th 2019) =
* fix(initPhpLogging): Moving fetch settings to before settings check. (https://github.com/rollbar/rollbar-php-wordpress/pull/84)

= Version 2.5.1 (February 20th 2019) =
* Fixed a call to Rollbar\Wordpress\Defaults for enableMustUsePlugin (https://github.com/rollbar/rollbar-php-wordpress/pull/75)

= Version 2.5.0 (February 19th 2019) =
* Moved Rollbar initialization from `plugins_loaded` hook to the invocation of the main plugin file (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* Added support for running the plugin as a Must-Use plugin (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* Added `Enable as a Must-Use plugin` settings (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* UI improvements

= Version 2.4.10 (February 5th 2019) =
* Added support for ROLLBAR_ACCESS_TOKEN constant and respecting the ROLLBAR_ACCESS_TOKEN environment variable (https://github.com/rollbar/rollbar-php-wordpress/issues/72)
* Fixed tests
* Updated dependencies

= Version 2.4.9 (January 24th 2019) =
* Fix for issue #69 (https://github.com/rollbar/rollbar-php-wordpress/issues/69)

= Version 2.4.8 (January 17th 2019) =
* Update rollbar-php to v1.7.4

= Version 2.4.7 (August 14th 2018) =
* Update rollbar-php to v1.6.2

= Version 2.4.6 (August 13th 2018) =
* Configuration option custom_data_method doesn’t exist in Rollbar (https://github.com/rollbar/rollbar-php-wordpress/issues/66)

= Version 2.4.5 (August 7th 2018) =
* Update rollbar-php to v1.6.1
* Remove mentions of IRC channel from README.md and readme.txt

= Version 2.4.4 (June 18th 2018) =
* Update rollbar-php to v1.5.3

= Version 2.4.3 (June 11th 2018) =
* Update rollbar-php to v1.5.2
* Use rollbar-php:v1.5.2 new defaults methods to handle restoring default settings.

= Version 2.4.2 (25th May 2018) =
* Fixed the plugin not always respecting the boolean true settings (https://github.com/rollbar/rollbar-php-wordpress/issues/58)

= Version 2.4.1 (19th May 2018) =
* Updated rollbar-php dependency to v1.5.1

= Version 2.4.0 (17th May 2018) =
* Added capture_ip, capture_email and capture_username to the config options.
* Fixed populating config options from the database to the plugin for boolean values.
* Updated rollbar-php dependency to v1.5.0

= Version 2.3.1 (10th April 2018) =
* Fixed a bug in strict PHP setups (https://github.com/rollbar/rollbar-php-wordpress/issues/44)

= Version 2.3.0 (5th April 2018) =
* Added `rollbar_plugin_settings` filter
* Added majority of Rollbar PHP config options to the User Interface.
* Moved the settings from Tools -> Rollbar to Settings -> Rollbar

= Version 2.2.0 (4th December 2017) =
* Fixed the logging level to correctly inlude errors from specified level and up.
* Changed the default logging level setting.
* Added instructions on tagging the repo to the README.md file.
* Added tests for logging level.
* Set up a PHPUnit test suite.
* Add rollbar_js_config filter for JS config data customization.
* Updated dependencies.

= Version 2.1.2 (11th October 2017) =
* Use the default rest route instead of permalink /wp-json
* Dynamically build the Rollbar JS snippet URL

= Version 2.1.1 (11th October 2017) =
* Fixed location of the Rollbar JS snippet

= Version 2.1.0 (11th October 2017) =
* Added "Send test message to Rollbar" button
* Fixed the plugin's name inconsistency between Wordpress plugin directory and composer.

= Version 2.0.1 (6th October 2017) =
* Fixed RollbarJsHelper class loading bug in src/Plugin.php (https://github.com/rollbar/rollbar-php-wordpress/issues/23)

= Version 2.0.0 (9th September 2017) =
* Added support for the WP_ENV environment variable
* Organized the code into namespaces
* Moved helper functions into static methods
* Updated Rollbar PHP library
* Included dependencies to make the plugin self-contained when installing through WP plugin directory
* Rewrote readme files

= Version 1.0.3 (12th August 2016) =
* Updated rollbar php lib to latest v0.18.2
* Added .pot translation file
* Removed WP.org assets from plugin folder

= Version 1.0.2 (28th March 2016) =
* Updated rollbar js lib
* Added escaping for setting values

= Version 1.0.0 (4th November 2015) =
* Initial release!

== Upgrade Notice ==

= Version 2.6.1 (December 27th 2019) =
* fix(initPhpLogging): Moving fetch settings to before settings check. (https://github.com/rollbar/rollbar-php-wordpress/pull/84)

= Version 2.5.1 (February 20th 2019) =
* Fixed a call to Rollbar\Wordpress\Defaults for enableMustUsePlugin (https://github.com/rollbar/rollbar-php-wordpress/pull/75)

= Version 2.5.0 (February 19th 2019) =
* Moved Rollbar initialization from `plugins_loaded` hook to the invocation of the main plugin file (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* Added support for running the plugin as a Must-Use plugin (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* Added `Enable as a Must-Use plugin` settings (https://github.com/rollbar/rollbar-php-wordpress/issues/73)
* UI improvements

= Version 2.4.10 (February 5th 2019) =
* Added support for ROLLBAR_ACCESS_TOKEN constant and respecting the ROLLBAR_ACCESS_TOKEN environment variable (https://github.com/rollbar/rollbar-php-wordpress/issues/72)
* Fixed tests
* Updated dependencies

= Version 2.4.9 (January 24th 2019) =
* Fix for issue #69 (https://github.com/rollbar/rollbar-php-wordpress/issues/69)

= Version 2.4.8 (January 17th 2019) =
* Update rollbar-php to v1.7.4

= Version 2.4.7 (August 14th 2018) =
* Update rollbar-php to v1.6.2

= Version 2.4.6 (August 13th 2018) =
* Configuration option custom_data_method doesn’t exist in Rollbar (https://github.com/rollbar/rollbar-php-wordpress/issues/66)

= Version 2.4.5 (August 7th 2018) =
* Update rollbar-php to v1.6.1
* Remove mentions of IRC channel from README.md and readme.txt

= Version 2.4.4 (June 18th 2018) =
* Update rollbar-php to v1.5.3

= Version 2.4.3 (June 11th 2018) =
* Update rollbar-php to v1.5.2
* Use rollbar-php:v1.5.2 new defaults methods to handle restoring default settings.

= Version 2.4.2 (25th May 2018) =
* Fixed the plugin not always respecting the boolean true settings (https://github.com/rollbar/rollbar-php-wordpress/issues/58)

= Version 2.4.1 (19th May 2018) =
* Updated rollbar-php dependency to v1.5.1

= Version 2.4.0 (5th April 2018) =
* Added capture_ip, capture_email and capture_username to the config options.
* Fixed populating config options from the database to the plugin for boolean values.
* Updated rollbar-php dependency to v1.5.0

= Version 2.3.1 (10th April 2018) =
* Fixed a bug in strict PHP setups (https://github.com/rollbar/rollbar-php-wordpress/issues/44)

= Version 2.3.0 (5th April 2018) =
* Added `rollbar_plugin_settings` filter
* Added majority of Rollbar PHP config options to the User Interface.
* Moved the settings from Tools -> Rollbar to Settings -> Rollbar

= Version 2.2.0 (4th December 2017) =
* Fixed the logging level to correctly inlude errors from specified level and up.
* Changed the default logging level setting.
* Added instructions on tagging the repo to the README.md file.
* Added tests for logging level.
* Set up a PHPUnit test suite.
* Add rollbar_js_config filter for JS config data customization.
* Updated dependencies.

= Version 2.1.2 (11th October 2017) =
* Use the default rest route instead of permalink /wp-json
* Dynamically build the Rollbar JS snippet URL

= Version 2.1.1 (11th October 2017) =
* Fixed location of the Rollbar JS snippet

= Version 2.1.0 (11th October 2017) =
* Added "Send test message to Rollbar" button
* Fixed the plugin's name inconsistency between Wordpress plugin directory and composer.

= Version 2.0.1 (6th October 2017) =
* Fixed RollbarJsHelper class loading bug in src/Plugin.php (https://github.com/rollbar/rollbar-php-wordpress/issues/23)

= Version 2.0.0 (9th September 2017) =
* Added support for the WP_ENV environment variable
* Organized the code into namespaces
* Moved helper functions into static methods
* Updated Rollbar PHP library
* Included dependencies to make the plugin self-contained when installing through WP plugin directory
* Rewrote readme files
* Made the package composer friendly with composer.json

= Version 1.0.3 (12th August 2016) =
* Updated rollbar php lib to latest v0.18.2
* Added .pot translation file
* Removed WP.org assets from plugin folder

= Version 1.0.2 (28th March 2016) =
* Updated rollbar js lib
* Added escaping for setting values

= Version 1.0.0 (4th November 2015) =
* Initial release!