=== Enable Media Replace ===
Contributors: mungobbq
Tags: admin, attachment, media, files
Requires at least: 4.0
Tested up to: 4.7.2
Stable tag: trunk

Enables replacing attachment files by simply uploading a new file in the media library edit view.

== Description ==

This plugin allows you to replace a file in your media library by uploading a new file in its place. No more deleting, renaming and re-uploading files!

#### A real timesaver

Don't you find it tedious and complicated to have to first delete a file and then upload one with the exact same name every time you want to update an image or any uploaded file inside the WordPress media library?

Well, no longer!

Now you'll be able to replace any uploaded file from the media "edit" view, where it should be. Media replacement can be done in one of two ways:

#### It's simple to replace a file

1. Just replace the file. This option requires you to upload a file of the same type as the one you are replacing. The name of the attachment will stay the same no matter what the file you upload is called.
1. Replace the file, use new file name and update all links. If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file will be updated to point to the new file name.

This plugin is very powerful and a must-have for any larger sites built with WordPress. 

#### Display file modification time

There is a shortcode available which picks up the file modification date and displays it in a post or a page. The code is:
`[file_modified id=XX format=XXXX]` where the "id" is required and the "format" is optional and defaults to your current WordPress settings for date and time format. 

So `[file_modified id=870]` would display the last time the file with ID 870 was updated on your site. To get the ID for a file, check the URL when editing a file in the media library (see screenshot #4)

If you want more control over the format used to display the time, you can use the format option, so `[file_modified id=870 format=Y-m-d]` would display the file modification date but not the time. The format string uses [standard PHP date() formatting tags](http://php.net/manual/en/function.date.php). 

== Changelog ==

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
* Moved localization files into own directory. [Thank you Michael](https://github.com/michael-cannon)
* Moved screensots into own directory. [Thank you Michael](https://github.com/michael-cannon)

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
* Added method for detecting upload screen to ensure backwards compatibility with versions pre 3.5

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

== Wishlist / Coming attractons ==

Do you have suggestions? Feel free to contact me at mans@mansjonasson.se

