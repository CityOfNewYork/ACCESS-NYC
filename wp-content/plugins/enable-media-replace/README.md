# Enable Media Replace

This plugin allows you to replace a file in your media library by uploading a new file in its place. No more deleting, renaming and re-uploading files!

## A real timesaver

Don't you find it tedious and complicated to have to first delete a file and then upload one with the exact same name every time you want to update an image or any uploaded file inside the WordPress media library?

Well, no longer!

Now you'll be able to replace any uploaded file from the media "edit" view, where it should be. Media replacement can be done in one of two ways:

## It's simple to replace a file

1. Just replace the file. This option requires you to upload a file of the same type as the one you are replacing. The name of the attachment will stay the same no matter what the file you upload is called.
1. Replace the file, use new file name and update all links. If you check this option, the name and type of the file you are about to upload will replace the old file. All links pointing to the current file will be updated to point to the new file name.

This plugin is very powerful and a must-have for any larger sites built with WordPress. 

## Display file modification time

There is a shortcode available which picks up the file modification date and displays it in a post or a page. The code is:
`[file_modified id=XX format=XXXX]` where the "id" is required and the "format" is optional and defaults to your current WordPress settings for date and time format. 

So `[file_modified id=870]` would display the last time the file with ID 870 was updated on your site. To get the ID for a file, check the URL when editing a file in the media library (see screenshot #4)

If you want more control over the format used to display the time, you can use the format option, so `[file_modified id=870 format=Y-m-d]` would display the file modification date but not the time. The format string uses [standard PHP date() formatting tags](http://php.net/manual/en/function.date.php). 

***

See [Enable Media Replace](http://wordpress.org/plugins/enable-media-replace/) at WordPress.org for more information.
