=== GatherContent Plugin ===
Contributors:      gathercontent, mathew-chapman, namshee, jtsternberg, justinsainton
Donate link:       http://www.gathercontent.com
Tags               structured content, gather content, gathercontent, import, migrate, export, mapping, production, writing, collaboration, platform, connect, link, gather, client, word, production
Requires at least: 3.8
Tested up to:      5.2
Stable tag:        3.1.14
License:           GPL-2.0+
License URI:       https://opensource.org/licenses/GPL-2.0

Quickly transfer structured content to and from your GatherContent projects and your WordPress site.

== Description ==

Installing our WordPress plugin on your site allows you to quickly perform updates of your content from your GatherContent account to WordPress as well as push your WordPress content updates back to GatherContent. Content can be imported as new pages/posts or custom post types, and you can also import your WordPress content back to new GatherContent items.

The plugin allows you to map each field in your GatherContent Templates with WordPress fields. This is accomplished by creating a Template Mapping, which allows you to map each field in GatherContent to various fields in WordPress; title, body content, custom fields, tags, categories, Yoast fields, advanced custom fields, featured images … and many more.

The module currently supports the following features:

* Import content from GatherContent
* Export content to GatherContent
* Update content in Wordpress from GatherContent
* Update content from Wordpress to GatherContent

