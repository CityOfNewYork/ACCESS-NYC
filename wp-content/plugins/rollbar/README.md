# Rollbar for WordPress
[![Plugin Version](https://img.shields.io/wordpress/plugin/v/rollbar.svg)](https://wordpress.org/plugins/rollbar/) [![WordPress Version Compatibility](https://img.shields.io/wordpress/v/rollbar.svg)](https://wordpress.org/plugins/rollbar/) [![Downloads](https://img.shields.io/wordpress/plugin/dt/rollbar.svg)](https://wordpress.org/plugins/rollbar/) [![Rating](https://img.shields.io/wordpress/plugin/r/rollbar.svg)](https://wordpress.org/plugins/rollbar/)

Rollbar full-stack error tracking for WordPress

The full documentation is available [here](https://docs.rollbar.com/v1.0.0/docs/wordpress).

## Description
Rollbar collects errors that happen in your application, notifies you, and analyzes them so you can debug and fix them.

This plugin integrates Rollbar into your WordPress installation.

Find out [how Rollbar can help you decrease development and maintenance costs](https://rollbar.com/features/).

See [real companies improving their development workflow thanks to Rollbar](https://rollbar.com/customers/).

[Official WordPress.org Plugin](https://wordpress.org/plugins/rollbar/)

## Installation

### Through [WordPress Plugin directory](https://wordpress.org/plugins/rollbar/)

The easiest way to install the plugin is from the WordPress Plugin directory. If you have an existing WordPress installation and you want to add Rollbar:

1. In your WordPress administration panel go to `Plugins` → `Add New`.
2. Search for "Rollbar" and find `Rollbar` by Rollbar in the search results.
3. Click `Install Now` next to the `Rollbar` plugin.
4. In `Plugins` → `Installed plugins` find `Rollbar` and click `Activate` underneath.
5. Log into your [Rollbar account dashboard](https://rollbar.com/login/):
   1. Go to `Settings` → `Project Access Tokens`.
   2. Copy the token value under `post_client_item` and `post_server_item`.
6. In WordPress, navigate to `Settings` → `Rollbar`:
   1. Enable `PHP error logging` and/or `Javascript error logging` depending on your needs.
   2. Paste the tokens you copied in step 7 in `Access Token` section.
   3. Provide the name of your environment in `Environment`. By default, the environment will be taken from `WP_ENV` environment variable if it's set otherwise it's blank. We recommend to fill this out either with `development` or `production`.
   4. Pick a minimum logging level. Only errors at that or higher level will be reported. For reference: [PHP Manual: Predefined Error Constants](http://php.net/manual/en/errorfunc.constants.php).
   5. Click `Save Changes`.

**Warning**: This installation method might not be suitable for complex WordPress projects. The plugin installed this way will be self-contained and include all of the required dependencies for itself and `rollbar/rollbar-php` library. In complex projects, this might lead to version conflicts between dependencies and other plugins/packages. If this is an issue in your project, we recommend the "Advanced" installation method. For more information why this might be important for you, read [Using Composer with WordPress](https://roots.io/using-composer-with-wordpress/).

### Through [wpackagist](https://wpackagist.org/) (if you manage your project with Composer) *recommended*

This is a recommended way to install Rollbar plugin for advanced projects. This way ensures the plugin and all of its' dependencies are managed by Composer.

1. If your WordPress project is not managed with Composer yet, we suggest looking into upgrading your WordPress: [Using Composer with WordPress](https://roots.io/using-composer-with-wordpress/).
2. In your `composer.json` add `wpackagist-plugin/rollbar` to your `require` section, i.e.:
```json
  "require": {
    "php": ">=5.5",
    ...,
    "wpackagist-plugin/rollbar": "*"
  }
```
3. Issue command `composer install` in the root directory of your WordPress project.
4. Go to step #4 above.

## Help / Support

If you run into any issues, please email us at [support@rollbar.com](mailto:support@rollbar.com)

For bug reports, please [open an issue on GitHub](https://github.com/rollbar/rollbar-php-wordpress/issues/new).

## Special thanks

The original author of this package is [@flowdee](https://twitter.com/flowdee/). This is a fork and continuation of his efforts.

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Verify your commit passes the code standards enforced by [Codacy](https://www.codacy.com).
5. Create new Pull Request

## Testing

The following is Mac/Linux only - Windows is not supported.

Before you run tests, provide test database credentials in `phpunit.env` (you can copy `phpunit.env.dist`, removing the comment in the first line).  Then start your `mysqld` service.

Tests are in `tests`; to run them, do `composer test`. To fix code style issues, do `composer fix`.

## Tagging

This is only for contributors with committer access:

1. Bump the plugin version.
    1. Bump the plugin version in `readme.txt` under `Stable tag`.
    2. Add record in the `Changelog` section of the `readme.txt`.
    3. Add record in the `Upgrade Notice` section of the `readme.txt`.
    4. Bump the plugin version in `rollbar-php-wordpress.php` in the `Version:` comment.
    5. Bump the plugin version in `src/Plugin.php` in the `\Rollbar\Wordpress\Plugin::VERSION` constant.
    5. Add and commit the changes you made to bump the plugin version: `git add readme.txt rollbar-php-wordpress.php src/Plugin.php && git commit -m"Bump version to v[version number]"`
    6. Bump versions of the JS and CSS files versions in Settings.php class to force refresh of those assets on users' installations.
    7. `git push origin master`
2. Tag the new version from the `master` branch and push upstream with `git tag v[version number] && git push --tags`.
3. Publish a new release on [GitHub](https://github.com/rollbar/rollbar-php-wordpress/releases).
4. Update the WordPress Plugin Directory Subversion Repository.
    1. Fetch the latest contents of Subversion repo with `svn update`.
    2. Remove the contents of `trunk/` with `rm -Rf trunk`.
    3. Update the contents of `trunk/` with a clone of the tag you created in step 2.
        2. `git clone https://github.com/rollbar/rollbar-php-wordpress.git trunk`
        3. `cd trunk && git checkout tags/v[version number] && cd ..`
        4. `rm -Rf trunk/.git`
        5. `svn add trunk --force`
        6. `svn commit -m"Sync with GitHub repo"`
    4. Create the Subversion tag: `svn copy https://plugins.svn.wordpress.org/rollbar/trunk https://plugins.svn.wordpress.org/rollbar/tags/[version number] -m"Tag [version number]"`. Notice the version number in Subversion doesn't include the "v" prefix.

## Disclaimer

This plugin is a community-driven contribution. All rights reserved to Rollbar. 

[![Rollbar](https://d26gfdfi90p7cf.cloudfront.net/rollbar-badge.144534.o.png)](https://rollbar.com/)
