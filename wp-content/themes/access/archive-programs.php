<?php
/**
 * Template Name: Programs Landing Page
 * Controller for the archive view at /programs
 */

script('main');

$context = Timber::get_context();

if (isset($_GET['program_cat'])) {
  $context['programCategory'] = get_term_by('slug', $_GET['program_cat'], 'programs');
} else {
  $context['programCategory'] = '';
}

$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();
Timber::render('program-landing.twig', $context);
