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
  const modal = new Modal();

  /**
   * A set of strings to override the
   */
  Form.strings = {
    'VALID_REQUIRED': 'This is required', // A generic message for required
    // inputs that are missing values.
    'VALID_{{ TYPE }}_INVALID': 'Invalid' // A validation message for a specific
    // type. See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#%3Cinput%3E_types
    // for available types.
  };

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
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        let alert = document.querySelector('[data-alert-name="feedback"]');
        let form = document.getElementById('feedback-form');
        alert.classList.remove('hidden');
        form.classList.add('hidden');
      })
      .catch(error => {
        console.error('There has been a problem with your fetch operation:', error);
      });
  };
})();