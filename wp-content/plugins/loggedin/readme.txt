=== Loggedin - Limit Active Logins ===
Contributors: joelcj91,duckdev
Tags: active logins, loggedin, login, logout, limit active logins, login limit, concurrent logins
Donate link: https://paypal.me/JoelCJ
Requires at least: 4.0
Tested up to: 5.5
Stable tag: 1.3.1
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Light weight plugin to limit number of active logins from an account. Set maximum number of concurrent logins a user can have from multiple places.

== Description ==

By default in WordPress users can login using one account from **unlimited** devices/browsers at a time. This is not good for everyone, seriously! With this plugin you can easily set a limit for no. of active logins a user can have.


> #### Loggedin 🔒 Features and Advantages
>
> - **Set maximum no. of active logins for a user**.<br />
> - **Block new logins when the login limit is reached.**<br />
> - **Allow new logins while logging out from other devices when the limit is reached.**<br />
> - **Force logout users from admin.**<br />
> - Prevent users from sharing their account.<br />
> - Useful for membership sites (for others too).<br />
> - No complex settings. Just one optional field to set the limit.<br />
> - Super Light weight.<br />
> - Filter to bypass login limit for certain users or roles.<br />
> - Completely free to use with lifetime updates.<br />
> - Follows best WordPress coding standards.<br />
>
> [Installation](https://wordpress.org/plugins/loggedin/installation/) | [Support](http://wordpress.org/support/plugin/loggedin/) | [Screenshots](https://wordpress.org/plugins/loggedin/screenshots/)

Please contribute to the plugin development in [GitHub](https://github.com/joel-james/LoggedIn).

**🔐 Important Notice**

Even if the user is closing the browser without logging out, their login session exists for period of time. So this will also considered as an active login.

== Installation ==


= Installing the plugin - Simple =
1. In your WordPress admin panel, go to *Plugins > New Plugin*, search for **LoggedIn** and click "*Install now*"
2. Alternatively, download the plugin and upload the contents of `loggedin.zip` to your plugins directory, which usually is `/wp-content/plugins/`.
3. Activate the plugin
4. Go to General tab under WordPress Settings menu.
5. Find the "Maximum Active Logins" option and select the maximum number of active logins for a user account.


= Missing something? =
If you would like to have an additional feature for this plugin, [let me know](https://duckdev.com/support/)

== Frequently Asked Questions ==

= How can I set the limit, and where? 🤔 =

This plugin does not have a seperate settings page. But we have one configural settings to let you set the login limit.

1. Go to `Settings` page in admin dashboard.
2. Scroll down to see the section `🔐 Loggedin`.
3. Set the maximum number of active logins a user can have in `Maximum Active Logins` option.

= Can I somehow allow new logins when the limit is reached? 🤔 =

You can forcefully logout the user from other devices and allow new login.

1. Go to `Settings` page in admin dashboard.
2. Scroll down to see the section `🔐 Loggedin`.
3. Select the `Login Logic` as `Allow`.

= Can I block the new logins when the limit is reached? 🤔 =

You block the new logins when the user is logged in from maximum no. of devices according to the limit you set.

1. Go to `Settings` page in admin dashboard.
2. Scroll down to see the section `🔐 Loggedin`.
3. Select the `Login Logic` as `Block`.
4. Now user will have to wait for the other login sessions to expire before login from new device.

= How long a login session exist? How long the user needs to wait for new login? 🤔 =

That depends. If the “Remember Me” box is checked while login, WordPress will keep the user logged in for 14 days by default. If “Remember Me” is not checked, 2 days will be the active login session time.

You can change that period using, auth_cookie_expiration filter.

<pre lang="php">
function loggedin_auth_cookie_expiration( $expire ) {
    // Allow for a month.
    return MONTH_IN_SECONDS;
}

add_filter( 'auth_cookie_expiration', 'loggedin_auth_cookie_expiration' );
</pre>

= How can I forcefully logout a user from all devices? 🤔 =

You can forcefully logout a user from all the devices he has logged into. Get his WordPress user ID and,

1. Go to `Settings` page in admin dashboard.
2. Scroll down to see the section `🔐 Loggedin`.
3. Enter user ID of the user you would like to logout.
4. Click `Force Logout`.

= Can I bypass this limit for certain users or roles? 🤔 =

Yes, of course. But this time you are going to add few lines of code. Don't worry. Just copy+paste this code in your theme's `functions.php` file or in custom plugin:

<pre lang="php">
function loggedin_bypass_users( $bypass, $user_id ) {
    
    // Enter the user IDs to bypass.
    $allowed_users = array( 1, 2, 3, 4, 5 );

    return in_array( $user_id, $allowed_users );
}

add_filter( 'loggedin_bypass', 'loggedin_bypass_users', 10, 2 );
</pre>

Or if you want to bypass this for certain roles:

<pre lang="php">
function loggedin_bypass_roles( $prevent, $user_id ) {

    // Array of roles to bypass.
    $allowed_roles = array( 'administrator', 'editor' );

    $user = get_user_by( 'id', $user_id );

    $roles = ! empty( $user->roles ) ? $user->roles : array();

    return ! empty( array_intersect( $roles, $whitelist ) );
}

add_filter( 'loggedin_bypass', 'loggedin_bypass_roles', 10, 2 );
</pre>


== Other Notes ==

= 🐛 Bug Reports =

Bug reports are always welcome - [report here](https://duckdev.com/support/).


== Screenshots ==

1. **Settings** - Set maximum no. of active logins for a user account.


== Changelog ==

= 1.3.1 (19/09/2020) =

**👌 Improvements**

* Support ajax logins - Thanks [Carlos Faria](https://github.com/cfaria).

= 1.3.0 (28/08/2020) =

**👌 Improvements**

* Improved "Allow" logic to check only after password check.

= 1.2.0 (07/06/2019) =

**📦 New**

* Added ability to choose login logic.

= 1.1.0 (06/06/2019) =

**📦 New**

* Added ability to force logout users.
* Added cleanup on plugin uninstall.
* Added review notice.

**👌 Improvements**

* Code improvement

= 1.0.1 (02/07/2016) =

**🐛 Bug Fixes**

* Fixing misspelled variable.

= 1.0.0 (16/06/2016) =

**📦 New**

* Initial version release.


== Upgrade Notice ==

= 1.3.1 (19/09/2020) =

**👌 Improvements**

* Support ajax logins.
