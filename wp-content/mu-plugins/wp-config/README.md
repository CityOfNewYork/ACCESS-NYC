# NYCO WP Config

A developer plugin for WordPress that sets constants and WordPress Options for an installation through a YAML configuration file and autoloads environment specific configuration scripts.

## Features

* Define constants from a **.yml** file.
* Set WordPress options from the same **.yml** file.
* Optionally encrypt the **.yml** file secrets.
* Autoload default **.php** configuration file.
* Autoload environment specific **.php** configuration file.

## Installation using [Composer](https://getcomposer.org/)

**$1** This package uses [Composer Installers](https://github.com/composer/installers) to install the package in the **Must Use** plugins directory (*/wp-content/mu-plugins*):

    composer require nyco/wp-config

*Not using Composer?* Download an archive of the code and drop it into the mu-plugins directory.

**$2** [Create a proxy PHP loader file](https://wordpress.org/support/article/must-use-plugins/#caveats) inside the mu-plugins directory, or [use the one included with the plugin](https://github.com/CityOfNewYork/wp-config/blob/master/autoloader-sample.php):

    mv wp-content/mu-plugins/wp-config/autoloader-sample.php wp-content/mu-plugins/@config.php

## Usage

### Recommendations

Secure the **config.yml** file and **env.php** described below by A) using the encryption method to encrypt the secrets file and B) not checking either file into your site's source control.

### Configuration

The package comes with a sample config directory **/config-sample**. Copy the directory, place into the **mu-plugins**, and rename it to **config**.

    mv wp-content/mu-plugins/wp-config/config-sample wp-content/mu-plugins/config

In the directory you'll find the following files:

**default.php** - This is the default configuration script for writing global configurations for your site.

**development.php** - This is a sample configuration script that would be required if you set the `WP_ENV` constant to `development`. You can add as many environments as you would like, but you need to set them (see below).

**config.yml** - This file will be automatically loaded and read for your different environments. For example:

    development:
        SOME_VAR: someVarHere

The `SOME_VAR` constant would be set for your development environment. It would be available to your WordPress plugins and themes through `SOME_VAR` or `constant('SOME_VAR')`. You can add as many environments as you would like, but you need to set them (see below). *It is recommended to add the **config.yml** file to your **.gitignore** if you are storing private keys so they aren't committed to source control*.

### Setting your environment

Simply add the following to your **wp-config.php** to set your environment variable:

    $_ENV['WP_ENV'] = 'development';
    define('WP_ENV', $_ENV['WP_ENV']);

### Setting WordPress Options

To set database options in the WordPress Installation, prefix the the variables in the **config.yml** file with `WP_OPTION_`. For example:

    development:
        WP_OPTION_SOME_VAR: someVarHere

### Encryption

The encryption method is optional. To utilize this method, you will need to  generate a secret key to encrypt and decrypt your **config.yml** secrets.

1. Clone or download the repository down and `cd` into the directory.
1. Run `php Secret.php`. This will generate a new secret key in an **env.php** file.
1. Place your **config.yml** from the **mu-plugins/config** directory you created into the same directory and run `php Encrypt.php` to encrypt the file.
1. Add the **env.php** to the **mu-plugins/wp-config** plugin directory in your WordPress installation.
1. Add the **config.yml** file back to the **mu-plugins/config** directory.

*It is recommended to add the **config.yml** and **env.php** file to your **.gitignore** if you are storing private keys so they aren't committed to source control*.

### Hooks

The action `nyco_wp_config_loaded` is fired after the **default.php**, **environment.php**, and **config.yml** file are loaded and variables are set.

### Contributing

Clone repository and create feature branch. Make changes and run `composer run lint` to follow the coding specification. `composer run format` can help fix some of the issues.

---

![The Mayor's Office for Economic Opportunity](NYCMOEO_SecondaryBlue256px.png)

[The Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity) (NYC Opportunity) is committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. **Interested in contributing?** See our open positions on [buildwithnyc.github.io](http://buildwithnyc.github.io/). Follow our team on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity) (if you are part of the [@cityofnewyork](https://github.com/CityOfNewYork/) organization) or [browse our work on Github](https://github.com/search?q=nycopportunity).