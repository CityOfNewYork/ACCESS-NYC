<?php

/**
 * Plugin Name: ICL LS Languages
 * Description: Modifying program url in the language switcher
 * Author: Blue State Digital
 */

add_filter('icl_ls_languages', function($languages) {
  global $sitepress;

  if (isset($_GET['program_cat'])) {
    $cur_prog = $_GET['program_cat'];
    $original_lang = ICL_LANGUAGE_CODE; // Save the current language

    // switch to english to capture the original taxonomies
    if ($original_lang != 'en') {
      $sitepress->switch_lang('en');
    }

    // retrieve the program taxonomies as array
    $terms = get_terms(array(
      'taxonomy' => 'programs',
      'hide_empty' => false,
    ));

    // switch back to the original language
    $sitepress->switch_lang($original_lang);

    // find the en taxonomy that matches the current program
    foreach ($terms as $term) {
      if (strpos($cur_prog, $term->slug) !== false) {
        $prog = $term->slug;
      }
    }

    // reconstruct the language url based on the program filter
    if (strpos(basename($_SERVER['REQUEST_URI']), 'program_cat') !== false) {
      foreach ($languages as $lang_code => $language) {
        if ($lang_code == 'en') {
          $newlang_code = "";
          $languages[$lang_code]['url'] = '/programs/?program_cat=' . $prog;
        } elseif ($lang_code != 'en' || $lang_code != '') {
          // if not english, then remove the language code and add the correct one
          $languages[$lang_code]['url'] = '/' . $lang_code .
            '/programs/?program_cat=' . $prog . '-' . $lang_code;
        }
      }
    }
  }

  return $languages;
});
