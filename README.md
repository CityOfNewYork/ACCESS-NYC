# ACCESS NYC
[ACCESS NYC](http://nyc.gov/accessnyc) provides a mobile-friendly front door for New Yorkers to screen for City, State, and Federal benefit and program eligibility as well as learn how to apply to programs and find local help.

ACCESS NYC is for benefits-seeking residents in NYC and accomodates residents...

* ... with low digital literacy
* ... with limited technology access, especially those who are mobile dependent
* ... who do not speak fluent English
* ... who have limited visual capabilities

### Tech
ACCESS NYC and its underlying content are available as in this WordPress site repository and the [NYC Open Data Portal](https://data.cityofnewyork.us/Social-Services/Benefits-and-Programs-API/2j8u-wtju). The ACCESS NYC eligibility screener is a single page web application powered by an API built on the [Drools Business Rules Management System](https://www.drools.org/) hosted on [Amazon Web Services](https://aws.amazon.com/) through the [NYC Department of Information Technology and Telecommunications](http://www.nyc.gov/doitt). Additional benefit program content is delivered by [WordPress](https://wordpress.org/), an open-source content management system, and hosted on [WP Engine](https://wpengine.com/). All benefit program information on ACCESS NYC is publicly available through the [Benefits and Programs API](https://data.cityofnewyork.us/Social-Services/Benefits-and-Programs-API/2j8u-wtju) on the City of New York’s Open Data portal.

The ACCESS NYC Design System utilizes patterns from the [U.S. Web Design System](https://designsystem.digital.gov/), the [Bourbon SASS Tool Set](https://www.bourbon.io/), and a [Tailwind.css Utility Framework](https://tailwindcss.com/).

Learn more about ACCESS NYC at [nyc.gov/opportunity](http://www1.nyc.gov/site/opportunity/portfolio/access-nyc.page).

## Local Usage

### Requirements
* Virtualization (Docker, Virtualbox, or other)
* Composer
* Node and NPM

*NYCO WP Docker Boilerplate*
This WordPress repository can be run many ways. The product team an NYC Opportunity uses [Docker for Mac](https://www.docker.com/docker-mac) and the [NYCO WP Docker Boilerplate](https://github.com/cityofnewyork/nyco-wp-docker-boilerplate) for running and managing the application locally.

*Composer*
PHP and WordPress plugin dependencies for WordPress core and the ACCESS NYC Theme are managed via Composer. [Learn more about Composer it's website](https://getcomposer.org/).

*Node and NPM*
The ACCESS NYC Theme uses Node with NPM to manage packages such as Gulp to compile assets for the front-end. Learn more about [Node](https://nodejs.org), [NPM](https://www.npmjs.com/), and [Gulp](https://gulpjs.com/) at their respective websites.

### Installation
This won't cover all of the options for standing up a WordPress site given all of the options available but it can be done with [Docker for Mac](https://www.docker.com/docker-mac) and following the instructions in the [NYCO WP Docker Boilerplate](https://github.com/cityofnewyork/nyco-wp-docker-boilerplate) readme.

### WordPress
ACCESS NYC is hosted on [WP Engine](https://wpengine.com/) and updates to WordPress are managed by their platform. WordPress dependencies (plugins) are managed via [WP Packagist](https://wpackagist.org/) and the WordPress Admin (if they aren't available on WP Packagist). They are packaged with the repository to be easily shipped to different environments.

There are three Must Use plugins that are specifically developed for this project. They include Drools Proxy, Send Me NYC, and Stat Collector.

### ACCESS NYC Theme
*Functions*
Some functions remain in the main `functions.php`, however, new modules are stored in the `/includes` directory and are included in `functions.php` at the bottom of the file.

*Templates*
The ACCESS NYC Theme is built on [Timber](https://www.upstatement.com/timber/) which uses the [Twig Templating Engine](https://twig.symfony.com/). Templates can be edited in the `/views` directory.

*Assets*
The source for image, style, and script files live in the `src` and are compiled to the `/assets` directory. This is done with the Gulp task manager.

To get started with modifying the theme front-end, change directories to the `access` theme and run `npm install` (Node and NPM are required to do this). This will install all node dependencies (including Gulp!) in the same directory.

The NPM package comes with three scripts;

* **development** - This runs the default Gulp task in development mode which watches and compiles files.
* **production** - This runs the default Gulp task in production mode which also watches and compiles files. The main difference is that production mode uses [ESLint](https://eslint.org/) and will enforce JavaScript writing style.
* **predeploy** - This will run a one-off build task in production mode. This should be run before all deployments.

Each can be run via `npm run <script>`. Once they run, they will fire up a [BrowserSync](https://www.browsersync.io/) instance to live test your code.

*.env file*
While not required, a `.env` file is included in the theme to define the proxy URL for [BrowserSync](https://www.browsersync.io/). Copy `.env.example` to a new file and name it `.env`. Change the `WP_DEV_URL` value to what is set in the `wp-config.php`.

*Debug Mode*
The query parameter `?debug=1` to the site URL in any environment to help in debugging front-end issues. This will do a number of things. It will serve a non-minified version of the JavaScript with some logging enabled. It will also allow you to jump around to different steps in the eligibility screener, e.g. `/eligiblity?debug=1#step-8`, and if the web inspector is open will pause the app before and after the screener form is submitted while outputting the data payload and response object respectively.

### Coding

*SCSS*
Many of the styles are either directly taken from or adapted from the [U.S. Web Design System](https://designsystem.digital.gov/). Like the USWDS, ACCESS NYC utilizes [Bourbon SASS Tool Set](https://www.bourbon.io/) and [Neat](https://neat.bourbon.io/) for SCSS mixin libraries.

Source files for the site’s CSS is located in the theme `/src/scss` directory. The SCSS files are processed, concatenated, and minfied by the gulp styles task. The stylesheet is broken up into several smaller partials. Refer to `scss/_all.scss` to get a sense of overall stylesheet organization.

For more details on multilingual and screen reader accessibile utilities refer to the Stylesheet wiki page.

*[ACCESS NYC Patterns Repository](https://github.com/cityofnewyork/access-nyc-patterns)* (in progress)
Currently, a stand-alone patterns repository is being developed for ACCESS NYC digital products. It will include the same USWD System integration with an additional utility framework called [Tailwind.css](https://tailwindcss.com/). Development for that project can be followed in the [ACCESS NYC Patterns repository](https://github.com/cityofnewyork/access-nyc-patterns).

The recommended style for module styling follows BEM with modules organized as Objects, Components, and Elements. The [ACCESS NYC Patterns repository](https://github.com/cityofnewyork/access-nyc-patterns) migrates the current repository to this new style.

*Javascript*
The JavaScript is written as modules using Babel ES6 syntax. Source files are located in the theme `/src/js` directory. JavaScript is linted by the `gulp lint` task with the ESLint [Google's standards](https://google.github.io/styleguide/javascriptguide.xml). It is transpiled, concatenated, and minified by the `gulp scripts` task, using [Babelify](https://github.com/babel/babelify) (Babel + Browserify).

The main JavaScript libraries used are [jQuery](http://jquery.com/), [Underscore.js](http://underscorejs.org/), and [Vue.js](https://vuejs.org/).

*PHP (in progress)*
PHP is not currently linted in this repository, however, the NYC Opportunity Product team recommends the [PSR-2 standard](https://www.php-fig.org/psr/psr-2/). Example configurations can be found in [NYCO Composer Packages on Packagist](https://packagist.org/users/nycopportunity/).

*Editor Config (deprecated)*
An `.editorconfig` file is included in the theme to enforce some style settings for code editors that support it. See [editorconfig.com](http://editorconfig.org/) for more information.

# About NYCO

NYC Opportunity is the [New York City Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity). We are committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. Follow @nycopportunity on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity), [Twitter](https://twitter.com/nycopportunity), [Facebook](https://www.facebook.com/NYCOpportunity/), and [Instagram](https://www.instagram.com/nycopportunity/).