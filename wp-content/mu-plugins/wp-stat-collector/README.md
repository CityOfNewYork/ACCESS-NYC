# NYCO Stat Collector for WordPress

A developer plugin for WordPress that adds [hooks](https://developer.wordpress.org/plugins/hooks/) to enable the logging of data from the site to a specified MySQL database.

## Installation using [Composer](https://getcomposer.org/)

**$1** This package uses [Composer Installers](https://github.com/composer/installers) to install the package in the **Must Use** plugins directory (*/wp-content/mu-plugins*):

    composer require nyco/wp-stat-collector

*Not using Composer?* Download an archive of the code and drop it into the mu-plugins directory.

**$2** [Create a proxy PHP loader file](https://wordpress.org/support/article/must-use-plugins/#caveats) inside the mu-plugins directory, or [use the one included with the plugin](https://github.com/CityOfNewYork/nyco-wp-stat-collector/blob/master/autoloader-sample.php):

    mv wp-content/mu-plugins/wp-stat-collector/autoloader-sample.php wp-content/mu-plugins/wp-stat-collector.php

## Initialization

The [sample autoloader](https://github.com/CityOfNewYork/nyco-wp-stat-collector/blob/master/autoloader-sample.php) contains the basic code required to initialize the plugin. It will...

- Add the [`statc_register`](#statc_register) with a sample to get started with creating a trigger to write information to your database.
- Add the [`statc_bootstrap`](#statc_bootstrap) with a sample query to create tables in your database for the data to be stored in.
- Require all files containing classes.
- Initialize the [`StatCollector\StatCollector`](https://github.com/CityOfNewYork/nyco-wp-stat-collector/blob/master/Class.php).
- Create an admin settings page under *Settings > Stat Collector* for [configuration](#configuration).

## Configuration

- Host (including port)
- Name
- Username
- Password
- Send Notifications - Wether or not to email the administrator if there is a connection error.

### Notices

- Tables Created - A notice that database tables have been created using the [`statc_bootstrap`](#statc_bootstrap) hook.
- Certificate Authority - A notice that a certificate authority has been found.
- Connection - A notice that a connection can be made using the credetials above.

## SSL

Stat Collector uses the [Amazon Web Services RDS certificate bundle](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/UsingWithRDS.SSL.html) for making MySQL connections over SSL.

## Actions

### statc_register

Hook for internal actions that collect information to write to the DB.

**...args**

- `Class StatCollector` - An instance of [StatCollector](https://github.com/CityOfNewYork/nyco-wp-stat-collector/blob/master/Class.php).

#### Example

    add_action('statc_register', function($statc) {
      add_action('my_action', function($data) use ($statc) {
        if (gettype($data) === 'string') {
          $statc->collect('my_table', [
            'my_data' => $data,
          ]);
        }
      }, $statc->settings->priority, 2);

      return true;
    });

### statc_init

Hook for plugin post-instantiation.

**...args**

- `Class StatCollector` - An instance of [StatCollector](https://github.com/CityOfNewYork/nyco-wp-stat-collector/blob/master/Class.php).

### statc_bootstrap

Hook for bootstrapping the database.

**...args**

- `Class wpdb` - An instance of [wpdb](https://developer.wordpress.org/reference/classes/wpdb/) with a connection to your database.

#### Example

    add_action('statc_bootstrap', function($db) {
      $db->query(
        'CREATE TABLE IF NOT EXISTS my_table (
          id INT(11) NOT NULL AUTO_INCREMENT,
          my_data TEXT DEFAULT NULL,
          PRIMARY KEY(id)
        ) ENGINE=InnoDB'
      );

      return true;
    });

---

![The Mayor's Office for Economic Opportunity](NYCMOEO_SecondaryBlue256px.png)

[The Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity) (NYC Opportunity) is committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. **Interested in contributing?** See our open positions on [buildwithnyc.github.io](http://buildwithnyc.github.io/). Follow our team on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity) (if you are part of the [@cityofnewyork](https://github.com/CityOfNewYork/) organization) or [browse our work on Github](https://github.com/search?q=nycopportunity).