<?php
/**
 * Program detail page
 */

enqueue_language_style('style');
enqueue_inline('rollbar');
enqueue_inline('webtrends');
enqueue_inline('data-layer');
enqueue_inline('google-optimize');
enqueue_inline('google-analytics');
enqueue_inline('google-tag-manager');
enqueue_script('programs');

$context = Timber::get_context();

// Gets the url parameter on the page for navigating each section.
if (isset($_GET['step'])) {
  $context['step'] = urlencode(
    validate_params('step', urldecode(htmlspecialchars($_GET['step'])))
  );
} else {
  $context['step'] = '';
}

$query = ($context['step'] !== '') ? '?step='.$context['step'] : '';

$post = Timber::get_post();
$templates = array('programs/single.twig');
$context['post'] = $post;

// Share by email/sms fields.
$context['shareAction'] = admin_url('admin-ajax.php');
$context['shareUrl'] = $post->link.$query;
$context['shareHash'] = \SMNYC\hash($context['shareUrl']);

Timber::render($templates, $context);
