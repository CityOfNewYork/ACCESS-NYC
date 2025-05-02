=== Content Workflow (by Bynder) ===
Contributors: bynder
Donate link: https://www.bynder.com/products/content-workflow/
Tags: structured content, gather content, gathercontent, import, migrate, export, mapping, production, writing, collaboration, platform, connect, link, gather, client, word, production, bynder, content, workflow
Requires at least: 5.6.0
Tested up to: 6.6.0
Stable tag: 1.0.5
License: GPL-2.0+
Requires PHP: 7.0
License URI: https://opensource.org/licenses/GPL-2.0

Quickly transfer structured content to and from your Content Workflow projects and your WordPress site.

== Description ==

The Content Workflow by Bynder (formerly GatherContent) plugin allows you quickly import content from your Content Workflow account into your WordPress site. The plugin supports pages, posts, custom post types and the ACF plugin for advanced mapping of components.
Set up Template Mappings between existing templates in Content Workflow and posts and pages within WordPress, to create  seamless export and import of your content.

The Content Workflow by Bynder plugin also supports the automation of your content workflow statuses to save time and reduce errors when migrating your content:
- filter existing content in your account by workflow status
- map your Content Workflow status to WordPress status
- automatically update status of a content item within Content Workflow on import

### What is Content Workflow?

Content Workflow is the new standard for structured content creation. Create content templates and workflows to manage the entire process without juggling documents, chasing feedback, or copying and pasting into your publishing tools.

Get everyone on the same page with a transparent content production process that promotes accountability and efficiency without jeopardizing quality. Work together in real-time to get work done—fast.

Kiss goodbye to un-approved content finding its way in front of customers. A transparent workflow ensures that content stays up to date and accurate—avoiding regulatory risk or off-brand content.

Accelerate content creation and unlock the potential of AI Assist to generate, edit, translate, and polish drafts effortlessly. With robust safeguards for additional peace of mind, you can recognize AI-generated content, manage user access, and always be in control.

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
1. Create Template mappings between Content Workflow and your WordPress posts and pages
2. Automate workflow status updates during import
3. Filter and refine to content you wish to import
4. Bulk import your content at scale

== Changelog ==

= 1.0.5 =
* Fixes an issue where plain text fields in a component were being imported as rich text fields
* Fixes an issue where the plugin couldn't map Content Workflow fields to Taxonomy/Terms

= 1.0.4 =
* Adds support for PHP versions 8 to 8.4.*

= 1.0.3 =
* Fixed an issue where creating a new row in an ACF PRO repeatable field doesn't create the field on Content Workflow.

= 1.0.1 =
* Updating the plugin listing page to have new assets and an improved description.
* Fixing small typo within the plugin API stopping the plugin from loading.

= 1.0.0 =
* Officially supporting component by using the Advanced Custom Fields plugins
* Full rebrand to Content Workflow by Bynder
* Migration from the GatherContent Wordpress plugin (https://wordpress.org/plugins/gathercontent-import/)
