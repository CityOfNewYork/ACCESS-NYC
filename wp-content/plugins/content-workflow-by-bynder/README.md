# Content Workflow (by Bynder) - Version 1.0.5 #

This plugin allows you to transfer content from your Content Workflow projects into your WordPress site and vice-versa.

## Description ##

Installing our WordPress plugin on your site allows you to quickly perform updates of your content from your Content
Workflow account to WordPress as well as push your WordPress content updates back to Content Workflow. Content can be
imported as new pages/posts or custom post types, and you can also import your WordPress content back to new Content
Workflow items.

The plugin allows you to map each field in your Content Workflow Templates with WordPress fields. This is accomplished
by creating a Template Mapping, which allows you to map each field in Content Workflow to various fields in WordPress;
title, body content, custom fields, tags, categories, Yoast fields, advanced custom fields, featured images … and many
more.

The module currently supports the following features:

* Import content from Content Workflow
* Export content to Content Workflow
* Update content in Wordpress from Content Workflow
* Update content from Wordpress to Content Workflow

### What is Content Workflow?

Content Workflow is an online platform for pulling together, editing, and reviewing website content with your clients
and colleagues. It's a reliable alternative to emailing around Word documents and pasting content into your CMS. This
plugin replaces that process of copying and pasting content and allows you to bulk import structured content, and then
continue to update it in WordPress with a few clicks.

Connecting a powerful content production platform, to a powerful content publishing platform.

## Installation ##

This section describes how to install the plugin and get it working.

### Downloading the plugin ###

1. To download the plugin, click the green "Code" button on the top right of this page.
2. Then click "Download ZIP".

For more information on how to download from GitHub please visit
their [help page](https://docs.github.com/en/repositories/working-with-files/using-files/downloading-source-code-archives).

### Installing the plugin ###

1. Upload `content-workflow` to the `/wp-content/plugins/` directory
2. Activate the Content Workflow plugin through the 'Plugins' menu in WordPress
3. Click on the menu item "Content Workflow"
4. Link your accounts. You will need to enter your Content Workflow account URL (
   e.g. http://mywebsite.gathercontent.com) and your personal Content Workflow API key. You can find your API key in
   your [Settings area within Content Workflow](https://gathercontent.com/developers/authentication/).

For more detailed installation instructions, please visit
our [Help Centre](http://help.gathercontent.com/importing-and-exporting-content#wordpress-integration).

## Support ##

If you need help,
Please [visit our support documentation](http://help.gathercontent.com/importing-and-exporting-content#wordpress-integration).

Also note, in your WordPress dashboard, under the Content Workflow menu item, you will see a Support page. On this page,
you'll find a large textarea filled with technical information about your server, browser, plugin, etc. This information
is very useful when debugging, and the Content Workflow support team may ask you for it at some point.

Below the text box is a button that will allow you to simply save all of that information to a .txt file. This allows
you to easily deliver it to anyone who needs it.

**However**, this information contains potentially senstive data. Please be careful with where you post it. Do not post
it in the WordPress support forums.

### Third-Party Services ###

This plugin relies on the following third-party services:

1. **Content Workflow**: This service is used for content management and synchronization between your WordPress site and
   Content Workflow. For more information, please
   visit [Content Workflow](https://www.bynder.com/en/products/content-workflow/).
   The [Terms of Service](https://gathercontent.com/legal/terms-of-service)
   and [Privacy Policy](https://www.bynder.com/en/legal/privacy-policy/) are available for review.

## Changelog

### 1.0.5 ###
* Fixes an issue where plain text fields in a component were being imported as rich text fields
* Fixes an issue where the plugin couldn't map Content Workflow fields to Taxonomy/Terms

### 1.0.4 ###
* Adds support for PHP versions 8 to 8.4.*

### 1.0.3 ###
* Fixed an issue where creating a new row in an ACF PRO repeatable field doesn't create the field on Content Workflow.

### 1.0.1 ###
* Updating the plugin listing page to have new assets and an improved description.
* Fixing small typo within the plugin API stopping the plugin from loading.

### 1.0.0 ###

* Officially supporting components by using the Advanced Custom Fields plugins
* Full rebrand to Content Workflow by Bynder
* Migration from the [GatherContent Wordpress plugin](https://wordpress.org/plugins/gathercontent-import/)

# Local Development
If you're running a WordPress instance locally via [Docker Base](https://github.com/Bynder/gathercontent-docker-base),
this repo will be mounted into the plugins directory, meaning any changes you make can be seen instantly.

Along with that, the entire WordPress installation will be mapped to a `wp-data` directory in this repo, from here you
can easily make changes.

## Logging
To enable logging, simply go to `/wp-data/wp-config.php` and add the following:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

This will allow critical errors to be logged, and will be viewable at `/wp-data/wp-content/debug.log`.

If you're using this plugins [logging](includes/classes/debug.php), you will find the logs at
`/wp-data/wp-content/gathercontent-debug.log`.

## Adding new plugins
If you need to add a new plugin locally, you'll often run into PHP upload limits which are a pain to adjust. Instead,
you can simply download the plugin, unzip it, and move it into the plugins directory at `/wp-data/wp-content/plugins`.
From there, you can activate the plugin with WordPress.
