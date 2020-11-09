/* eslint-env browser */

import StepByStep from 'modules/step-by-step';

// ACCESS Patterns
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';

// Patterns Framework
import localize from 'utilities/localize/localize';

(() => {
  'use strict';

  /**
   * Instantiate the Program Guide
   */
  (element => {
    if (element) new StepByStep(element);
  })(document.querySelector(StepByStep.selector));

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

