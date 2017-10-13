/* eslint-env browser */
import jQuery from 'jquery';
import _ from 'underscore';
import SmoothScroll from 'smoothscroll-polyfill';
import ScreenerField from 'modules/screener.field';
import ResultsField from 'modules/results.field';
import ShareForm from 'modules/share-form';
import Tooltip from 'modules/tooltip';
import Utility from 'modules/utility';

(function(window, $) {
  'use strict';

  require('smoothscroll-polyfill').polyfill();

  // Get SVG sprite file.
  $.get('/wp-content/themes/access/assets/img/icons.svg', Utility.svgSprites);

  let $body = $('body');

  // Simple Toggle
  $body.on('click', '[data-js="simple-toggle"]', Utility.simpleToggle);

  // Show/hide share form disclaimer
  $body.on('click', '.js-show-disclaimer', ShareForm.ShowDisclaimer);

  // Initialize eligibility screener.
  $(ScreenerField.Selectors.DOM).each((i, el) =>
    new ScreenerField(el).init());

  // Initialize eligibility screener.
  $(ResultsField.Selectors.DOM).each((i, el) =>
    new ResultsField(el).init());

  // Initialize tooltips.
  $(`.${Tooltip.CssClass.TRIGGER}`).each((i, el) =>
    new Tooltip(el).init());

  // Application reloading
  $('[data-js="reload"]').each((i, el) => {
    $(el).on('click', (event) => {
      event.preventDefault();
      let message = _.findWhere(window.LOCALIZED_STRINGS,
          {slug: 'MSG_RELOAD'}
        ).label;
      let dialogue = confirm(message);
      if (dialogue) {
        if (event.currentTarget.hash) {
          window.location = event.currentTarget.hash;
        } else {
          location.reload();
        }
      }
      return false;
    });
  });

})(window, jQuery);
