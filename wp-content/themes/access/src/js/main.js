/* eslint-env browser */
// Core Modules
import RollbarConfigure from 'modules/rollbar-configure';
import Track from 'modules/track';
import TranslateElement from 'modules/google-translate-element';

// ACCESS Patterns
import Accordion from 'components/accordion/accordion';
import Filter from 'components/filter/filter';
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';
import AlertBanner from 'objects/alert-banner/alert-banner';
import Newsletter from 'objects/newsletter/newsletter';
import TextController from 'objects/text-controller/text-controller';

// Patterns Framework
// import Track from 'utilities/track/track'; TODO: modify src for compatibility
import Icons from 'utilities/icons/icons';
import Toggle from 'utilities/toggle/toggle';
import Copy from 'utilities/copy/copy';
import localize from 'utilities/localize/localize';
import WebShare from 'utilities/web-share/web-share';

(function(window) {
  'use strict';

  /**
   * Configure Rollbar
   */
  new RollbarConfigure();

  /**
   * Instantiate ACCESS NYC Patterns
   */
  new Icons('/wp-content/themes/access/assets/svg/icons.3fd5a989.svg');
  new Toggle();
  new Accordion();
  new Filter();
  new Copy();

  /**
   * Instantiate Web Share and tracking callback
   */
  new WebShare({
    callback: () => {
      Track.event('Web Share', [
        {action: 'web-share/shared'}
      ]);
    },
    fallback:() => {
      new Toggle({
        selector: WebShare.selector
      });
    }
  });

  /**
   * Instantiate Alert Banner
   */
  (element => {
    if (element) new AlertBanner(element);
  })(document.querySelector(AlertBanner.selector));

  /**
   * Instantiate Text Controller
   */
  (element => {
    if (element) new TextController(element);
  })(document.querySelector(TextController.selector));

  /**
   * Instantiate Newsletter and pass it translated strings
   */
  (elements => {
    elements.forEach(element => {
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
        localize(i)
      ]));

      newsletter.strings = strings;
      newsletter.form.strings = strings;
    });
  })(document.querySelectorAll(Newsletter.selector));

  /**
   * Initialize the Share Form and Disclaimer
   */
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
        localize(i)
      ]));

      shareForm.strings = strings;
      shareForm.form.strings = strings;

      shareForm.sent = instance => {
        let key = instance.type.charAt(0).toUpperCase() +
          instance.type.slice(1);

        Track.event(key, [
          {'DCS.dcsuri': `share/${instance.type}`}
        ]);
      };
    });

    new Disclaimer();
  })(document.querySelectorAll(ShareForm.selector));

  /**
   *
   */

  let body = document.querySelector('body');

  /**
   * Initialize Mobile Nav Toggle
   */
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-js*="o-mobile-nav"]'))
      return;

    event.preventDefault();

    body.classList.toggle('overlay');
    body.classList.toggle('active');

    document.querySelector('#o-mobile-nav')
      .classList.toggle('active');
  });

  /**
   * Search Box Control
   */
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

  /**
   * Basic click tracking
   */
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-js*="track"]'))
      return;

    let key = event.target.dataset.trackKey;
    let data = JSON.parse(event.target.dataset.trackData);

    Track.event(key, data, event);
  });

  /**
   * Capture the queries on Search page
   */
  (element => {
    if (~window.location.href.indexOf('?s=') && element) {
      window.addEventListener('load', () => {
        let key = element.dataset.wtSearchKey;
        let data = JSON.parse(element.dataset.wtSearchData);

        Track.webtrends(key, data);
      });
    }
  })(document.querySelector('[data-js="wt-search"]'));

  /**
   * Submit the search form when a category is chosen.
   */
  (element => {
    if (element) {
      let submitSearch = event => {
        event.target.closest('form').submit();
      };

      element.addEventListener('change', submitSearch);
      element.addEventListener('input', submitSearch);
    }
  })(document.querySelector('[data-js="program-search-filter"]'));

  /**
   * For pages with "print-view" class, print the page on load. Currently only
   * used on program detail pages after the print link is clicked.
   */
  if (document.querySelector('html').classList.contains('print-view')) {
    window.onload = window.print;
  }

  (elements => {
    elements.forEach(element => {
      element.addEventListener('click', event => {
        event.preventDefault();

        window.print();
      });
    });
  })(document.querySelectorAll('[data-js*="window-print"]'));

  /**
   * Add noopener attribute to new window links if it isn't there.
   */
  (elements => {
    elements.forEach((element) => {
      let rel = (element.hasAttribute('rel'))
        ? `${element.getAttribute('rel')} ` : '';

      if (rel.indexOf('noopener') === -1) {
        element.setAttribute('rel', `${rel}noopener`);
      }
    });
  })(document.querySelectorAll('a[target="_blank"]'));

  /**
   * Instantiate Google Translate Element
   */
  (element => {
    if (element) {
      new TranslateElement(element);
    }
  })(document.querySelector(TranslateElement.selector));
})(window);
