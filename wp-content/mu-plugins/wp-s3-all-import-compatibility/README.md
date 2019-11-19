# NYCO WordPress S3 Uploads and WP All Import Compatibility

A developer plugin for WordPress that ensures compatibility between [hummanmade / S3 Uploads](https://github.com/humanmade/S3-Uploads) and [WP All Import](http://www.wpallimport.com/). It disables S3 Uploads while WP All Import is being viewed or used in the admin. This ensures the plugin can operate on the uploads directory normally.

## Installation using [Composer](https://getcomposer.org/)

**$1** This package uses [Composer Installers](https://github.com/composer/installers) to install the package in the **Must Use** plugins directory (*/wp-content/mu-plugins*):

    composer require nyco/wp-s3-all-import-compatibility

*Not using Composer?* Download an archive of the code and drop it into the mu-plugins directory.

**$2** [Create a proxy PHP loader file](https://wordpress.org/support/article/must-use-plugins/#caveats) inside the mu-plugins directory, or [use the one included with the plugin](https://github.com/CityOfNewYork/nyco-wp-s3-all-import-compatibility/blob/master/autoloader-sample.php):

    mv wp-content/mu-plugins/wp-s3-all-import-compatibility/autoloader-sample.php wp-content/mu-plugins/wp-s3-all-import-compatibility.php

## Initialization

The [sample autoloader](https://github.com/CityOfNewYork/nyco-wp-s3-all-import-compatibility/blob/master/autoloader-sample.php) contains the basic code required to initialize the plugin. It will add hooks necessary to deactivate S3 Uploads and reactivate when necessary while using WP All Import.

---

![The Mayor's Office for Economic Opportunity](NYCMOEO_SecondaryBlue256px.png)

[The Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity) (NYC Opportunity) is committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. **Interested in contributing?** See our open positions on [buildwithnyc.github.io](http://buildwithnyc.github.io/). Follow our team on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity) (if you are part of the [@cityofnewyork](https://github.com/CityOfNewYork/) organization) or [browse our work on Github](https://github.com/search?q=nycopportunity).