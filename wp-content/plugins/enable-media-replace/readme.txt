=== Enable Media Replace ===
Contributors: ShortPixel
Donate link: https://www.paypal.me/resizeImage
Tags: replace, attachment, media, files, replace image, replace jpg, change media, replace media, image, file
Requires at least: 4.2
Tested up to: 5.3
Requires PHP: 5.4
Stable tag: trunk

Easily replace any attached image/file by simply uploading a new file in the Media Library edit view - a real time saver!

== Description ==

**A free, lightweight and easy to use plugin that allows you to seamlessly replace an image or file in your Media Library by uploading a new file in its place. No more deleting, renaming and re-uploading files!
Supported by the friendly team that created <a href="https://wordpress.org/plugins/shortpixel-image-optimiser/" target="_blank">ShortPixel</a>  :)**

#### A real timesaver

Don't you find it tedious and complicated to have to first delete a file and then upload one with the exact same name every time you want to update an image or any uploaded file inside the WordPress media library?

Well, no longer!

Now you'll be able to replace any uploaded file from the media "edit" view, where it should be. Media replacement can be done in one of two ways:

#### It's simple to replace a file

1. Just replace the file. This option requires you to upload a file of the same type as the one you are replacing. The name of the attachment will stay the same no matter what the file you upload is called.
1. Replace the file, use new file name and update all links. If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file will be updated to point to the new file name.

This plugin is very powerful and a must-have for any larger sites built with WordPress. It now also comes with preview of the replaced image!

#### Display file modification time

There is a shortcode available which picks up the file modification date and displays it in a post or a page. The code is:
`[file_modified id=XX format=XXXX]` where the "id" is required and the "format" is optional and defaults to your current WordPress settings for date and time format.

