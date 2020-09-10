<?php

/**
 * Timber/Twig Filter Handler
 *
 * @author NYC Opportunity
 */

use Timber;

/**
 * Filter Filter Hook
 */
add_filter('timber/twig', function($twig) {
  /**
   * Add the c-checklist class to <ul> tags in a string of html content
   *
   * @param   String  $subject  The html containing any <ul> tag
   *
   * @return  String            The html with c-checklist class added
   */
  $twig->addFilter(new Timber\Twig_Filter('add_anyc_checklist', function($subject) {
    return preg_replace("/<ul[^>]*>/", '<ul class="c-checklist">', $subject);
  }));

  /**
   * Add the numeric table class to <table> tags in a string of html content
   *
   * @param   String  $subject  The html containing any <table> tag
   *
   * @return  String            The html with table-numeric class added
   */
  $twig->addFilter(new Timber\Twig_Filter('add_anyc_table_numeric', function($subject) {
    return preg_replace("/<table[^>]*>/", '<table class="table-numeric">', $subject);
  }));

  return $twig;
});
