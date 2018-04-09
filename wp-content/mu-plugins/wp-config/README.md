# NYCO WP Config

This "Must Use" Wordpress Plugin autoloads a YAML configuration file for different environments. It also includes script configurations for WP Engine specific environment checking (through `is_wpe()` and `is_wpe_snapshot()`) as well as specific environment variable checking (through a constant `WP_ENV`).

## Usage

### Installation

It uses composer/installers to install it to the mu-plugin directly using Composer. Just run:

```
composer require nyco/wp-config
```

You can also download the package and add it manually to your mu-plugin directory. This package requires the `mustangostang/spyc` YAML parser as a dependency.

The plugin comes in a directory, so you will need to create an mu-plugin autoloader to get it to be used by your WordPress site. [Here is an example of an autoloader in the codex](https://codex.wordpress.org/Must\_Use\_Plugins#Autoloader_Example).

### Configuration

The package comes with a sample config directory `/config-sample`. Copy the directory, place into the `mu-plugins`, and rename to `config`. In the directory you'll find the following files:

`development.php` - This is a sample file that would be required if you set the `WP_ENV` constant to `development`. You can add as many environments as you would like, but you need to set them (see below).

`config.yml` - This file will be automaticall loaded and read for your different environments. For example:

```
development:
    SOME_VAR: someVarHere
```

`SOME_VAR` would be set for your development environment based on the `WP_ENV` constant. It would be available to your Wordpress plugins and themes through `getenv('SOME_VAR)`. If the variable is not set, it will be returned as a blank string. You can add as many environments as you would like, but you need to set them (see below). *Be sure to add the  `config.yml` file to you `.gitignore` so that your keys aren't shared*.

### Setting your environment

Simply add the following to your `wp-config.php` to set your environment variable:

```
putenv('WP_ENV=development'); // The environment will be set to
$_ENV['WP_ENV'] = getenv('WP_ENV');
define('WP_ENV', getenv('WP_ENV'));
```

### Contributing

Clone repository and create feature branch. Make changes and run `composer run lint` to follow the coding specification. `composer run format` can help fix some of the issues.

## About

NYC Opportunity is the [New York City Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity). We are committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. Follow @nycopportunity on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity), [Twitter](https://twitter.com/nycopportunity), [Facebook](https://www.facebook.com/NYCOpportunity/), and [Instagram](https://www.instagram.com/nycopportunity/).
