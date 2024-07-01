=== Content Workflow (by Bynder) ===
Contributors: bynder
Donate link: https://www.bynder.com/products/content-workflow/
Tags: structured content, gather content, gathercontent, import, migrate, export, mapping, production, writing, collaboration, platform, connect, link, gather, client, word, production, bynder, content, workflow
Requires at least: 5.6.0
Tested up to: 6.5.4
Stable tag: 1.0.0
License: GPL-2.0+
Requires PHP: 7.0
License URI: https://opensource.org/licenses/GPL-2.0

Quickly transfer structured content to and from your Content Workflow projects and your WordPress site.

== Description ==

Installing our WordPress plugin on your site allows you to quickly perform updates of your content from your Content Workflow account to WordPress as well as push your WordPress content updates back to Content Workflow. Content can be imported as new pages/posts or custom post types, and you can also import your WordPress content back to new Content Workflow items.

The plugin allows you to map each field in your Content Workflow Templates with WordPress fields. This is accomplished by creating a Template Mapping, which allows you to map each field in Content Workflow to various fields in WordPress; title, body content, custom fields, tags, categories, Yoast fields, advanced custom fields, featured images … and many more.

The module currently supports the following features:

* Import content from Content Workflow
* Export content to Content Workflow
* Update content in Wordpress from Content Workflow
* Update content from Wordpress to Content Workflow

### What is Content Workflow?

Content Workflow is an online platform for pulling together, editing, and reviewing website content with your clients and colleagues. It's a reliable alternative to emailing around Word documents and pasting content into your CMS. This plugin replaces that process of copying and pasting content and allows you to bulk import structured content, and then continue to update it in WordPress with a few clicks.

Connecting a powerful content production platform, to a powerful content publishing platform.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `content-workflow` to the `/wp-content/plugins/` directory
2. Activate the Content Workflow plugin through the 'Plugins' menu in WordPress
3. Click on the menu item "Content Workflow"
4. Link your accounts. You will need to enter your Content Workflow account URL (e.g. http://mywebsite.gathercontent.com) and your personal Content Workflow API key. You can find your API key in your [Settings area within Content Workflow](https://gathercontent.com/developers/authentication/).

For more detailed installation instructions please visit our [Help Centre](http://help.gathercontent.com/importing-and-exporting-content#wordpress-integration).

== Third-Party Services ==

This plugin relies on the following third-party services:

1. **Content Workflow**: This service is used for content management and synchronization between your WordPress site and Content Workflow. For more information, please visit [Content Workflow](https://www.bynder.com/en/products/content-workflow/). The [Terms of Service](https://gathercontent.com/legal/terms-of-service) and [Privacy Policy](https://www.bynder.com/en/legal/privacy-policy/) are available for review.

== Frequently Asked Questions ==

= What is the Support page? =
* Under the Content Workflow menu item, you will see a Support page. On this page, you'll find a large textarea filled with technical information about your server, browser, plugin, etc. This information is very useful when debugging, and the Content Workflow support team may ask you for it at some point.

Below the text box is a button that will allow you to simply save all of that information to a .txt file. This allows you to easily deliver it to anyone who needs it.

**Note:** This information contains potentially senstive data. Please be careful with where you post it. Do not post it in the WordPress support forums.

= If you need help =
* Please [visit our support documentation](https://support.bynder.com/hc/en-us/articles/14786938909458-Content-Workflow-Integrations#wordpress-integration).

== Screenshots ==
1. Create Template Mappings to map your Content Workflow Templates to your WordPress content.
2. Mappings allow you to import Content Workflow items as Pages, Posts, Media and various Custom Post Types.
3. Map individual fields to a huge range of places in WordPress.
4. Quickly find your items by entering the bulk-edit view, and using filters and live search.
5. Using the post metabox, you can push and pull your Content Workflow items, and change their status on Content Workflow.
6. Or change the item's Content Workflow status in quick-edit mode.

== Changelog ==
= 1.0.0 =
* Officially supporting component by using the Advanced Custom Fields plugins
* Full rebrand to Content Workflow by Bynder
* Migration from the GatherContent Wordpress plugin (https://wordpress.org/plugins/gathercontent-import/)
