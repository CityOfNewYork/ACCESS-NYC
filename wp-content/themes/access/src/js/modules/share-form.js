// core modules
import Track from 'modules/track';

// ACCESS Patterns
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';

// Patterns Framework
import localize from 'utilities/localize/localize';

// public-facing site key
const siteKey = '6Lf0tTgUAAAAACnS4fRKqbLll_oFxFzeaVfbQxyX';

(() => {
  'use strict';

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
   * Add ReCAPTCHA to Share Form
   */
  (elements => {
    elements.forEach(element => {
      element.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent immediate form submission
  
        grecaptcha.ready(function () {
          grecaptcha.execute(siteKey, {action: 'share_form'}).then(function (token) {
            // Inject the token into the hidden field
            const tokenField = element.querySelector('input[name="g-recaptcha-response"]');
            if (tokenField) {
              tokenField.value = token;
            } else {
              // Defensive fallback
              const hidden = document.createElement('input');
              hidden.type = 'hidden';
              hidden.name = 'g-recaptcha-response';
              hidden.value = token;
              element.appendChild(hidden);
            }
  
            // Finally submit the form
            element.submit();
          });
        });
      });
    });

    new Disclaimer();
  })(document.querySelectorAll(ShareForm.selector));
    
})();

