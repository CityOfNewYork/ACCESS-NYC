=== Relevanssi Premium - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/
Tags: search, relevance, better search
Requires at least: 4.9
Requires PHP: 7.0
Tested up to: 6.5
Stable tag: 2.25.2

Relevanssi Premium replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features and configurable options. You'll get better results, better presentation of results - your users will thank you.

= Key features =
* Search results sorted in the order of relevance, not by date.
* Fuzzy matching: match partial words, if complete words don't match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Search for phrases with quotes, for example "search phrase".
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the documents when user clicks through search results.
* Search comments, tags, categories and custom fields.

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Automatic support for [WPML multi-language plugin](http://wpml.org/).
* Automatic support for various membership plugins.
* Advanced filtering to help hacking the search results the way you want.
* Search result throttling to improve performance on large databases.
* Disable indexing of post content and post titles with a simple filter hook.
* Multisite support.

= Premium features (only in Relevanssi Premium) =
* PDF content indexing.
* Search result throttling to improve performance on large databases.
* Improved spelling correction in "Did you mean?" suggestions.
* Searching over multiple subsites in one multisite installation.
* Indexing and searching user profiles.
* Weights for post types, including custom post types.
* Limit searches with custom fields.
* Index internal links for the target document (sort of what Google does).
* Search using multiple taxonomies at the same time.

Relevanssi is available in two versions, regular and Premium. Regular Relevanssi is and will remain free to download and use. Relevanssi Premium comes with a cost, but will get all the new features. Standard Relevanssi will be updated to fix bugs, but new features will mostly appear in Premium. Also, support for standard Relevanssi depends very much on my mood and available time. Premium pricing includes support.

= Other search plugins =
Relevanssi owes a lot to [wpSearch](https://wordpress.org/extend/plugins/wpsearch/) by Kenny Katzgrau. Relevanssi was built to replace wpSearch, when it started to fail.


== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new version and if the new version has changes in the indexing, rebuild the index.

= Note on updates =
If it seems the plugin doesn't work after an update, the first thing to try is deactivating and reactivating the plugin. If there are changes in the database structure, those changes do not happen without a deactivation, for some reason.

= Changes to templates =
None necessary! Relevanssi uses the standard search form and doesn't usually need any changes in the search results template.

If the search does not bring any results, your theme probably has a query_posts() call in the search results template. That throws Relevanssi off. For more information, see [The most important Relevanssi debugging trick](http://www.relevanssi.com/knowledge-base/query_posts/).

= How to index =
Check the options to make sure they're to your liking, then click "Save indexing options and build the index". If everything's fine, you'll see the Relevanssi options screen again with a message "Indexing successful!"

If something fails, usually the result is a blank screen. The most common problem is a timeout: server ran out of time while indexing. The solution to that is simple: just return to Relevanssi screen (do not just try to reload the blank page) and click "Continue indexing". Indexing will continue. Most databases will get indexed in just few clicks of "Continue indexing". You can follow the process in the "State of the Index": if the amount of documents is growing, the indexing is moving along.

If the indexing gets stuck, something's wrong. I've had trouble with some plugins, for example Flowplayer video player stopped indexing. I had to disable the plugin, index and then activate the plugin again. Try disabling plugins, especially those that use shortcodes, to see if that helps. Relevanssi shows the highest post ID in the index - start troubleshooting from the post or page with the next highest ID. Server error logs may be useful, too.

= Using custom search results =
If you want to use the custom search results, make sure your search results template uses `the_excerpt()` to display the entries, because the plugin creates the custom snippet by replacing the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()` filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

See Frequently Asked Questions for more instructions on what you can do with Relevanssi.

= The advanced hacker option =
If you're doing something unusual with your search and Relevanssi doesn't work, try using `relevanssi_do_query()`. See [Knowledge Base](http://www.relevanssi.com/knowledge-base/relevanssi_do_query/).

= Uninstalling =
To uninstall the plugin remove the plugin using the normal WordPress plugin management tools (from the Plugins page, first Deactivate, then Delete). If you remove the plugin files manually, the database tables and options will remain.

= Combining with other plugins =
Relevanssi doesn't work with plugins that rely on standard WP search. Those plugins want to access the MySQL queries, for example. That won't do with Relevanssi. [Search Light](http://wordpress.org/extend/plugins/search-light/), for example, won't work with Relevanssi.

Some plugins cause problems when indexing documents. These are generally plugins that use shortcodes to do something somewhat complicated. One such plugin is [MapPress Easy Google Maps](http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/). When indexing, you'll get a white screen. To fix the problem, disable either the offending plugin or shortcode expansion in Relevanssi while indexing. After indexing, you can activate the plugin again.

== Frequently Asked Questions ==

= Where is the Relevanssi search box widget? =
There is no Relevanssi search box widget.

Just use the standard search box.

= Where are the user search logs? =
See the top of the admin menu. There's 'User searches'. There. If the logs are empty, please note showing the results needs at least MySQL 5.

= Displaying the number of search results found =

The typical solution to showing the number of search results found does not work with Relevanssi. However, there's a solution that's much easier: the number of search results is stored in a variable within $wp_query. Just add the following code to your search results template:

`<?php echo 'Relevanssi found ' . $wp_query->found_posts . ' hits'; ?>`

= Advanced search result filtering =

If you want to add extra filters to the search results, you can add them using a hook. Relevanssi searches for results in the _relevanssi table, where terms and post_ids are listed. The various filtering methods work by listing either allowed or forbidden post ids in the query WHERE clause. Using the `relevanssi_where` hook you can add your own restrictions to the WHERE clause.

These restrictions must be in the general format of ` AND doc IN (' . {a list of post ids, which could be a subquery} . ')`

For more details, see where the filter is applied in the `relevanssi_search()` function. This is stricly an advanced hacker option for those people who're used to using filters and MySQL WHERE clauses and it is possible to break the search results completely by doing something wrong here.

There's another filter hook, `relevanssi_hits_filter`, which lets you modify the hits directly. The filter passes an array, where index 0 gives the list of hits in the form of an array of post objects and index 1 has the search query as a string. The filter expects you to return an array containing the array of post objects in index 0 (`return array($your_processed_hit_array)`).

= Direct access to query engine =
Relevanssi can't be used in any situation, because it checks the presence of search with the `is_search()` function. This causes some unfortunate limitations and reduces the general usability of the plugin.

You can now access the query engine directly. There's a new function `relevanssi_do_query()`, which can be used to do search queries just about anywhere. The function takes a WP_Query object as a parameter, so you need to store all the search parameters in the object (for example, put the search terms in `$your_query_object->query_vars['s']`). Then just pass the WP_Query object to Relevanssi with `relevanssi_do_query($your_wp_query_object);`.

Relevanssi will process the query and insert the found posts as `$your_query_object->posts`. The query object is passed as reference and modified directly, so there's no return value. The posts array will contain all results that are found.

= Sorting search results =
If you want something else than relevancy ranking, you can use orderby and order parameters. Orderby accepts $post variable attributes and order can be "asc" or "desc". The most relevant attributes here are most likely "post_date" and "comment_count".

If you want to give your users the ability to sort search results by date, you can just add a link to http://www.yourblogdomain.com/?s=search-term&orderby=post_date&order=desc to your search result page.

Order by relevance is either orderby=relevance or no orderby parameter at all.

= Filtering results by date =
You can specify date limits on searches with `by_date` search parameter. You can use it your search result page like this: http://www.yourblogdomain.com/?s=search-term&by_date=1d to offer your visitor the ability to restrict their search to certain time limit (see [RAPLIQ](http://www.rapliq.org/) for a working example).

The date range is always back from the current date and time. Possible units are hour (h), day (d), week (w), month (m) and year (y). So, to see only posts from past week, you could use by_date=7d or by_date=1w.

Using wrong letters for units or impossible date ranges will lead to either defaulting to date or no results at all, depending on case.

Thanks to Charles St-Pierre for the idea.

= Displaying the relevance score =
Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add something like

`echo $post->relevance_score`

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
To use Google-style "did you mean?" suggestions, first enable search query logging. The suggestions are based on logged queries, so without good base of logged queries, the suggestions will be odd and not very useful.

To use the suggestions, add the following line to your search result template, preferably before the have_posts() check:

`<?php if (function_exists('relevanssi_didyoumean')) { relevanssi_didyoumean(get_search_query(), "<p>Did you mean: ", "?</p>", 5); }?>`

The first parameter passes the search term, the second is the text before the result, the third is the text after the result and the number is the amount of search results necessary to not show suggestions. With the default value of 5, suggestions are not shown if the search returns more than 5 hits.

= Search shortcode =
Relevanssi also adds a shortcode to help making links to search results. That way users can easily find more information about a given subject from your blog. The syntax is simple:

`[search]John Doe[/search]`

This will make the text John Doe a link to search results for John Doe. In case you want to link to some other search term than the anchor text (necessary in languages like Finnish), you can use:

`[search term="John Doe"]Mr. John Doe[/search]`

Now the search will be for John Doe, but the anchor says Mr. John Doe.

One more parameter: setting `[search phrase="on"]` will wrap the search term in quotation marks, making it a phrase. This can be useful in some cases.

= Restricting searches to categories and tags =
Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or tags, since those are pretty much the same). Just add a hidden input field named `cat` in your search form and list the desired category or tag IDs in the `value` field - positive numbers include those categories and tags, negative numbers exclude them.

This input field can only take one category or tag id (a restriction caused by WordPress, not Relevanssi). If you need more, use `cats` and use a comma-separated list of category IDs.

You can also set the restriction from general plugin settings (and then override it in individual search forms with the special field). This works with custom taxonomies as well, just replace `cat` with the name of your taxonomy.

If you want to restrict the search to categories using a dropdown box on the search form, use a code like this:

`<form method="get" action="<?php bloginfo('url'); ?>">
	<div><label class="screen-reader-text" for="s">Search</label>
	<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" />
<?php
	wp_dropdown_categories(array('show_option_all' => 'All categories'));
?>
	<input type="submit" id="searchsubmit" value="Search" />
	</div>
</form>`

This produces a search form with a dropdown box for categories. Do note that this code won't work when placed in a Text widget: either place it directly in the template or use a PHP widget plugin to get a widget that can execute PHP code.

= Restricting searches with taxonomies =

You can use taxonomies to restrict search results to posts and pages tagged with a certain taxonomy term. If you have a custom taxonomy of "People" and want to search entries tagged "John" in this taxonomy, just use `?s=keyword&people=John` in the URL. You should be able to use an input field in the search form to do this, as well - just name the input field with the name of the taxonomy you want to use.

It's also possible to do a dropdown for custom taxonomies, using the same function. Just adjust the arguments like this:

`wp_dropdown_categories(array('show_option_all' => 'All people', 'name' => 'people', 'taxonomy' => 'people'));`

This would do a dropdown box for the "People" taxonomy. The 'name' must be the keyword used in the URL, while 'taxonomy' has the name of the taxonomy.

= Automatic indexing =
Relevanssi indexes changes in documents as soon as they happen. However, changes in shortcoded content won't be registered automatically. If you use lots of shortcodes and dynamic content, you may want to add extra indexing. Here's how to do it:

`if (!wp_next_scheduled('relevanssi_build_index')) {
	wp_schedule_event( time(), 'daily', 'relevanssi_build_index' );
}`

Add the code above in your theme functions.php file so it gets executed. This will cause WordPress to build the index once a day. This is an untested and unsupported feature that may cause trouble and corrupt index if your database is large, so use at your own risk. This was presented at [forum](http://wordpress.org/support/topic/plugin-relevanssi-a-better-search-relevanssi-chron-indexing?replies=2).

= Highlighting terms =
Relevanssi search term highlighting can be used outside search results. You can access the search term highlighting function directly. This can be used for example to highlight search terms in structured search result data that comes from custom fields and isn't normally highlighted by Relevanssi.

Just pass the content you want highlighted through `relevanssi_highlight_terms()` function. The content to highlight is the first parameter, the search query the second. The content with highlights is then returned by the function. Use it like this:

`if (function_exists('relevanssi_highlight_terms')) {
    echo relevanssi_highlight_terms($content, get_search_query());
}
else { echo $content; }`

= Multisite searching =
To search multiple blogs in the same WordPress network, use the `searchblogs` argument. You can add a hidden input field, for example. List the desired blog ids as the value. For example, searchblogs=1,2,3 would search blogs 1, 2, and 3.

The features are very limited in the multiblog search, none of the advanced filtering works, and there'll probably be fairly serious performance issues if searching common words from multiple blogs.

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency* while idf is *inverted document frequency*. Term frequency is simply the number of times the term appears in a document, while document frequency is the number of documents in the database where the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about every document are completely useless for information retrieval purposes. Basically, their inverted document frequency is really low, so they never have much power in matching. Also, removing those words helps to make the index smaller and searching faster.

== Known issues and To-do's ==
* Known issue: In general, multiple Loops on the search page may cause surprising results. Please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* Known issue: When a tag is removed, Relevanssi index isn't updated until the post is indexed again.

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape.
* Mohib Ebrahim for relentless bug hunting.
* John Blackbourn for amazing internal link feature and other fixes.
* John Calahan for extensive 2.0 beta testing.

== Changelog ==
= 2.25.2 =
* Security fix: Prevent CSV injection attack in log export.
* Security fix: Restrict access to doc count updates.
* Minor fix: Product variations check the parent product for access restrictions, to avoid situations where variations of a draft product appear in the results.
* Minor fix: Improved TablePress compatibility.
* Minor fix: Added error handling to the Ninja Table compatibility code.

= 2.25.1 =
* Security fix: Relevanssi had a vulnerability where anyone could access the search logs and click logs. The log export is now protected.
* Minor fix: Relevanssi had problems with Polylang when a post or term didn't have language specified. Now Relevanssi handles those situations better.
* Minor fix: Post date throttling had a MySQL error that made it replace JOINs instead of concatenating.
* Minor fix: The log database table now has an index on session_id, as not having that index can slow down the search a lot.

= 2.25.0 =
* New feature: New filter hook `relevanssi_searchform_dropdown_args` filters the arguments for `wp_dropdown_categories()` in search forms.
* Changed behaviour: Search form shortcode taxonomy dropdowns are now sorted alphabetically and not by term ID.
* Minor fix: Caught a bug in excerpt-building with empty words.
* Minor fix: It's now possible to set both `post__in` and `post__not_in` and likewise for `parent__in` and `parent__not_in`.
* Minor fix: The `post_status` is no longer available as a query parameter.

= 2.24.4 =
* Minor fix: Fixes broken taxonomy indexing.

= 2.24.3 =
* Security fix: Relevanssi had a vulnerability for SQL injections. Exploiting the vulnerability does require WP admin access. This vulnerability is now fixed.
* Security fix: Relevanssi had a vulnerability for error log injections. Exploiting this vulnerability requires file upload access to the site. This vulnerability is now fixed.
* New feature: New filter hook 'relevanssi_get_attachment_posts_query_final' filters the final SQL query for attachment post fetching.
* Changed behaviour: Relevanssi now skips attachments with 'Server did not respond' errors when reading attachments. This should help with problems coming from attachments that are too big read.
* Minor fix: Improved server timeout error handling for attachment reading.
* Minor fix: Relevanssi didn't strip tags from custom field specific excerpts, and could show zero-hit excerpts for the content.
* Minor fix: Multisite search failed when the search term resolved to nothing in the tokenizer.

= 2.24.2 =
* Minor fix: Fixes broken WP CLI progress bars.
* Minor fix: Meta query boolean to array conversion.
* Minor fix: For indexing, stemmer is always in OR mode so that both stemmed word and the original word is indexed.

= 2.24.1 =
* New feature: The debugging tab now shows the status of the 'relevanssi_words' option.
* Changed behaviour: The 'relevanssi_index_content' and 'relevanssi_index_titles' filter hooks now get the post object as a second parameter.
* Minor fix: Stop Relevanssi from blocking the feed searches.
* Minor fix: Remove warning from missing blog_id parameter.
* Minor fix: Improve exact match boosts with accented letters.
* Minor fix: Entering synonyms in Polylang all languages mode was possible; it shouldn't be.
* Minor fix: Relevanssi is now blocked in the reusable content block search.

= 2.24.0 =
* New feature: New filter hook `relevanssi_highlight_regex` makes it possible to adjust the regex used for highlighting.
* New feature: New filter hook `relevanssi_excerpt_custom_fields` filters the list of custom fields used for creating the excerpt.
* New feature: New filter hook `relevanssi_phrase_custom_fields` filters the list of custom fields used for phrase matching. Return an empty array to disable phrase matching in custom fields.
* New feature: New filter hook `relevanssi_phrase_taxonomies` filters the list of taxonomies used for phrase matching. Return an empty array to disable phrase matching in taxonomies.
* New feature: If RELEVANSSI_DEBUG, WP_DEBUG and WP_DEBUG_DISPLAY are all true, Relevanssi will print out indexing debugging messages to the error log (PHP error log or whatever is defined in WP_DEBUG_LOG).
* Changed behaviour: If the `relevanssi_accents_replacement_arrays` returns an empty array, the accent variation feature is disabled.
* Minor fix: Term indexing with WPML only indexed the terms in the current admin language. Now the terms are indexed in all languages.
* Minor fix: Some ACF fields change the global $post, leading to indexing problems. Relevanssi tries to prevent that now.
* Minor fix: Relevanssi couldn't create the click tracking table on subsites during the multisite installation.
* Minor fix: Safety features for post-part targeting to avoid fatal errors from wrong variable types.
* Minor fix: Pinning failed in multisite if the search site didn't have any pinned posts.
* Minor fix: The `relevanssi_custom_field_value` filter hook is now applied to `_relevanssi_pdf_content` field when the PDF content is indexed for the parent post.
* Minor fix: The `relevanssi_premium_get_post()` now returns a `WP_Error` when the post is not found.
* Minor fix: The "Did you mean" feature now makes less suggestions for words that are already correct.
* Minor fix: Trigger reindexing of the parent post when an attachment is attached or detached from the Media Library.
* Minor fix: Click tracking now works much better in multisite searches and counts the clicks for the correct subsite.

= 2.23.0 =
* New feature: Relevanssi can now create custom field specific excerpts that come from one custom field only and know which field that is.
* New feature: You can see the list of indexed custom field names in the indexing and excerpt settings.
* New feature: New filter hook `relevanssi_excerpt_specific_custom_field_content` filters the excerpt custom field content if `relevanssi_excerpt_specific_fields` is enabled.
* Changed behaviour: The `relevanssi_get_custom_field_content()` function now returns an array instead of string. If `relevanssi_excerpt_specific_fields` is off, the previous string return value is returned as a single-item array with the string in index 0. If the setting is on, the array keys are the field names.
* Changed behaviour: The `relevanssi_get_child_pdf_content()` function now returns an array.
* Minor fix: The "none" value in category dropdowns from the searchform shortcode is changed from -1 to 0.
* Minor fix: The stopword population during the multisite installation used the wrong database table, leading to failed population.
* Minor fix: Multisite installation is moved from `wp_insert_site` (priority 10) to `wp_initialize_site` (priority 200) in order to avoid trouble.
* Minor fix: Wildcard search now works in whole word matching, not only in partial matching.
* Minor fix: The Gutenberg sidebar translations didn't show up. Now you should be able to see the sidebar in the right language.

= 2.22.0 =
* New feature: Logging now includes a session ID (based on user ID for logged-in users, HTTP user agent for others, and current time, stable for 10 minutes per user). This is used to remove duplicate searches from live searches, keeping only the final search query.
* Minor fix: The pin weights did not appear in the classic editor Relevanssi metabox, despite being stored in the database.

= 2.21.0 =
* New feature: You can now add weights to pinned terms to control the order of the pinned posts.
* New feature: New filter hook `relevanssi_add_attachment_scripts` lets you add the attachment javascripts to other post types than `attachment`.
* New feature: New filter hook `relevanssi_highlight_query` lets you modify the search query for highlighting.
* Changed behavior: Relevanssi no longer searches in feed searches by default.
* Minor fix: The filter `relevanssi_get_attachment_url` is now also used when adding the attachment metabox.
* Minor fix: No more crashes from Polylang forced plugin updates.
* Minor fix: PHP 8.1 deprecated FILTER_SANITIZE_STRING, those are now replaced.

= 2.20.4 =
* New feature: New filter hook `relevanssi_blocked_field_types` can be used to control which ACF field types are excluded from the index. By default, this includes 'repeater', 'flexible_content', and 'group'.
* New feature: New filter hook `relevanssi_acf_field_object` can be used to filter the ACF field object before Relevanssi indexes it. Return false to have Relevanssi ignore the field type.
* Minor fix: ACF field exclusion is now recursive. If a parent field is excluded, all sub fields will also be excluded.
* Minor fix: The indexing settings tab now checks if the wp_relevanssi database table exists and will create the table if it doesn't.
* Minor fix: Pinning code has been foolproofed to cover some situations that would lead to errors.
* Minor fix: Handling of data attributes in in-document highlighting had a bug that caused problems with third-party plugins.

= 2.20.3 =
* New feature: Relevanssi now has a debug mode that will help troubleshooting and support.
* Minor fix: Using the_permalink() caused problems with search result links. That is now fixed. Relevanssi no longer hooks onto `the_permalink` hook and instead uses `post_link` and other similar hooks.
* Minor fix: Click tracking parameters have more control to avoid problems from malformed click tracking data.

= 2.20.2 =
* Fixes the persistent update nag.

= 2.20.1 =
* New feature: New filter hook `relevanssi_add_highlight_and_tracking` can be used to force Relevanssi to add the `highlight` and tracking parameters to permalinks.
* Changed behaviour: Exclusions now override pinning. If a post is pinned for 'foo' and excluded for 'foo bar', it will now be excluded when someone searches for 'foo bar'. Previously pinning overrode the exclusion.
* Changed behaviour: The 'relevanssi_wpml_filter' filter function now runs on priority 9 instead of 10 to avoid problems with custom filters on relevanssi_hits_filter.
* Minor fix: Page links didn't get the click tracking tags. This is fixed now.
* Minor fix: Including posts in the Related posts could cause duplicates. Now Relevanssi excludes the included posts from the search so that there won't be duplicates.
* Minor fix: Handle cases of missing posts better; relevanssi_get_post() now returns a WP_Error if no post is found.
* Minor fix: Avoid a slow query on the searching tab when the throttle is not enabled.
* Minor fix: Search queries that contain apostrophes and quotes can now be deleted from the log.

= 2.20.0 =
* New feature: Relevanssi now shows the MySQL `max_allowed_packet` size on the debug tab.
* New feature: Relevanssi now shows the indexing query on the debug tab.
* New feature: You can now edit pinning and exclusions from Quick Edit.
* New feature: You can now remove queries from the search log from the query insights page.
* New feature: ACF field settings now include a 'Exclude from Relevanssi index' setting. You can use that to exclude ACF fields from the Relevanssi index.
* Changed behaviour: Click tracking is disabled in multisite searches. It causes problems with wrong links and isn't very reliable in the best case.
* Changed behaviour: Plugin translation updates are disabled, unless explicitly enabled either from the Overview settings or with the `relevanssi_update_translations` filter hook.
* Minor fix: Relevanssi was adding extra quotes around search terms in the `highlight` parameter.
* Minor fix: Metabox fields look nicer on Firefox.
* Minor fix: Adds the `relevanssi_related_posts_cache_id` filter to the relevanssi_related_posts() function.
* Minor fix: Yet another update to data attributes in highlighting. Thanks to Faeddur.
* Minor fix: Taxonomy query handling was improved. This should help in particular Polylang users who've had problems with Relevanssi ignoring Polylang language restrictions.
* Minor fix: Negative search terms in AND searches caused problems, but now work better.
* Minor fix: Pinning phrases that had the same word more than once (e.g. 'word by word') didn't work. Now it works better.

== Upgrade notice ==
= 2.25.2 =
* Security hardening, improved WooCommerce, TablePress and Ninja Table compatibility.

= 2.25.1 =
* Security hardening, better Polylang support.

= 2.25.0 =
* Improvements to search form shortcode.

= 2.24.4 =
* Fix broken taxonomy indexing.

= 2.24.3 =
* Security hardening, attachment handling improvements.

= 2.24.2 =
* Meta query errors, WP CLI progress bars, stemmer indexing.

= 2.24.1 =
* Fix for the blog_id bug, small improvements.

= 2.24.0 =
* Improved debugging, bug fixes and new filter hooks.

= 2.23.0 =
* Better method for handling custom fields in excerpts, bug fixes.

= 2.22.0 =
* Logs now include a session ID.

= 2.21.0 =
* You can now assign weights to pinned keywords.

= 2.20.4 =
* Better ACF field controls, bug fixes.

= 2.20.3 =
* Fixes a bug with broken permalinks.

= 2.20.2 =
* Fixes the persistent update nag.

= 2.20.1 =
* Bug fixes and small improvements.

= 2.20.0 =
* New features, performance improvements, bug fixes.