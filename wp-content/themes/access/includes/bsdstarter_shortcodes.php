<?php

/**
 * Pullquotes embedded in the WYSIWYG of a detail page
 *
 * @param array  $attr {
 *  Attributes of the quote shortcode
 *
 *  @type string $author    The source of the quote
 * }
 * @param string  $content Shortcode content
 * @return string HTML markup to display a callout box with the quote
 */
function bsdstarter_quote($attr, $content = null) {
  // If there's no quote to display, let's get out of here
  if (!$content) {
    return;
  }

  $atts = shortcode_atts(array(
    'author' => null
  ), $attr);

  $output = '';

  $class = 'pullquote';

  $output .= '<blockquote class="' . $class . '">' . $content;
  if ($atts['author']) {
    $output .= '<cite class="pullquote__source">' . $atts['author'] . '</cite>';
  }
  $output .= '</blockquote>';

  return $output;
}

add_shortcode('quote', 'bsdstarter_quote');

/**
 * Statistics embedded in the WYSIWYG of a detail page
 *
 * @param array  $attr {
 *   Attributes of the stat shortcode
 *
 *   @type string $value  The statistic value (a number)
 *   @type string $label  The statistic label or description
 *   @type string $side   The side of the page to show the callout
 *                        box on, when the screen size is large enough.
 *                        Accepts 'left' or 'right'
 * }
 * @return string HTML markup to display a callout box with the stat
 */
function bsdstarter_stat($attr) {
  $atts = shortcode_atts(array(
    'value' => null,
    'label' => null,
  ), $attr);

  // Both value and label are required
  // If either is missing, don't display anything
  if (!( $atts['value'] && $atts['label'] )) {
    return;
  }

  $output = '<div class="stat">';
  $output .= '<span class="stat__value">' . $atts['value'] . '</span>';
  $output .= '<span class="stat__label">' . $atts['label'] . '</span>';
  $output .= '</div>';
  return $output;
}

add_shortcode('stat', 'bsdstarter_stat');

/**
 * Iframed external videos embedded in the WYSIWYG of a detail page
 *
 * @param array  $attr {
 *  Attributes of the video shortcode
 *
 *  @type string $type    The type of the video
 *  @type string $id    The ID of the video
 *  @type string $src    The source of the video
 * }
 * @return string HTML markup to display embedded video
 */
function bsdstarter_video($attr) {

  $atts = shortcode_atts(array(
    'type' => null,
    'id' => null,
    'src' => null
  ), $attr);

  // If there's no video to display, let's get out of here
  if (empty($atts['id']) && empty($atts['src'])) {
    return;
  }


  $output = '';
  $extra_class = '';

  if ($atts['type'] == 'youtube') {
    $atts['src'] = '//www.youtube.com/embed/' . $atts['id'];
  }

  if ($atts['type'] == 'vimeo') {
    $atts['src'] = '//player.vimeo.com/video/' . $atts['id'];
    $extra_class = ' vimeo';
  }

  $class = 'flex-video' . $extra_class;

  $output .= '<div class="'. $class . '">';
  $output .= '<iframe src="' . $atts['src'] .'" frameborder="0" allowfullscreen></iframe>';
  $output .= '</div>';

  return $output;
}

add_shortcode('iframe', 'bsdstarter_video');
