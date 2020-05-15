---
title: "Setup"
description: "All about setting up Timber with your theme."
menu:
  main:
    parent: "getting-started"
---

## Installation

### Via WordPress.org (easy)

You can grab the all-things-included plugin at [WordPress.org](http://wordpress.org/plugins/timber-library/) either through the WordPress site or through the Plugins menu in the backend. Then skip ahead to [using the starter theme](#use-the-starter-theme).

### Via GitHub (for developers)

The GitHub version of Timber requires [Composer](https://getcomposer.org/download/). If you'd prefer one-click installation, you should use the [WordPress.org](https://wordpress.org/plugins/timber-library/) version.

Run the following Composer command from within your theme's root directory:

```shell
composer require timber/timber
```

If you're using the [starter theme](https://github.com/timber/starter-theme), a `composer.json` file is already included, so you can run the following command instead:

```shell
composer install
```

If you're not using the starter theme or your theme is not setup to pull in Composer’s autoload file, you will need to add the following at the top of your `functions.php` file to load the Composer dependencies (including Timber), and initialize Timber.

**functions.php**

```php
<?php
require_once( __DIR__ . '/vendor/autoload.php' );
$timber = new Timber\Timber();
```

## Use the starter theme

The [starter theme](https://github.com/timber/starter-theme) is for starting a project from scratch. You can also use Timber in an existing theme.

### Navigate to your WordPress themes directory

Like where twentyeleven and twentytwelve live. The Timber Starter Theme will live at the same level.

	/wp-content/themes	/twentyeleven
						/twentytwelve
						/timber-starter-theme

You should now have:

	/wp-content/themes/timber-starter-theme

You should probably **rename** this to something better.

### 1. Activate Timber

It will be in `wp-admin/plugins.php`.

### 2. Select your theme in WordPress

Make sure you select the Timber-enabled theme **after** you activate the plugin. The theme will crash unless Timber is activated. Use the **timber-starter-theme** theme from the step above (or whatever you renamed it to).

### 3. Let’s write our theme!

Continue ahead in [part 2 about Theming](https://timber.github.io/docs/getting-started/theming/).
