# WP Crontrol #

Contributors: johnbillion, scompt  
Tags: cron, wp-cron, crontrol, debug  
Requires at least: 4.1  
Tested up to: 5.4  
Stable tag: 1.8.5  
Requires PHP: 5.3  

WP Crontrol lets you view and control what's happening in the WP-Cron system.

## Description ##

WP Crontrol lets you view and control what's happening in the WP-Cron system. From the admin screens you can:

 * View all cron events along with their arguments, recurrence, callback functions, and when they are next due.
 * Edit, delete, and immediately run any cron events.
 * Add new cron events.
 * Bulk delete cron events.
 * Add, edit, and remove custom cron schedules.

The admin screen will show you a warning message if your cron system doesn't appear to be working (for example if your server can't connect to itself to fire scheduled cron events).

### Usage ###

1. Go to the `Tools → Cron Events` menu to manage cron events.
2. Go to the `Settings → Cron Schedules` menu to manage cron schedules.

## Frequently Asked Questions ##

### What's the use of adding new cron schedules? ###

Cron schedules are used by WordPress and plugins for scheduling events to be executed at regular intervals. Intervals must be provided by the WordPress core or a plugin in order to be used. As an example, many backup plugins provide support for periodic backups. In order to do a weekly backup, a weekly cron schedule must be entered into WP Crontrol first and then a backup plugin can take advantage of it as an interval.

### How do I create a new cron event? ###

There are two steps to getting a functioning cron event that executes regularly. The first step is telling WordPress about the hook. This is the part that WP Crontrol was created to provide. The second step is calling a function when your hook is executed.

*Step One: Adding the hook*

In the Tools → Cron Events admin panel, click on the "Add Cron Event" tab and enter the details of the hook. You're best off having a hookname that conforms to normal PHP variable naming conventions. The event schedule is how often your hook will be executed. If you don't see a good interval, then add one in the Settings → Cron Schedules admin panel.

*Step Two: Writing the function*

This part takes place in PHP code (for example, in the `functions.php` file from your theme). To execute your hook, WordPress runs an action. For this reason, we need to tell WordPress which function to execute when this action is run. The following line accomplishes that:

	add_action( 'my_hookname', 'my_function' );

The next step is to write your function. Here's a simple example:

	function my_function() {
		wp_mail( 'hello@example.com', 'WP Crontrol', 'WP Crontrol rocks!' );
	}

### How do I create a new PHP cron event? ###

In the Tools → Cron Events admin panel, click on the "Add PHP Cron Event" tab. In the form that appears, enter the schedule and next run time in the boxes. The event schedule is how often your event will be executed. If you don't see a good interval, then add one in the Settings → Cron Schedules admin panel. In the "Hook code" area, enter the PHP code that should be run when your cron event is executed. You don't need to provide the PHP opening tag (`<?php`).

### Which users can manage cron events and schedules? ###

Only users with the `manage_options` capability can manage cron events and schedules. By default, only Administrators have this capability.

### Which users can manage PHP cron events? Is this dangerous? ###

Only users with the `edit_files` capability can manage PHP cron events. This means if a user cannot edit files on the site (eg. through the Plugin Editor or Theme Editor) then they cannot edit or add a PHP cron event. By default, only Administrators have this capability, and with Multisite enabled only Super Admins have this capability.

If file editing has been disabled via the `DISALLOW_FILE_MODS` or `DISALLOW_FILE_EDIT` configuration constants then no user will have the `edit_files` capability, which means editing or adding a PHP cron event will not be permitted.

Therefore, the user access level required to execute arbitrary PHP code does not change with WP Crontrol activated.

### Are any WP-CLI commands available? ###

The cron commands which were previously included in WP Crontrol are now part of WP-CLI (since 0.16), so this plugin no longer provides any WP-CLI commands. See `wp help cron` for more info.

## Screenshots ##

1. New cron events can be added, modified, deleted, and executed<br>![](.wordpress-org/screenshot-1.png)

2. New cron schedules can be added, giving plugin developers more options when scheduling events<br>![](.wordpress-org/screenshot-2.png)

## Changelog ##

### 1.8.5 ###

* Fix an issue with the tabs in 1.8.4.

### 1.8.4 ###

* Add a warning message if the default timezone has been changed. <a href="https://github.com/johnbillion/wp-crontrol/wiki/PHP-default-timezone-is-not-set-to-UTC">More information</a>.
* Fixed string being passed to `strtotime()` function when the `Now` option is chosen when adding or editing an event.

### 1.8.3 ###

* Fix the editing of events that aren't currently listed on the first page of results.

### 1.8.2 ###

