<?php
/**
 * Template Name: Programs Landing Page
 * Controller for the archive view at /programs
 */

$context = Timber::get_context();

$category = (isset($_GET['program_cat']))
  ? get_term_by('slug', $_GET['program_cat'], 'programs') : false;

$context['category'] = $category;
$context['posts'] = Timber::get_posts();
$context['pagination'] = Timber::get_pagination();
Timber::render('programs/archive.twig', $context);
