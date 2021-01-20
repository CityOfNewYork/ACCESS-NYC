=== Query monitor Twig profile ===
Contributors: nielsdeblaauw
Tags: timber, twig, query monitor, performance, profile, speed, template, theme, developer, development, debug
Requires at least: 4.9.0
Tested up to: 5.6.0
Requires PHP: 7.0
Stable tag: 1.3.1
License: MIT
License URI: https://raw.githubusercontent.com/NielsdeBlaauw/query-monitor-twig-profile/master/LICENSE

Displays Twig profiler output in Query Monitor.

== Description ==
Find out which pages are slow, and why! Immediately see profiling information from twig in your [Query Monitor](https://wordpress.org/plugins/query-monitor/) toolbar. 

The Twig profile extension for Query Monitor helps you notice which templates are used on a page and where the time rendering the page is spent.

Kind of like the ['What the file' plugin](https://wordpress.org/plugins/what-the-file/), but for Twig and with timing information.

- Support for dark mode.
- Clickable links to profiled templates in your preferred editor.
- Downloadable blackfire.io profiles.
- History mode. Save profiles so you can see the impact of your changes.
- Color scheme for dark and light modes meet WCAG AA accessibility standards, and all controls are keyboard accessible. 
- Automatically integrates with Timber.

== Installation ==
1. Install the plugin.
2. Activate it.
3. Check the 'Twig profile' tab in Query Monitor.
4. Speed up your site!

Alternatively, you can use [wpackagist](https://wpackagist.org/search?q=query-monitor-twig-profile&type=plugin&search=) or [packagist](https://packagist.org/packages/nielsdeblaauw/query-monitor-twig-profile).

You can also download specific releases and the development version from [GitHub](https://github.com/NielsdeBlaauw/query-monitor-twig-profile/releases).

== Frequently Asked Questions ==
# Can I use it with other frameworks that use twig?
Definitely. Just add a twig profiler extension to your twig instance and submit it to the collector.

`
if ( function_exists( 'NdB\QM_Twig_Profile\collect' ) ) {
	$twig = \NdB\QM_Twig_Profile\collect( $twig );
}
`

= Privacy Statement =
Query Monitor Twig Profile data is private by default and always will be. It does not persistently store any of the data that it collects. It does not send data to any third party, nor does it include any third party resources.

== Screenshots ==
1. The Twig profile tab in Query Monitor (dark mode)
2. The Twig profile tab in Query Monitor (light mode)

== Changelog ==
1.3.1
* Maintenance: Do not upload the `node_modules` folder of webcomponent to the plugin repository.

1.3.0
* Adds history mode. Save your profiles and view them later to see the impact of your changes. Compare profiles over multiple pages, and more.
* Automatically integrates with clarkson-core:^1.0

1.2.0
* Adds blackfire.io profile downloads.

1.1.0
* Support for dark mode.
* Support direct links to the templates in the editor.
* Makes it easier to profile a custom Twig instance.

1.0.3
* Removes assets release library.
* Uses readme.txt file.

1.0.2
* Fixes readme.

1.0.1
* Adds automated releases from GitHub.
* Improves readme.
* Fixes several type hints.
* Adds CI checks (phpstan, phpcs, phpcompat, composer validate).
* Defines required PHP version as >7.0.

1.0.0:
* Initial release.

== Development ==

This open source tool is developed in a public [GitHub repository](https://github.com/NielsdeBlaauw/query-monitor-twig-profile). If you have any feature requests, found an issue or want to contribute check out the repository.
