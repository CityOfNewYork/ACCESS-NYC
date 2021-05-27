/* eslint-env browser */

import Forms from '@nycopportunity/patterns-framework/src/utilities/forms/forms';
import Modal from '@nycopportunity/pattern-modal/src/modal';
import serialize from 'for-cerial';
import Spinner from '@nycopportunity/pttrn-scripts/src/spinner/spinner';

(() => {
  'use strict';

  /**
   * Instantiate Form and Modal modules
   */

  const Form = new Forms(document.getElementById('feedback-form'));

  new Modal();

  Form.selectors.ERROR_MESSAGE_PARENT = '.c-question__container';

  Form.watch(); // Automatically watch for input errors on blur

  window.reCaptchaCallback = () => { };

  /**
   * The form submission handler
   *
   * @param   {Object}  event  Form submission event
   */
   Form.submit = (event) => {
    event.preventDefault();

    recaptcha();
  };

  /**
   * Add loading spinner to the DOM
   */
  const loading = () => {
    let container = document.getElementById('modal-body');
    let spinner = new Spinner();
    let loading = document.createElement('div');

    Form.FORM.classList.add('hidden');
    Form.FORM.setAttribute('aria-hidden', 'true');

    loading.classList.add('flex', 'justify-center', 'items-center', 'text-yellow-access');
    loading.id = 'feedback-spinner';
    loading.appendChild(spinner);

    container.appendChild(loading);
  };

  /**
   * Add reCaptcha
   */
  const recaptcha = () => {
    let questions = Form.FORM.querySelector('[data-js*="questions"]');
    let questionRecaptcha = Form.FORM.querySelector('[data-js*="question-recaptcha"]');

    questions.classList.add('hidden');
    questions.setAttribute('aria-hidden', 'true');

    questionRecaptcha.classList.remove('hidden');
    questionRecaptcha.setAttribute('aria-hidden', 'false');

    window.grecaptcha.render(Form.FORM.querySelector('[data-js="recaptcha"]'), {
      'sitekey': '6Lf0tTgUAAAAACnS4fRKqbLll_oFxFzeaVfbQxyX',
      'callback': () => {
        loading();
        submit();
      },
      'error-callback': () => {
        failure();
      }
    });
  };

  /**
   * Hide the Spinner and show the success message
   */
  const success = () => {
    let alert = document.querySelector('[data-js="feedback-alert"]');
    let spinnerEl = document.getElementById('feedback-spinner');

    spinnerEl.classList.add('hidden');

    alert.classList.remove('hidden');
    alert.setAttribute('aria-hidden', 'false');
  };

  /**
   * Hide the spinner and show the failure message
   */
  const failure = () => {
    let alert = document.querySelector('[data-js="feedback-alert-error"]');
    let spinnerEl = document.getElementById('feedback-spinner');

    spinnerEl.classList.add('hidden');

    alert.classList.remove('hidden');
    alert.setAttribute('aria-hidden', 'false');
  };

  /**
   * Use fetch to submit the request
   */
  const submit = () => {
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
    .then(response => response.json())
    .then(response => {
      if (response.success) {
        success();
      } else {
        failure();
      }

      if (process.env.NODE_ENV === 'development')
        console.dir(response); // eslint-disable-line no-console
    }).catch(error => {
      failure();

      if (process.env.NODE_ENV === 'development')
        console.error('There has been a problem with your fetch operation:', error); // eslint-disable-line no-console
    });
  };
})();
