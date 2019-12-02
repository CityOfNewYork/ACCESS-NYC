# NYCO WP Open Data Transients

This WordPress Plugin will provide an interface in the WP Admin to save saving external Open Data as WordPress transients and a developer API to interact with them. You can read more about the [WordPress Transients API](https://codex.wordpress.org/Transients_API) in the Codex.

## Features
* Admin Interface (Settings > Open Data Transients) for adding new transients and adding an Open Data token.
* Transients can be updated manually in the Admin, but they are set to expire weekly (using the WordPress constant `WEEK_IN_SECONDS`).
* Developer API for getting the transient data, and updating it automatically if it is expired.

## Usage

### Installation

It uses `composer/installers` to install it to the plugin directly using Composer. Just run:

    composer require nyco/wp-open-data-transients

You can also download the package and add it manually to your plugin directory.

### App Token

A token will be sent in the header (`X-App-Token`) to the Open Data endpoint to authenticate your application for for saving data. This can be set in the same admin or as an environment variable `$_ENV['OPEN_DATA_APP_TOKEN']`. The [NYCO WP Config](https://github.com/cityofnewyork/nyco-wp-config) plugin can be used to manage environment variables.

### Saved Transients

Transients can be saved by adding a valid name (letters and underscores only) and valid url. Clicking "Save Transient" will save the transient and cache the request. Once it is saved, the developer API can be used to get the data. The developer API also exposes the save and set methods used by the admin interface.

Typing the name of an already saved transient and clicking "Save Transient" will update the transient data cache.

### Developer API

Once the plugin is installed, you can reference the name space to use it;

    use NYCO\Transients as Transients;

#### Save

The transient needs to be saved with a valid name (letters and underscores only) and valid url before it can be exposed to the [`set`](#set) or [`get`](#get) methods.

    Transients::save('your_transient_name', 'https://opendata.com/endpoint');

#### Set

Uses WordPress' `wp_remote_get` and `set_transient` methods to retrieve the saved endpoint and save the response body. It will expect a JSON response and will be saved as a PHP Object.

    Transients::set('your_transient_name');

#### Get

Returns a saved transient. If the transient is empty (expired) it will use the [`set`](#set) method to re-cache it.

    Transients::get('your_transient_name');

### Potential Improvements

This plugin only provides a small interface for saving external Open Data as WordPress Transients and external data and is not a fully fledged transient manager.

- [ ] Method to delete saved transients.
- [ ] Option to set the expiration of transients.

# About NYCO

NYC Opportunity is the [New York City Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity). We are committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. Follow @nycopportunity on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity), [Twitter](https://twitter.com/nycopportunity), [Facebook](https://www.facebook.com/NYCOpportunity/), and [Instagram](https://www.instagram.com/nycopportunity/).
