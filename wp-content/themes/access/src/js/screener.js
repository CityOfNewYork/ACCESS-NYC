/* eslint-env browser */

// Core Modules
import Screener from 'modules/screener';

// ACCESS Patterns
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';

// Patterns Framework
import Tooltips from 'utilities/tooltips/tooltips';
import localize from 'utilities/localize/localize';

(function() {
  'use strict';

  // Initialize eligibility screener.
  let el = document.querySelector(Screener.Selectors.FORM);
  if (el) new Screener(el).init();

  /**
   * Initialize tooltips
   */
  (elements => {
    elements.forEach(element => new Tooltips(element));
  })(document.querySelectorAll(Tooltips.selector));

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

})();
