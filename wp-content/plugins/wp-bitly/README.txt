=== WP Bitly ===
Contributors: temeritystudios, chipbennett
Tags: shortlink, bitly, url, shortener, custom domain, social, media, twitter, facebook, share
Requires at least: 4.5
Tested up to: 4.9.4
Requires PHP: 5.2.4
Stable tag:  2.5.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use Bitly generated shortlinks for all your WordPress posts and pages, including custom post types.

== Description ==

Love WordPress? Love Bitly? What if you could access an interface between both? Now, you can. WP-bitly allows WordPress users to quickly and easily generate shortlinks for any page, post or custom post type.

What’s more, these shortlinks may also be embedded using a php function or a WordPress shortcode. No matter the type of site you own (from a personal blog to an ecommerce store and everything in between) WP-bitly makes it easy to share your links as and when you please.

Getting started is easy as pie. Simply install the plugin, visit the settings page, and authorize with your Bitly account. Pick the post types you want shortlinks generated for, and voila! You’re ready to start sharing posts with speed and ease.

After all, texting a shortlink is far simpler and quicker than texting the full URL.

**PS:** *WP Bitly also offers insights into the way in which your links do the rounds. Who’s clicking? Who’s sharing? With WP-bitly, you’ll always know.*

== Installation ==

1. From the *Dashboard* navigate to *Plugins >> Add New*
2. Enter "WP Bitly" in the search field
3. Select *Install Now*, click *OK* and finally *Activate Plugin*
4. This will return you to the WordPress Plugins page. Find WP Bitly in the list and click the *Settings* link to configure.
5. Authenticate with Bitly, select the post types you'd like to use shortlinks with, and you're done!

== Frequently Asked Questions ==

= After installation, do I need to update all my posts for short links to be created? =

No. The first time a shortlink is requested for a particular post, WP Bitly will automatically generate one.

= What happens if I change a posts permalink? =

WP Bitly verifies all shortlink requests against Bitly. If the URL has changed it will see this and request a new shortlink.

= Can I include the shortlink directly in a post? =

The shortcode `[wpbitly]` accepts all the same arguments as the_shortlink(). You can also set a "post_id" directly if you wish.

= How do I include a shortlink using PHP? =

Return a shortlink for the current post:
`wpbitly_shortlink();`

Returns a shortlink for the specified post ID:
`wpbitly_shortlink(42);`

== Screenshots ==

1. Straight forward settings page, authorize the plugin and choose your post types.
2. The new and improved statistics metabox found on any post that has an attached shortlink.

== Upgrade Notice ==

= 2.5.2 =
2.5.x adds ability to regenerate shortlinks, new metabox and fixes a variety of php warnings.

== Changelog ==

= 2.5.2 =
* Fixes various php warnings produced by assuming $post
* Better response handling for wpbitly_get()
= 2.5.0 =
* Adds "Regenerate Shortlink" feature to pages and posts
* Adds chart showing previous 7 days of activity
= 2.4.3 =
* Adds debugging to authorization process
* Adds manual entry of the OAuth token in case automatic authorization fails
= 2.4.1 =
* Backwards compatible with PHP 5.2.4
* Updates styling on settings page
* Updates `wp_generate_shortlink()`
* Adds debug setting back
= 2.4.0 =
* Updated for use with WordPress 4.9.2 and current Bitly Oauth
= 2.3.2 =
* Fixed a typo in `wpbitly_shortlink`
= 2.3.0 =
* Trimmed excess bloat from `wp_get_shortlink()`
* Tightened up error checking in `wp_generate_shortlink()`
= 2.2.6 =
* Fixed bug where shortlinks were generated for any post type regardless of setting
* Added `save_post` back, further testing needed
= 2.2.5 =
* Added the ability to turn debugging on or off, to aid in tracking down issues
* Updated to WordPress coding standards
* Removed `wpbitly_generate_shortlink()` from `save_post`, as filtering `pre_get_shortlink` seems to be adequate
* Temporarily removed admin bar shortlink button (sorry! it's quirky)
= 2.2.3 =
* Replaced internal use of cURL with wp_remote_get
* Fixed a bug where the OAuth token wouldn't update
= 2.0 =
* Updated for WordPress 3.8.1
* Updated Bitly API to V3
= 1.0.1 =
* Fixed bad settings page link in plugin row meta on Manage Plugins page
= 1.0 =
* Updated for WordPress 3.5
* Removed all support for legacy backwards compatibility
* Updated Settings API implementation
* Moved settings from custom settings page to Settings -> Writing
* Enabled shortlink generation for scheduled (future) posts
* Added I18n support.
= 0.2.6 =
* Added support for automatic generation of shortlinks when posts are viewed.
= 0.2.5 =
* Added support for WordPress 3.0 shortlink API
* Added support for custom post types.
= 0.1.0 =
* Initial release of WP Bitly
