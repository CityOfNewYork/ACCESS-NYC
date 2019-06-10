# NYCO WP Config

This "Must Use" Wordpress Plugin sets environment variables for a WordPress installation through a YAML configuration file and autoloads environment specific scripts.

## Features
* Set environment variables from a `.yml` file.
* Set WordPress options from the same `.yml` file.
* Optionally encrypt the `.yml` file secrets.
* Autoload environment specific `php` file (like a `functions.php` file specific to an environment).

## Usage

### Installation

It uses composer/installers to install it to the mu-plugin directly using Composer. Just run:


    composer require nyco/wp-config


You can also download the package and add it manually to your mu-plugin directory. Create a must-use plugin loader in the root of the `mu-plugins` directory. and include the following;

    if (file_exists(WPMU_PLUGIN_DIR . '/wp-config/Config.php')) {
      require_once ABSPATH . '/vendor/mustangostang/spyc/Spyc.php'; // loads the mustangostang/spyc dependency
      require_once WPMU_PLUGIN_DIR . '/wp-config/Config.php'; // loads the Config class

      new Nyco\WpConfig\Config\Config(); // instantiates the class and sets environment variables
    }

### Requirements

This package requires the `mustangostang/spyc` YAML parser and the `illuminate/encryption` as dependencies. It will expect these to already be loaded so it is recommended to have Composer dependencies defined in the root of the WordPress installation.

The plugin comes in a directory, so you will need to create an mu-plugin autoloader to get it to be used by your WordPress installation. [Here is an example of an autoloader in the codex](https://codex.wordpress.org/Must\_Use\_Plugins#Autoloader_Example).

### Recommendations

Secure the `config.yml` file and `env.php` described below by A) using the encryption method to encrypt the secrets file and B) not checking either file into your site's source control.

### Configuration

The package comes with a sample config directory `/config-sample`. Copy the directory, place into the `mu-plugins`, and rename it to `config`. In the directory you'll find the following files:

`development.php` - This is a sample file that would be required if you set the `WP_ENV` constant to `development`. You can add as many environments as you would like, but you need to set them (see below).

`config.yml` - This file will be automatically loaded and read for your different environments. For example:

    development:
        SOME_VAR: someVarHere

`SOME_VAR` would be set for your development environment based on the `WP_ENV` constant. It would be available to your WordPress plugins and themes through `getenv('some_var')` (note the lowercase). If the variable is not set, it will be returned as a blank string. You can add as many environments as you would like, but you need to set them (see below). *It is recommended to add the `config.yml` file to your `.gitignore` if you are storing private keys so they aren't committed to source control*.

### Setting your environment

Simply add the following to your `wp-config.php` to set your environment variable:

    putenv('WP_ENV=development'); // The environment will be set to
    $_ENV['WP_ENV'] = getenv('WP_ENV');
    define('WP_ENV', getenv('WP_ENV'));

### Setting WordPress Options
To set database options in the WordPress Installation, prefix the the variables in the `config.yml` file with `WP_OPTION_`. For example:

    development:
        WP_OPTION_SOME_VAR: someVarHere

The variable will be set to the environment w/o the `WP_OPTION_` prefix so `WP_OPTION_SOME_VAR` would be available as `getenv('some_var')`.

### Encryption
The encryption method is optional. To utilize this method, you will need to  generate a secret key to encrypt and decrypt your `config.yml` secrets.

1. Clone or download the repository down and `cd` into the directory.
1. Run `php Secret.php`. This will generate a new secret key in an `env.php` file.
1. Place your `config.yml` from the `mu-plugins/config` directory you created into the same directory and run `php Encrypt.php` to encrypt the file.
1. Add the `env.php` to the `mu-plugins/wp-config` plugin directory in your WordPress installation.
1. Add the `config.yml` file back to the `mu-plugins/config` directory.

*It is recommended to add the `config.yml` and `env.php` file to your `.gitignore` if you are storing private keys so they aren't committed to source control*.

### Contributing

Clone repository and create feature branch. Make changes and run `composer run lint` to follow the coding specification. `composer run format` can help fix some of the issues.

# About NYCO

NYC Opportunity is the [New York City Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity). We are committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. Follow @nycopportunity on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity), [Twitter](https://twitter.com/nycopportunity), [Facebook](https://www.facebook.com/NYCOpportunity/), and [Instagram](https://www.instagram.com/nycopportunity/).
