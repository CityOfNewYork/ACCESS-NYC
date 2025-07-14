// core modules
import Track from 'modules/track';

// ACCESS Patterns
import ShareForm from 'components/share-form/share-form';
import Disclaimer from 'components/disclaimer/disclaimer';

// Patterns Framework
import localize from 'utilities/localize/localize';

class ShareFormAccess extends ShareForm {
  constructor(element) {
    super(element);

    // Public-facing ReCAPTCHA site key
    this.siteKey = document.querySelector('meta[name="g_recaptcha_site_key"]').content;
  }
  
  /**
   * POSTs the serialized form data using the Fetch Method
   * Overrides ShareForm to add ReCAPTCHA
   * @return {Promise} Fetch promise
   */
  submit() {
    // To send the data with the application/x-www-form-urlencoded header
    // we need to use URLSearchParams(); instead of FormData(); which uses
    // multipart/form-data
    let formData = new URLSearchParams();

    Object.keys(this._data).map(k => {
      formData.append(k, this._data[k]);
    });

    let html = document.querySelector('html');

    if (html.hasAttribute('lang')){
      formData.append('lang', html.getAttribute('lang'));
    }

    // Return a promise that resolves only after the token is fetched and added
    return new Promise((resolve, reject) => {
      grecaptcha.enterprise.ready(() => {
        grecaptcha.enterprise.execute(this.siteKey, {action: 'submit'}).then(token => {
          formData.set("g-recaptcha-response", token);

          fetch(this.form.FORM.getAttribute('action'), {
            method: this.form.FORM.getAttribute('method'),
            body: formData
          })
          .then(resolve)
          .catch(reject);
        }).catch(reject); // catch reCAPTCHA error
      });
    });
  }

}

(() => {
  'use strict';

  /**
   * Initialize the Share Form and Disclaimer
   */
  (elements => {
    elements.forEach(element => {
      let shareForm = new ShareFormAccess(element);
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
  })(document.querySelectorAll(ShareFormAccess.selector));
    
})();
