# NYCO WP Assets

Helpers for managing assets in Wordpress

## Installation

    composer require nyco/wp-assets

#### Usage

Require in `functions.php`.

    require_once(get_template_directory() . '/vendor/nyco/wp-assets/style');
    require_once(get_template_directory() . '/vendor/nyco/wp-assets/script');

## Enqueue Style

`Nyco\Enqueue\style`

Enqueues stylesheet with hashed filename for cache busting. Supports language
code in file name. The naming pattern is `

In `functions.php` or `single.php`, etc.

    Enqueue\style();
    // Enqueues "style-default.<hash>.css"

    Enqueue\style('style-sp', '');
    // Enqueues "style-sp.<hash>.css"

    Enqueue\style('style', '', '-');
    // Enqueues "style-<hash>.css"

    Enqueue\style('style', '.min');
    // Enqueues "style.<hash>.min.css" if ?debug=1 else, enqueues "style.<hash>.css"

In template (twig).

    {{ function('Enqueue\\style') }}
    {# Enqueues "style.<hash>.css" #}

    {{ function('Enqueue\\style', 'style-sp') }}
    {# Enqueues "style-sp.<hash>.css" #}

    {{ function('Enqueue\\style', 'style', '-') }}
    {# Enqueues "style-<hash>.css" #}

    {{ function('Enqueue\\style', 'style', '.min') }}
    {# Enqueues "style.<hash>.min.css" if ?debug=1 else, enqueues "style.<hash>.css" #}

## Enqueue Script

`Nyco\Enqueue\script`

Enqueues script with hashed filename for cache busting. Both functions work the same, but have different arguments and must be required
individually.

#### Arguments

## Enqueue Style

`Nyco\Enqueue\style`

- `[string]  $name ` Optional, The base name or path of the stylesheet source. Default: `'style'`

- `[string]  $sep `  Optional, The separator between the filename and the hash. Default: `.`

- `[boolean] $min  ` Optional, The post fix for minified files. Default: `''`

- `[array]   $deps ` Optional, maps to wp_enqueue_style `$deps`. Default: `array()`

- `[string]  $media` Optional, maps to wp_enqueue_style `$media`. Default: `'all'`

## Enqueue Script

`Nyco\Enqueue\script`

- `[string]  $name     ` The base name or path of the script source.

- `[string]  $sep `      Optional, The separator between the filename and the hash. Default: `.`

- `[boolean] $ugl      ` Optional, The post fix for minified files. Default: `''`

- `[array]   $deps     ` Optional, maps to wp_enqueue_script `$deps`. Default: `array()`

- `[array]   $in_footer` Optional, maps to wp_enqueue_script `$in_footer`. Default: `true`

### Contributing

Clone repository and create feature branch. Make changes and run `composer run lint`
to follow the coding specification. `composer run format` can help fix some of the issues.

# About NYCO

NYC Opportunity is the [New York City Mayor's Office for Economic Opportunity](http://nyc.gov/opportunity). We are committed to sharing open source software that we use in our products. Feel free to ask questions and share feedback. Follow @nycopportunity on [Github](https://github.com/orgs/CityOfNewYork/teams/nycopportunity), [Twitter](https://twitter.com/nycopportunity), [Facebook](https://www.facebook.com/NYCOpportunity/), and [Instagram](https://www.instagram.com/nycopportunity/).
