/* eslint-env browser */
import jQuery from 'jquery';
import _ from 'underscore';

import 'core-js/features/promise';
import 'core-js/features/array/for-each';

import 'whatwg-fetch';

// eslint-disable-next-line no-unused-vars
import 'modules/polyfill-window-scroll';

// Patterns Framework
import 'utilities/element/matches';
import 'utilities/element/closest';
import 'utilities/element/remove';
import 'utilities/nodelist/foreach';

// Local Modules
import Screener from 'modules/screener';
import Results from 'modules/results';
import Utility from 'modules/utility';

// Patterns Framework
import Accordion from 'components/accordion/accordion';
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';
import AlertBanner from 'objects/alert-banner/alert-banner';

// Patterns Framework
import Icons from 'utilities/icons/icons';
import Toggle from 'utilities/toggle/toggle';

(function(window, $) {
  'use strict';

  Utility.configErrorTracking(window);

  let $body = $('body');

  // A basic click tracking function
  $body.on('click', '[data-js*="track"]', event => {
    let key = event.currentTarget.dataset.trackKey;
    let data = JSON.parse(event.currentTarget.dataset.trackData);
    Screener.track(key, data);
  });

  // Initialize eligibility screener.
  $(Screener.Selectors.DOM).each((i, el) =>
    new Screener(el).init());

  // Initialize eligibility screener.
  $(Results.Selectors.DOM).each((i, el) =>
    new Results(el).init());

  /** Initialize ACCESS NYC Patterns library components */
  new Icons('/wp-content/mu-plugins/anyc-field-screener/assets/svg/icons.475e6e65.svg');
  new Accordion();
  new Toggle();

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

  /**
   * Instantiate Alert Banner
   */
  (element => {
    if (element) new AlertBanner(element);
  })(document.querySelector(AlertBanner.selector));

  // Application reloading
  $('[data-js="reload"]').each((i, el) => {
    $(el).on('click', event => {
      event.preventDefault();
      let message = _.findWhere(window.LOCALIZED_STRINGS,
          {slug: 'MSG_RELOAD'}
        ).label;
      let dialogue = confirm(message);
      if (dialogue) {
        if (event.currentTarget.href) {
          window.location = event.currentTarget.href;
        } else {
          location.reload();
        }
      }
      return false;
    });
  });

  // Add noopener attribute to new window links if it isn't there.
  $('a[target="_blank"]').each(Utility.noopener);

  // Enable environment warnings
  $(window).on('load', () => Utility.warnings());
})(window, jQuery);
