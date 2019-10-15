/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/features/promise';
import 'core-js/features/array/for-each';
import 'core-js/features/array/find';
import 'core-js/features/array/flat';
import 'core-js/features/array/flat-map';
import 'core-js/features/array/map';
import 'core-js/features/object/assign';
import 'core-js/features/object/from-entries';
import 'core-js/features/object/entries';

// Fetch
import 'whatwg-fetch';

import jQuery from 'jquery';

// Element.prototype.polyfills
import 'modules/polyfill-element-matches';
import 'modules/polyfill-element-remove';

import Alerts from 'modules/alert';
import Tooltip from 'modules/tooltip';
import Utility from 'modules/utility';

// ACCESS Patterns
import Icons from 'elements/icons/icons';
import Accordion from 'components/accordion/accordion';
import Filter from 'components/filter/filter';
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';
import Newsletter from 'objects/newsletter/newsletter';
import TextController from 'objects/text-controller/text-controller';

// Patterns Framework
import Toggle from 'utilities/toggle/toggle';

(function(window, $) {
  'use strict';

  Utility.configErrorTracking(window);

  /** Initialize ACCESS NYC Patterns library components */
  new Icons('/wp-content/themes/access/assets/svg/icons.475e6e65.svg');
  new Toggle();
  new Accordion();
  new Filter();
  new Alerts();

  /** Instantiate Text Controller */
  (element => {
    if (element) new TextController(element);
  })(document.querySelector(TextController.selector));

  /** Instantiate Newsletter and pass it translated strings */
  (element => {
    if (element) {
      let newsletter = new Newsletter(element);
      let strings = Object.fromEntries([
        'NEWSLETTER_VALID_REQUIRED',
        'NEWSLETTER_VALID_EMAIL_REQUIRED',
        'NEWSLETTER_VALID_EMAIL_INVALID',
        'NEWSLETTER_VALID_CHECKBOX_BOROUGH',
        'NEWSLETTER_SUCCESS_CONFIRM_EMAIL',
        'NEWSLETTER_ERR_PLEASE_TRY_LATER',
        'NEWSLETTER_ERR_PLEASE_ENTER_VALUE',
        'NEWSLETTER_ERR_TOO_MANY_RECENT',
        'NEWSLETTER_ERR_ALREADY_SUBSCRIBED',
        'NEWSLETTER_ERR_INVALID_EMAIL'
      ].map(i => [
        i.replace('NEWSLETTER_', ''),
        Utility.localize(i)
      ]));

      newsletter.strings = strings;
      newsletter.form.strings = strings;
    }
  })(document.querySelector(Newsletter.selector));

  /** Initialize the Share Form and Disclaimer */
  (elements => {
    elements.forEach(element => {
      let shareForm = new ShareForm(element);
      let strings = Object.fromEntries([
        'SHARE_FORM_SERVER',
        'SHARE_FORM_SERVER_TEL_INVALID',
        'SHARE_FORM_VALID_REQUIRED',
        'SHARE_FORM_VALID_EMAIL_INVALID',
        'SHARE_FORM_VALID_TEL_INVALID'
      ].map(i => [
        i.replace('SHARE_FORM_', ''),
        Utility.localize(i)
      ]));

      shareForm.strings = strings;
      shareForm.form.strings = strings;

      shareForm.sent = instance => {
        let key = instance.type.charAt(0).toUpperCase() +
          instance.type.slice(1);

        Utility.track(key, [
          {'DCS.dcsuri': `share/${instance.type}`}
        ]);
      };
    });

    new Disclaimer();
  })(document.querySelectorAll(ShareForm.selector));

  let body = document.querySelector('body');

  /** Initialize Mobile Nav Toggle */
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-js*="o-mobile-nav"]'))
      return;

    event.preventDefault();

    body.classList.toggle('overlay');
    body.classList.toggle('active');

    let mobileNav = document.querySelector('#o-mobile-nav');
    mobileNav.classList.toggle('active');
  });

  /** Search Box Control */
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-js*="o-search-box"]'))
      return;

    event.preventDefault();

    let searchBox = document.querySelector('#search');
    searchBox.classList.toggle('active');

    if (searchBox.classList.contains('active')) {
      setTimeout(() => {
        searchBox.querySelector('#search-field').focus();
      }, 20);
    }
  });

  /** Basic click tracking */
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-js*="track"]'))
      return;

    let key = event.target.dataset.trackKey;
    let data = JSON.parse(event.target.dataset.trackData);

    Utility.track(key, data, event);
  });

  // Capture the queries on Search page
  $(window).on('load', function() {
    let $wtSearch = $('[data-js="wt-search"]');
    if (~window.location.href.indexOf('?s=') && $wtSearch.length) {
      let key = $wtSearch.data('wtSearchKey');
      let data = $wtSearch.data('wtSearchData');
      Utility.webtrends(key, data);
    }
  });

  // On the search results page, submits the search form when a category is
  // chosen.
  $('.js-program-search-filter').on('change', 'input', e => {
    $(e.currentTarget).closest('form')[0].submit();
  });

  // Initialize tooltips.
  $(`.${Tooltip.CssClass.TRIGGER}`).each((i, el) => {
    const tooltip = new Tooltip(el);
    tooltip.init();
  });

  // For pages with "print-view" class, print the page on load. Currently only
  // used on program detail pages after the print link is clicked.
  if (document.querySelector('html').classList.contains('print-view')) {
    window.onload = window.print;
  }

  // Add noopener attribute to new window links if it isn't there.
  $('a[target="_blank"]').each(Utility.noopener);

  // Enable environment warnings
  $(window).on('load', () => Utility.warnings());
})(window, jQuery);