For additional developer documentation, please [review the wiki](https://github.com/gathercontent/wordpress-plugin/wiki).

### What is GatherContent?

GatherContent is an online platform for pulling together, editing, and reviewing website content with your clients and colleagues. It's a reliable alternative to emailing around Word documents and pasting content into your CMS. This plugin replaces that process of copying and pasting content and allows you to bulk import structured content, and then continue to update it in WordPress with a few clicks.

Connecting a powerful content production platform, to a powerful content publishing platform.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gathercontent-import` to the `/wp-content/plugins/` directory
2. Activate the GatherContent plugin through the 'Plugins' menu in WordPress
3. Click on the menu item "GatherContent"
4. Link your accounts. You will need to enter your GatherContent account URL (e.g. http://mywebsite.gathercontent.com) and your personal GatherContent API key. You can find your API key in your [Settings area within GatherContent](https://gathercontent.com/developers/authentication/).

For more detailed installation instructions please visit our [Help Centre](http://help.gathercontent.com/importing-and-exporting-content#wordpress-integration).

== Frequently Asked Questions ==

= What is the Support page? =
* Under the GatherContent menu item, you will see a Support page. On this page, you'll find a large textarea filled with technical information about your server, browser, plugin, etc. This information is very useful when debugging, and the GatherContent support team may ask you for it at some point.

Below the text box is a button that will allow you to simply save all of that information to a .txt file. This allows you to easily deliver it to anyone who needs it.

**Note:** This information contains potentially senstive data. Please be careful with where you post it. Do not post it in the WordPress support forums.

= If you need help =
* Please [visit our support documentation](http://help.gathercontent.com/importing-and-exporting-content#wordpress-integration).

== Screenshots ==
1. Create Template Mappings to map your GatherContent Templates to your WordPress content.
2. Mappings allow you to import GatherContent items as Pages, Posts, Media and various Custom Post Types.
3. Map individual fields to a huge range of places in WordPress.
4. Quickly find your items by entering the bulk-edit view, and using filters and live search.
5. Using the post metabox, you can push and pull your GatherContent items, and change their status on GatherContent.
6. Or change the item's GatherContent status in quick-edit mode.

== Changelog ==

= 3.1.14 =
* Fix yoast integration

= 3.1.13 =
* Fix auth_enabled() returning true for empty vars

= 3.1.12 =
* Remove importing hierarchy

= 3.1.11 =
* Fix for post date not updating
* Allow user to disconnect post from GatherContent Item
* Update to use authenticated file downloads

= 3.1.10 =
* Fix push to GatherContent for new and old editor

= 3.1.9 =
* Fix the 3rd param passed to `Pull::sanitize_post_field()`, which needs to be the entire post data array.
* Updated the help centre links.
* Fixed quoted attributes (like alt text) for the pseudo-shortcodes used for media in the GatherContent content, e.g. `[media-1 align=right linkto=file alt="This will go to the image alt tag"]`
* Allow using new shortcode syntax (like `[media_2-1]`) to include media from multiple media fields in GatherContent mapped to the content or excerpt. The original syntax will continue to work (e.g. `[media-1]`), but will be assumed to be the first media field, and will be the same as using the new syntax, `[media_1-1]`.

= 3.1.8 =
* If mapping does not map a field to the `post_title`, be sure to update title from the GC item name.
* Fix bug with item updated dates not being properly formatted in some languages.

= 3.1.7 =
* Add WPML compatibility shim for properly mapping GatherContent taxonomy terms to translated language taxonomy terms where applicable, and vice-versa. **Note:** If the GC item uses the foreign language term name, then this will need to be unhooked. This can be done via:
```php
if ( class_exists( 'GatherContent\\Importer\\General' ) ) {
	$general     = GatherContent\Importer\General::get_instance();
	$wpml_compat = isset( $general->compatibility_wml ) ? $general->compatibility_wml : null;

	remove_filter( 'gc_new_wp_post_data', array( $wpml_compat, 'maybe_transform_meta_for_wpml' ), 10, 2 );
	remove_filter( 'gc_update_wp_post_data', array( $wpml_compat, 'maybe_transform_meta_for_wpml' ), 10, 2 );
	remove_filter( 'gc_config_taxonomy_field_value_updated', array( $wpml_compat, 'maybe_update_taxonomy_item_value_from_wpml' ), 10, 4 );
}
```

= 3.1.6 =
* Update `\GatherContent\Importer\get_post_by_item_id()` to remove any WPML `WP_Query` filters so the mapped post is properly located.
* Remove `.misc-pub-post-status` class from GC metabod, as it was adding a redundant pin icon.
* Set user-agent when making GatherContent API calls.

= 3.1.5 =
* Update to enable the Yoast SEO focus keyword again (a Yoast SEO plugin update changed the field type).
* Add ACF compatibility shim for transforming ACF checkbox values to/from GatherContent checkbox values.
* Two new filters, `gc_config_pre_meta_field_value_updated` and `gc_config_meta_field_value_updated`.

= 3.1.4 =
* Fix issue where syncing multiple items would not work (only syncing the first). Caused by nested wp-async tasks causing the action name name to be modified and the hooked callbacks not to be called.
* Fixed "Attempt to modify property of non-object" notice.

= 3.1.3 =
* Fix bug where some taxonomy terms were not being set (caused by changes made for [#27](https://github.com/gathercontent/wordpress-plugin/issues/27)).

= 3.1.2 =
* Allow side-loading non-image files/media from GatherContent.

= 3.1.1 =
* Added ability log the async requests in debug mode.
* Removed duplicated abstract method. Fixes "Can't inherit abstract function" error which may occur on some servers.

= 3.1.0 =
* Do not require logged-in cookies for wp-async requests (which performa push/pull operations). Fixes [#27](https://github.com/gathercontent/wordpress-plugin/issues/27).

= 3.0.9 =
* Fix improperly cast object property for php 7 compatibility.

= 3.0.8 =
* Update the error message to indicate user may not have proper permission in GatherContent to view GatherContent Templates/Projects.
* Add "class" and "alt" to whitelisted shortcode attributes for the GatherContent `[media]` shortcode.
* Add the `wp_get_attachment_image()` attributes array to the `gc_content_image` filter.
* Add `gc_admin_enqueue_style` and `gc_admin_enqueue_script` actions.
* Fix issue when BadgeOS is installed. BadgeOS is enqueueing its (old) version of select2 in the entire admin. It is incompatible with the new version, so we need to remove it on our pages.
* Check multiple server variable keys to detect if HTTP authentication is enabled on the site. ([https://wordpress.org/support/topic/import-hangs-at-1/](https://wordpress.org/support/topic/import-hangs-at-1/))
* Fix occasional bug when "Do not import" being selected could cause issues when pushing content back to GatherContent.

= 3.0.7 =
* Improved percentage accuracy, and loader animations with the import/sync process.
* Specific to the "1%" sync error, Now detects if site has HTTP authentication enabled, and provides settings fields for storing authentication credentials. (Plugin sync processes will not work if they are not provided)

= 3.0.6 =
* Improved stability when importing a very large number of items.

= 3.0.5 =
* Add ability to set "Do not change" for WP status updates. Props [@achbed](https://github.com/achbed), [#23](https://github.com/gathercontent/wordpress-plugin/pull/23).

= 3.0.4 =
* Update to complement the 3.0.0.8 release to make sure that the minimum 1.8.3 version of underscore is loaded early so that it works when SCRIPT_DEBUG is disabled.
* Fix bug where GatherContent admin column and metabox would not display for a mapped post-type occasionally (if the mapping was imported, or when it is first created).

= 3.0.3 =
* Fix bug where post-types with`'exclude_from_search' => true` would not be properly connected.
* Add filter, `gathercontent_mapping_post_types`, for ability to filter allowed post-types for mapping.
* Add GatherContent plugin settings link to inline action links on plugin page.

= 3.0.2 =
* Now supports mapping GatherContent hierarchy to WordPress hierarchy for hierarchical post-types (like pages). Default behavior can be overridden with the `gc_map_hierarchy` filter.
* Adds a constant to enable developer debug mode (`GATHERCONTENT_DEBUG_MODE`).
* Give GatherContent selectors IDs and classes which do not conflict with WordPress core UI.
* Add a `gc_pull_complete` and `gc_push_complete` hook which is triggered after all items are asynchronously synced.
* Update support instructions on the Support page.
* Fix a few php notices when failing to fetch a project or template from the GatherContent API.

= 3.0.1 =
* Adds a support page to the GatherContent menu for gathering system information for support requests.
* Adds a developer debug mode for advanced developer debugging.
* Fix typos in a few i18n functions, from `_()` to `__()`.
* Fix possible debug notices when options array is empty.

= 3.0.0.9 =
* Fix bug where a custom taxonomy could be saved in a template mapping but would appear to reset or not be saved.

= 3.0.0.8 =
* Re-register underscore.js script on our admin pages when on older WordPress versions (with older bundled underscore script).

= 3.0.0.7 =
* Fix issue with sideloading images. Proper handling for `WP_Error`.
* Fix "Undefined property" notice.

= 3.0.0.6 =
* Fix conflicts/errors which occur on installations using PHP 5.3.

= 3.0.0.5 =
* Fix conflict with other plugins (notably WooCommerce) using the same script handle for select2, causing conflicts/errors.

= 3.0.0.4 =
* Allow file fields to be mapped to custom fields. Will store an array of WordPress attachment ids, or a single attachment id if the file field from GatherContent only contains a single file.

= 3.0.0.3 =
* Fix issue on PHP 5.4 with using shortand array syntax.

= 3.0.0.2 =
* Fix bug when creating a new mapping and trying to map GatherContent statuses before saving the mapping.

= 3.0.0.1 =
* Fix bug where WordPress pointer script/css was not properly enqueued in some instances.

= 3.0.0 =
* Complete rewrite. Plugin no longer uses the legacy API, and allows mapping templates, and then importing/exporting items via the mapped templates.

= 2.6.40 =
* Update plugin to use Items instead of Pages

= 2.6.3 =
* Better integration with yoast and ACF pro. Map to author. Added post format option

= 2.6.2 =
* Remove inline comments from text content

= 2.6.1 =
* Fix bug for multi site installs

= 2.6.0 =
* Add support for custom tabs feature within GatherContent

= 2.5.0 =
* Import hierarchy from GatherContent. Added publish state dropdown to

= 2.4.1 =
* Integrated a few updates from github and fixed coding standard to match WordPress coding standards

= 2.4.0 =
* Changed how the plugin stores page data to allow a larger amount of pages with larger content

= 2.3.0 =
* Updated GatherContent API requests to match current API version and minor UI updates for WP 3.8

= 2.2.1 =
* Added check to makesure cURL is enabled

= 2.2.0 =
* Reworked pages importing to work via ajax. Should fix problems importing too many fields (`max_input_vars`)

= 2.1.0 =
* Added repeatable field mapping

= 2.0.4 =
* Fixed a bug where tag strings weren't being separated by commas

= 2.0.3 =
* Added an alert when pages have no fields to import

= 2.0.2 =
* Fixed line break issues

= 2.0.1 =
* Fixed errors that were only displaying in WP_DEBUG mode

= 2.0 =
* Complete rewrite of old plugin

== Upgrade Notice ==

= 3.1.10 =
* Fix push to GatherContent for new and old editor

= 3.1.9 =
* Fix the 3rd param passed to `Pull::sanitize_post_field()`, which needs to be the entire post data array.
* Updated the help centre links.
* Fixed quoted attributes (like alt text) for the pseudo-shortcodes used for media in the GatherContent content, e.g. `[media-1 align=right linkto=file alt="This will go to the image alt tag"]`
* Allow using new shortcode syntax (like `[media_2-1]`) to include media from multiple media fields in GatherContent mapped to the content or excerpt. The original syntax will continue to work (e.g. `[media-1]`), but will be assumed to be the first media field, and will be the same as using the new syntax, `[media_1-1]`.

= 3.1.8 =
* If mapping does not map a field to the `post_title`, be sure to update title from the GC item name.
* Fix bug with item updated dates not being properly formatted in some languages.

= 3.1.7 =
* Add WPML compatibility shim for properly mapping GatherContent taxonomy terms to translated language taxonomy terms where applicable, and vice-versa. **Note:** If the GC item uses the foreign language term name, then this will need to be unhooked. This can be done via:
```php
if ( class_exists( 'GatherContent\\Importer\\General' ) ) {
	$general     = GatherContent\Importer\General::get_instance();
	$wpml_compat = isset( $general->compatibility_wml ) ? $general->compatibility_wml : null;

	remove_filter( 'gc_new_wp_post_data', array( $wpml_compat, 'maybe_transform_meta_for_wpml' ), 10, 2 );
	remove_filter( 'gc_update_wp_post_data', array( $wpml_compat, 'maybe_transform_meta_for_wpml' ), 10, 2 );
	remove_filter( 'gc_config_taxonomy_field_value_updated', array( $wpml_compat, 'maybe_update_taxonomy_item_value_from_wpml' ), 10, 4 );
}
```

= 3.1.6 =
* Update `\GatherContent\Importer\get_post_by_item_id()` to remove any WPML `WP_Query` filters so the mapped post is properly located.
* Remove `.misc-pub-post-status` class from GC metabod, as it was adding a redundant pin icon.
* Set user-agent when making GatherContent API calls.

= 3.1.5 =
* Update to enable the Yoast SEO focus keyword again (a Yoast SEO plugin update changed the field type).
* Add ACF compatibility shim for transforming ACF checkbox values to/from GatherContent checkbox values.
* Two new filters, `gc_config_pre_meta_field_value_updated` and `gc_config_meta_field_value_updated`.

= 3.1.4 =
* Fix issue where syncing multiple items would not work (only syncing the first). Caused by nested wp-async tasks causing the action name name to be modified and the hooked callbacks not to be called.
* Fixed "Attempt to modify property of non-object" notice.

= 3.1.3 =
* Fix bug where some taxonomy terms were not being set (caused by changes made for [#27](https://github.com/gathercontent/wordpress-plugin/issues/27)).

= 3.1.2 =
* Allow side-loading non-image files/media from GatherContent.

= 3.1.1 =
* Added ability log the async requests in debug mode.
* Removed duplicated abstract method. Fixes "Can't inherit abstract function" error which may occur on some servers.

= 3.1.0 =
* Do not require logged-in cookies for wp-async requests (which performa push/pull operations). Fixes [#27](https://github.com/gathercontent/wordpress-plugin/issues/27).

= 3.0.9 =
* Fix improperly cast object property for php 7 compatibility.

= 3.0.8 =
* Update the error message to indicate user may not have proper permission in GatherContent to view GatherContent Templates/Projects.
* Add "class" and "alt" to whitelisted shortcode attributes for the GatherContent `[media]` shortcode.
* Add the `wp_get_attachment_image()` attributes array to the `gc_content_image` filter.
* Add `gc_admin_enqueue_style` and `gc_admin_enqueue_script` actions.
* Fix issue when BadgeOS is installed. BadgeOS is enqueueing its (old) version of select2 in the entire admin. It is incompatible with the new version, so we need to remove it on our pages.
* Check multiple server variable keys to detect if HTTP authentication is enabled on the site. ([https://wordpress.org/support/topic/import-hangs-at-1/](https://wordpress.org/support/topic/import-hangs-at-1/))
* Fix occasional bug when "Do not import" being selected could cause issues when pushing content back to GatherContent.

= 3.0.7 =
* Improved percentage accuracy, and loader animations with the import/sync process.
* Detects if site has HTTP authentication enabled, and provides settings fields for storing authentication credentials. (Plugin sync processes will not work if they are not provided)

= 3.0.6 =
* Improved stability when importing a very large number of items.

= 3.0.5 =
* Add ability to set "Do not change" for WP status updates. Props [@achbed](https://github.com/achbed), [#23](https://github.com/gathercontent/wordpress-plugin/pull/23).

= 3.0.4 =
* Update to complement the 3.0.0.8 release to make sure that the minimum 1.8.3 version of underscore is loaded early so that it works when SCRIPT_DEBUG is disabled.
* Fix bug where GatherContent admin column and metabox would not display for a mapped post-type occasionally (if the mapping was imported, or when it is first created).

= 3.0.3 =
* Fix bug where post-types with`'exclude_from_search' => true` would not be properly connected.
* Add filter, `gathercontent_mapping_post_types`, for ability to filter allowed post-types for mapping.
* Add GatherContent plugin settings link to inline action links on plugin page.

= 3.0.2 =
* Now supports mapping GatherContent hierarchy to WordPress hierarchy for hierarchical post-types (like pages). Default behavior can be overridden with the `gc_map_hierarchy` filter.
* Adds a constant to enable developer debug mode (`GATHERCONTENT_DEBUG_MODE`).
* Give GatherContent selectors IDs and classes which do not conflict with WordPress core UI.
* Add a `gc_pull_complete` and `gc_push_complete` hook which is triggered after all items are asynchronously synced.
* Update support instructions on the Support page.
* Fix a few php notices when failing to fetch a project or template from the GatherContent API.

= 3.0.1 =
* Adds a support page to the GatherContent menu for gathering system information for support requests.
* Adds a developer debug mode for advanced developer debugging.
* Fix typos in a few i18n functions, from `_()` to `__()`.
* Fix possible debug notices when options array is empty.

= 3.0.0.9 =
* Fix bug where a custom taxonomy could be saved in a template mapping but would appear to reset or not be saved.

= 3.0.0.8 =
* Re-register underscore.js script on our admin pages when on older WordPress versions (with older bundled underscore script).

= 3.0.0.7 =
* Fix issue with sideloading images. Proper handling for `WP_Error`.
* Fix "Undefined property" notice.

= 3.0.0.6 =
* Fix conflicts/errors which occur on installations using PHP 5.3.