So `[file_modified id=870]` would display the last time the file with ID 870 was updated on your site. To get the ID for a file, check the URL when editing a file in the media library (see screenshot #4)

If you want more control over the format used to display the time, you can use the format option, so `[file_modified id=870 format=Y-m-d]` would display the file modification date but not the time. The format string uses [standard PHP date() formatting tags](http://php.net/manual/en/function.date.php).


#### Compatible and recommended Plugins =

* [ShortPixel Image Optimization](https://wordpress.org/plugins/shortpixel-image-optimiser/) - Enable Media Replace is fully compatible with this plugin. Once enabled, ShortPixel will automatically optimize the images you replace using Enable Media Replace.
* [Resize Image After Upload plugin](https://wordpress.org/plugins/resize-image-after-upload/) - automatically resize images upon upload to save traffic & disk space. Good for SEO and compatible with EMR.
* [Regenerate Thumbnails Advanced](https://wordpress.org/plugins/regenerate-thumbnails-advanced/) - Fast, free and simple to use plugin to regenerate the thumbnails for your site after changing a theme (for example). Supported & maintained by [ShortPixel](https://ShortPixel.com)

== Changelog ==

= 3.3.7 =

Release date: 13th November 2019
* call the hook enable-media-replace-upload-done on both modes
* fix JSON compatibility for hostings that don't have JSON module activated
* Language – 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

= 3.3.6 =

Release date: 5th September 2019
* fix JSON compatibility for hostings that don't have JSON module activated

= 3.3.5 =

Release date: 25th July 2019
* fix Replace button on the MediaLibrary image details popup

= 3.3.4 =

Release date: 23rd July 2019
* compatibility fixes for WP version 4.8 and below
* cache killer

= 3.3.3 =

Release date: 19th July 2019
* Fix error "using $this when not in object context" on some PHP versions

= 3.3.2 =

Release date: 17th July 2019
* Check if medium size !> 400px, display that one, otherwise smallest.
* Fixed: Links not updated when using Advanced Custom Fields
* Fixed: Fails silently when file is too big for upload
* When source file does not exist, show placeholder instead of failed image load
* Fixed: Fatal error when replacing images
* Fixed: Not the right time zone on replace
* Fixed Beaver Builder incompatibility by not allowing replace with rename.
* Fixed: Cannot replace non default Wordpress file types, even those allowed to upload [ Media Library Assistant compat ]
* Fixed: error when trying to remove a file that doesn't exist - because the files are actually on another server

= 3.3.1 =

Release date: 18th June 2019
* Fix error class not found on WPEngine

= 3.3.0 =
* When replacing an image and changing the name, Search / Replace is now also done on the meta_value of postmeta.
* Replace PDF thumbnails too
* Copy title from EXIF
* RTL View incorporated into the CSS
* ‘wp_handle_upload’ filter should be treated as such (and not as action)
* Use wp_attached_file instead of the GUID
* Fix: replace missing file
* Fix: aphostrophe breaking the upload
* Fix: broken "before" image
* Fix: update properly the date
* Fix: errors for non-image items in Media Library
* Fix: empty admin menu item created
* Refactored all the code

= 3.2.9 =
* properly replace thumbnails names in the content when the replaced image has a different aspect ratio, thus the new thumbnails have a different height in the name.

= 3.2.8 =
* fix for failures in link updating when replacing file because of addslashes - use prepared query instead
* replace basename with wp_basename because basename doesn't work well with UTF8

= 3.2.7 =
* Add minimum required php version to run the plugin.
* Security: Prevent direct access to php files.
* Security: Prevent direct access to directories.
* Security: Escape translation strings using `esc_attr__()` and `esc_html__()` functions.
* Fix RTL issues.

= 3.2.6 =
* no more 404 error if no image was selected when trying to replace it
* added preview so you can check the image being replaced and also the image that's being replaced with
* .dat files can be replaced (functionality accidentally removed in the previous version)
* added compatibility with S3 upload plugin
* when an image is replaced the date is also updated

= 3.2.5 =
* remove the leftover setcookie and the plugins recommendations.

= 3.2.4 =
* Fix PDF thumbnails not replaced when replacing a PDF
* Fix not replacing text files with .dat extension

= 3.2.3 =
* disable ShortPixel recommendation on secondary sites of a multisite install when it was network activated.

= 3.2.2 =
* Fixed compatibility with ShortPixel and Resize Image After Upload
* Added ShortPixel links and images, fixed the problem of ShortPixel recommendation not dismissing.

= 3.2.1 =
* Bugfix, typo made metadata changes (thanks GitHub user icecandy!)
* Removed Shortpixel links and images

= 3.2 =
* Tested with WP 4.9.4
* Added Shortpixel link in replace media screen

= 3.1.1 =
* Fixed bug introduced in an earlier version, preventing the updating of URLs on pages/posts if the link did not contain the domain name

= 3.1 =
* Got rid of some pesky old code, and added some better filtering options, thanks to GitHub users speerface, aaemnnosttv, and ururk
* Brand new, shiny code to replace other image sizes in embedded media, thanks to GitHub user ianmjones!
* Tested with WP 4.8

= 3.0.6 =
* Tested with WP 4.7.2
* New PT translations (thanks Pedro Mendonca! https://github.com/mansj/enable-media-replace/commit/b6e63b9a8a3ae46b3a6664bd5bbf19b2beaf9d3f)

= 3.0.5 =
* Tested with WP 4.6.1

= 3.0.4 =
* Fixed typo in .pt translations (https://github.com/mansj/enable-media-replace/pull/18)
* Fixed better error handling in modification date functions (https://github.com/mansj/enable-media-replace/pull/16)
* Tested with WP 4.4.1

= 3.0.3 =
* Scrapped old method of detecting media screen, button to replace media will now show up in more places, yay!
* Made sure the call to get_attached_file() no longer skips filters, in response to several users wishes.
* Suppressed error messages on chmod()
* Added Japanese translation (Thank you, chacomv!)

= 3.0.2 =
* Cleaned up language files
* Added Portuguese translation (Thanks pedro-mendonca!)
* Tested with WP 4.1
* Added missing Swedish translation strings

= 3.0.1 =
* Tiny fix to re-insert the EMR link in the media list view.

= 3.0 =
* Updated for WordPress 4.0
* Now inheriting permissions of the replaced files,  [Thank you Fiwad](https://github.com/fiwad)

= 2.9.7RC1 =
* Moved localization files into their own directory. [Thank you Michael](https://github.com/michael-cannon)
* Moved screenshots into their own directory. [Thank you Michael](https://github.com/michael-cannon)

= 2.9.6 =
* Added fix by Grant K Norwood to address a possible security problem in SQL statements. Thanks Grant!
* Created GitHub repo for this plugin, please feel free to contribute at github.com/mansj/enable-media-replace

= 2.9.5 =
* Bug fix for the short code displaying the modification date of a file
* Updated all database queries in preparation for WP 3.9

= 2.9.4 =
* Bug fix for timezone changes in WordPress
* Minor UI change to inform the user about what actually happens when replacing an image and using a new file name

= 2.9.3 =
* Added call to update_attached_file() which should purge changed files for various CDN and cache plugs. Thanks Dylan Barlett for the suggestion! (http://wordpress.org/support/topic/compatibility-with-w3-total-cache)
* Suppressed possible error in new hook added in 2.9.2

= 2.9.2 =
* Small bug fix
* Added hook for developers to enable purging possible CDN when updating files - thanks rubious for the suggestion!

= 2.9.1 =
* Added Brazilian Portuguese translation, thanks Roger Nobrega!
* Added filter hook for file name creation, thanks to Jonas Lundman for the code!
* Added modification date to the edit attachment screen, thanks to Jonas Lundman for the code!
* Enhanced the deletion method for old file/image thumbnails to never give unnecessary error messages and more accurately delete orphaned thumbs

= 2.9 =
* Added Portuguese translation, thanks Bruno Miguel Bras Silva!
* New edit link from media library
* After uploading, the plugin now takes you back to edit screen instead of library

= 2.8.2 =
* Made another change to the discovery of media context which will hopefully fix a bug in certain cases. Thanks to "Joolee" at the WordPress.org forums!
* Added a new, supposedly better Russian translation from "Vlad".

= 2.8.1 =
* Fixed a small bug which could create error messages on some systems when deleting old image files.

= 2.8 =
* New and safer method for deleting thumbnails when a new image file is uploaded.
* New translations for simplified Chinese (thanks Tunghsiao Liu) and Italian (grazie Marco Chiesi)
* Added method for detecting upload screen to ensure backward compatibility with versions pre 3.5

= 2.7 =
* A couple of changes made to ensure compatibility with WordPress 3.5. Thanks to Elizabeth Powell for the fixes!

= 2.6 =
* New and improved validation of uploaded files, now using WP's own functions for checking file type and extension. Thanks again to my old friend Ulf "Årsta" Härnhammar for keeping us all on our toes! :) This should also hopefully fix the problems people have been having with their installations claiming that perfectly good PDF files are not allowed file types.

= 2.5.2 =
* The "more reliable way" of determining MIME types turned out to be less reliable. Go figure. There seems to be no perfect way of performing a reliable check for MIME-types on an uploaded file that is also truly portable. I have now made checks for the availability of mime_content_type() before using it, using the old method as a fall-back. It is far from beautiful, so if anybody has a better way of doing it, please contact me!

= 2.5.1 =
* Bug fix - there is now a more reliable way of determining file type on your upload so you can upload PDF files without seeing that pesky "File type does not meet security guidelines" message.
* New translation to Danish - thanks to Michael Bering Petersen!

= 2.5 =
* Tested with WordPress 3.2.1
* New translation to German - thanks to Martin Lettner!
* New translation to French - thanks to François Collette!

= 2.4.1 =
* Bug fix for WordPress 3.1 RC. Now properly tested and should be working with 3.1 whenever it finally comes out. :)

= 2.4 =
* Bug fixes, security fixes. Thanks to my old pal Ulf "&Aring;rsta" H&auml;rnhammar for pointing them out!
* New method for uploading avoids going around WP, for greater security.

= 2.3 =
* Lots of code trimmed and enhanced, thanks to Ben ter Stal! Now working properly with Windows systems, better security, optimized loading, and much more.
* Added Dutch translation by Ben ter Stal.

= 2.2 =
* Bug fix, fixed typo in popup.php, thanks to Bill Dennen and others for pointing this out!

= 2.1 =
* New shortcode - display file modification date on your site (see description for more info)
* A couple of bug fixes for final release of 3.0 - Thanks to Jim Isaacs for pointing them out!

= 2.0.1 =
* Added support for SSL admin

= 2.0 =
* Replaced popup with inline navigation when replacing media
* Added instructions in admin link under Media

= 1.4.1 =
* Tested with WordPress 3.0 beta 2

= 1.4 =
* Removed short tags for better compatibility.

= 1.3 =
* Added support for wp_config setting "FORCE_SSL_ADMIN"

= 1.2 =
* Added Russian translation, thanks to Fat Cower.

= 1.1 =
* Minor bugfix, now working with IE8 too!

= 1.0 =
* First stable version of plugin.

== Installation ==

Quick and easy installation:

1. Upload the folder `enable-media-replace` to your plugin directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Done!

== Frequently Asked Questions ==

= What does this plugin actually do? =

This plugin makes it easy to update/replace files that have been uploaded to the WordPress Media Library.

= How does it work? =

A new option will be available in the Edit Media view, called "Replace Media". This is where you can upload a new file to replace the old one.

= I replaced a file, but it didn't change! =

There are two main reasons this would happen.

First, make sure you are not viewing a cached version of the file, especially if you replaced an image. Press "Refresh" in your browser to make sure.

Second, if the file really looks unchanged, make sure WordPress has write permissions to the files in your uploads folder. If you have ever moved your WP installation (maybe when you moved it to a new server), the permissions on your uploaded files are commonly reset so that WordPress no longer has permissions to change the files. If you don't know how to do this, contact your web server operator.

== Screenshots ==

1. The new link in the media library.
2. The replace media-button as seen in the "Edit media" view.
3. The upload options.
4. Get the file ID in the edit file URL

== Wishlist / Coming attractions ==

Do you have suggestions? Feel free to contact ShortPixel <a href="https://shortpixel.com/contact" target="_blank">here</a>


== Contribute ==

Want to help us improve the plugin feel free to submit PRs via GitHub <a href="https://github.com/short-pixel-optimizer/enable-media-replace" target="_blank">here</a>.
