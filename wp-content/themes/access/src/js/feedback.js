/* eslint-env browser */

import Forms from '@nycopportunity/patterns-framework/src/utilities/forms/forms';
import Modal from 'pattern-modal/src/modal';
import serialize from 'for-cerial';

(() => {
  'use strict';
  /**
   * Pass the DOM element to the form.
   */
  const form = document.getElementById('feedback-form');
  const Form = new Forms(form);

  new Modal();

  Form.selectors.ERROR_MESSAGE_PARENT = '.c-question__container';

  /**
   * This function automatically watches inputs within the form and displays
   * error messages on the blur event for each input.
   */
  Form.watch();

  /**
   * The submit function for the form.
   */
  Form.submit = (event) => {
    event.preventDefault();

    // To send the data with the application/x-www-form-urlencoded header
    // we need to use URLSearchParams(); instead of FormData(); which uses
    // multipart/form-data
    let data = serialize(Form.FORM, {hash: true});
    let formData = new URLSearchParams();

    Object.keys(data).map(k => {
      formData.append(k, data[k]);
    });

    let html = document.querySelector('html');

    if (html.hasAttribute('lang'))
      formData.append('lang', html.getAttribute('lang'));

    fetch(Form.FORM.getAttribute('action'), {
      method: Form.FORM.getAttribute('method'),
      body: formData
    }).then(response => {
      let alert = document.querySelector('[data-alert-name="feedback"]');
      let form = document.getElementById('feedback-form');

      alert.classList.remove('hidden');
      alert.setAttribute('aria-hidden', 'false');

      form.classList.add('hidden');
      form.setAttribute('aria-hidden', 'true');

      if (process.env.NODE_ENV === 'development')
        console.dir(response);
    }).catch(error => {
      let errorAlert = document.querySelector('[data-alert-name="feedback-error"]');

      errorAlert.classList.remove('hidden');
      errorAlert.setAttribute('aria-hidden', 'false');

      if (process.env.NODE_ENV === 'development')
        console.error('There has been a problem with your fetch operation:', error);
    });
  };
})();