* Bypass the duplicate event check when manually running an event. This allows an event to manually run even if it's due within ten minutes or if it's overdue.
* Force only one event to fire when manually running a cron event.
* Introduce polling of the events list in order to show a warning when the event listing screen is out of date.
* Add a warning for cron schedules which are shorter than `WP_CRON_LOCK_TIMEOUT`.
* Add the Site Health check event to the list of persistent core hooks.


### 1.8.1 ###

* Fix the bottom bulk action menu on the event listing screen.
* Make the timezone more prominent when adding or editing a cron event.

### 1.8.0 ###

* Searching and pagination for cron events
* Ability to delete all cron events with a given hook
* More accurate response messages when managing events (in WordPress 5.1+)
* Visual warnings for events without actions, and PHP events with syntax errors
* Timezone-related clarifications and fixes
* A more unified UI
* Modernised codebase


### 1.7.1 ###

* Correct the PHP.net URL for the `strtotime()` reference.

### 1.7.0 ###

* Remove the `date` and `time` inputs and replace with a couple of preset options and a plain text field. Fixes #24 .
* Ensure the schedule name is always correct when multiple schedules exist with the same interval. Add error handling. Fixes #25.
* Re-introduce the display of the current site time.
* Use a more appropriate HTTP response code for unauthorised request errors.


### 1.6.2 ###

* Remove the ability to delete a PHP cron event if the user cannot edit files.
* Remove the `Edit` link for PHP cron events when the user cannot edit the event.
* Avoid a PHP notice due to an undefined variable when adding a new cron event.

### 1.6.1 ###

* Fix a potential fatal error on the cron events listing screen.

### 1.6 ###

* Introduce bulk deletion of cron events. Yay!
* Show the schedule name instead of the schedule interval next to each event.
* Add core's new `delete_expired_transients` event to the list of core events.
* Don't allow custom cron schedules to be deleted if they're in use.
* Add links between the Events and Schedules admin screens.
* Add syntax highlighting to the PHP code editor for a PHP cron event.
* Styling fixes for events with many arguments or long arguments.
* Improvements to help text.
* Remove usage of `create_function()`.
* Fix some translator comments, improve i18n, improve coding standards.

### 1.5.0 ###

* Show the hooked actions for each cron event.
* Don't show the `Delete` link for core's built-in cron events, as they get re-populated immediately.
* Correct the success message after adding or editing PHP cron events.
* Correct the translations directory name.

### 1.4 ###

- Switch to requiring cron event times to be input using the site's local timezone instead of UTC.
- Add the ability for a PHP cron event to be given an optional display name.
- Better UX for users who cannot edit files and therefore cannot add or edit PHP cron events.
- Terminology and i18n improvements.


### 1.3.1 ###

- Display a less scary looking message when `DISABLE_WP_CRON` is defined.
- Correct the example code for cron event arguments.


### 1.3 ###

- Improvements to the UI.
- More error detection when testing WP-Cron functionality.
- Improve the capability checks for single site and multisite.
- Lots of escaping and sanitising.
- Fix various issues with multiple events with the same hook name.
- Removed the WP-CLI commands, as these have now been added to WP-CLI core (see `wp help cron` for more info)


### 1.2.3 ###

- Tweaks to i18n and date and args formatting
- Properly escape the `crontrol_message` query var (props Julio Potier)


### 1.2.2 ###

- Added `wp crontrol run-event` and `wp crontrol delete-event` WP-CLI commands
- Clarify language regarding hooks/entries/events


### 1.2.1 ###

- Correctly display the local time when listing cron events
- Remove a PHP notice
- Pass the WP-Cron spawn check through the same filter as the actual spawner


### 1.2 ###

- Added support for [WP-CLI](http://wp-cli.org/)
- Removed some PHP4 code that's no longer relevant


### 1.1 ###

- Bug fixes for running cron events and adding cron schedules
- Added a cron spawn test to check for errors when spawning cron
- Various small tweaks
- WordPress 3.4 compatibility


### 1.0 ###

- Input of PHP code for cron events
- Non-repeating cron events
- Handles cron events with arguments


### 0.3 ###

- Internationalization
- Editing/deleting/execution of cron events
- More text, status messages, etc.
- Allow a user to enter a schedule event in a human manner
- Looks better on WordPress 2.5


### 0.2 ###

- Fully documented the code.
- Fixed the bug that the activate action wouldn't be run if the plugin wasn't in a subdirectory.
- Now will play nicely in case any other plugins specify additional cron schedules.
- Minor cosmetic fixes.


### 0.1 ###

- Super basic, look at what's in WP-Cron functionality.

