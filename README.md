# ACCESS NYC
## Table of Contents
* [Installation and Local Environment Configuration](#installation)
	* [wp-config](#installation-wp-config)
	* [.env file](#installation-env)
	* [Building the Project](#installation-build)
* [Technical Details](#tech)
	* [CSS](#tech-css)
	* [JavaScript](#tech-js)
	* [Code Linting and Standards](#tech-standards)
	* [Debug](#tech-debug)
	* [Directory Structure](#tech-structure)

<a name="installation"></a>
## Installation and Local Environment Configuration

Clone the repository and set up your local environment however you would normally set up a WordPress project locally.

<a name="installation-wp-config"></a>
### wp-config
After cloning the repo and downloading the database, save a copy of `wp-config-sample.php` as `wp-config.php`. (Don't delete the original!) Add the following lines to your wp-config file, replacing "yourlocalurl" with the URL you are using locally:

```
define('WP_DEBUG', true);
define('WP_HOME', 'http://yourlocalurl');
define('WP_SITEURL', 'http://yourlocalurl');
```

Add the database credentials to that file as well.

<a name="installation-env"></a>
### .env file
While not required, a .env file is used to define the proxy URL for BrowserSync. Go to `wp-content/themes/access`, copy `.env.example` to a new file and name it `.env`. Change the `WP_DEV_URL` value to whatever you have set `wp-config.php`’s URL to be.

<a name="installation-build"></a>
### Building the Project
If you haven't yet, you'll need to [install node and npm](https://nodejs.org/en/download/). Once those are installed, open up Terminal and navigate to the `access` theme directory.

```
$ cd ~/{{your local path here}}/access-nyc/wp-content/themes/access
```

In the `access` directory, install the project dependencies. You only need to do this the first time you are setting up the project.

```
$ npm install
```

Once project dependencies are installed, run:

```
$ gulp
```

This will build the project and watch the project directory for changes, rebuilding as necessary. If a `.env` was configured correctly, a browser window pointed to `http://localhost:3001` will open with [BrowserSync](https://www.browsersync.io/) active. This allows for live reloading of the page as assets (CSS, JS, images) are updated and is useful for synchronous browser testing.

If you only want to build the project and not set up a watch task or BrowserSync window, you can simply do.

```
$ gulp build
```

<a name="tech"></a>
## Technical Details

<a name="tech-css"></a>
### CSS

Source files for the site's CSS is located in the `/wp-content/themes/access/src/scss` directory. The SCSS files are processed, concatenated, and minfied by the `gulp styles` task.

Many of the styles are either directly taken from or adapted from the [U.S. Web Design Standards](https://github.com/18F/web-design-standards). Like the USWDS, we utilize [Bourbon (v4.2.7)](bourbon.io/docs/) and [Neat (v1.8)](http://neat.bourbon.io/docs/1.8.0/) for our SCSS mixin libraries.

The main style sheet is `style.scss`. This is used for English, Russian, and other sites where the usual Latin character font set is used.

Korean (`style-ko.scss`) and Chinese (`style-zh-hant.scss`) stylesheets are variants on the main one where the appropriate font files are added and the font stacks reconfigured.

Finally the Arabic stylesheet (`style-ar.scss`) adds the Arabic font family to the font stack and flips the layout for right-to-left. This is accomplished using SCSS variables defined in `scss/core/_variables.scss`. 

```
$text-direction: ltr;
$text-direction-start: left;
$text-direction-end: right;

@if $language == 'ar' {
  $text-direction: rtl;
  $text-direction-start: right;
  $text-direction-end: left;
}
```

Essentially, any time you would write "left" in the stylesheet, use the `$text-drection-start` variable instead. E.g. `float: $text-direction-start;` or `margin-#{$text-direction-start}: 0;`

The stylesheet is broken up into several smaller partials. Refer to `scss/_all.scss` to get a sense of overall stylesheet organization. 

<a name="tech-js"></a>
### JavaScript
The two main JavaScript libraries used are [jQuery](http://jquery.com/) and [Underscore.js](http://underscorejs.org/). [js-cookie](https://github.com/js-cookie/js-cookie) is also used for browser cookie management.

The JavaScript is written in a pseudo-classical pattern and broken up into modules using ES6 syntax. Source files are located in the `/wp-content/themes/access/src/js` directory. The JavaScript is linted by the `gulp lint` task. It is translated to ES5 syntax, concatenated, and minified by the `gulp scripts` task, using Babelify (Babel + Browserify).

<a name="tech-standards"></a>
### Code Linting and Standards
An `.editorconfig` file is included to enforce some style settings for code editors that support it. See [editorconfig.com](http://editorconfig.org/) for more information. Furthermore, an eslint task enforces JavaScript style rules, based on [Google's standards](https://google.github.io/styleguide/javascriptguide.xml). While no SCSS linting task has been configured, this project follows the [18F front end guide](https://pages.18f.gov/frontend/#css).

<a name="tech-debug"></a>
### Debug Mode
You can append `?debug=1` to the site URL in any environment to help in debugging front-end issues. This will do a number of things. It will serve a cache-busted version of the stylesheet. It will serve a non-minified, cachce-busted version of the JavaScript. Finally, if you are in the eligibility screener, it will allow you to jump around to different steps, e.g. `/eligiblity?debug=1#step-8`, and if the web inspector is open will pause the app before and after the screener form is submitted while outputting the data payload and response object respectively.

<a name="tech-structure"></a>
### Directory Structure

<kbd>/wp-admin</kbd>: Wordpress core files. Do not make changes here. They will be overwritten when Wordpress updates.

<kbd>/wp-content/plugins</kbd>: Wordpress plugins. Any third-party and custom-plugins (other than the must-use plugins) will go in this directory. Several plugins were custom-developed for this project, including `drools-proxy`, `sendmenyc`, and `statcollector`.

<kbd>/wp-content/themes/access</kbd>: The bulk of the site's code lives in this theme folder.

  - <kbd>/assets</kbd>: Contains static assets for the site. Most of these are compiled from the src directory

  - <kbd>/includes</kbd>: BSD boilerplate helper functions.

  - <kbd>/src</kbd>: Contains SCSS, JavaScript, and image source files

    - <kbd>/img</kbd>: Image sources. There is a Gulp task, `gulp images`, set up to minify images and copy them to the assets folder automatically
      - <kbd>/sprite</kbd>: SVG files that are optimized and compiled into a single sprite file by the `gulp svg-sprites` task. This directory is ignored by the `gulp images` task.

    - <kbd>/js</kbd>: JavaScript source files

        - <kbd>/modules</kbd>: ES6 modules that are imported into `main.js`

        - <kbd>/main.js</kbd>: The main JavaScript file for the project.

    - <kbd>/scss</kbd>: SCSS Stylesheets

        - <kbd>/core</kbd>: Mostly variables and utility styles.

        - <kbd>/elements</kbd>: Atomic css classes used throughout the site
        - <kbd>/components</kbd>: CSS styles for more component or sectional classes

        - <kbd>/style.scss and other language variants</kbd>: The main stylesheet for the project. It must compile to a file named style.css (or style-*.css) that is in the root of the theme directory (not within <kbd>/assets</kbd>) and include a comment at the top giving the theme name. These essentially just imports `_all.scss`, but pass a language SASS variable.
        - <kbd>/_all.scss</kbd>: The primary SASS file that imports all partials.

- <kbd>/views</kbd>: Twig templates used to generate the front-end markup

<kbd>/wp-includes</kbd>: More Wordpress core files. Do not make changes here. They will be overwritten when Wordpress updates.
