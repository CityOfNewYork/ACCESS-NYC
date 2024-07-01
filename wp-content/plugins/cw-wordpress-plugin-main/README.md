# Content Workflow (by Bynder) - Version 1.0.0 #

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

### 1.0.0 ###

* Officially supporting components by using the Advanced Custom Fields plugins
* Full rebrand to Content Workflow by Bynder
* Migration from the [GatherContent Wordpress plugin](https://wordpress.org/plugins/gathercontent-import/)
