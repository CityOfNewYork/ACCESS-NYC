/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';


/**
 * This component handles validation and submission for share by email and
 * share by SMS forms.
 * @class
 */
class ShareForm {
  /**
   * @param {HTMLElement} el - The html form element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {boolean} Whether this form is valid. */
    this._isValid = false;

    /** @private {boolean} Whether the form is currently submitting. */
    this._isBusy = false;

    /** @private {boolean} Whether the form is disabled. */
    this._isDisabled = false;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;
  }

  /**
   * If this component has not yet been initialized, attaches event listeners.
   * @method
   * @return {this} ShareForm
   */
  init() {
    if (this._initialized) {
      return this;
    }

    $(this._el).on('submit', (e) => {
      e.preventDefault();
      this._validate();
      if (this._isValid && !this._isBusy && !this._isDisabled) {
        this._submit();
      }
    });

    this._initialized = true;

    return this;
  }

  /**
   * Runs validation rules and sets validity of component.
   * @method
   * @return {this} ShareForm
   */
  _validate() {
    let validity = true;
    const $email = $(this._el).find('input[type="email"]');
    const $tel = $(this._el).find('input[type="tel"]');

    // Clear any existing error messages.
    $(this._el).find(`.${ShareForm.CssClass.ERROR_MSG}`).remove();

    if ($email.length) {
      validity = this._validateRequired($email[0]) &&
          this._validateEmail($email[0]);
    }

    if ($tel.length) {
      validity = this._validateRequired($tel[0]) &&
          this._validatePhoneNumber($tel[0]);
    }

    this._isValid = validity;
    if (this._isValid) {
      $(this._el).removeClass(ShareForm.CssClass.ERROR);
    }
    return this;
  }

  /**
   * For a given input, checks to see if its value is a valid email. If not,
   * displays an error message and sets an error class on the element.
   * @param {HTMLElement} input
   * @return {boolean}
   */
  _validateEmail(input) {
    if (!$(input).val()) {
      return false;
    }
    if (!Utility.isValidEmail($(input).val())) {
      this._showError(ShareForm.Message.EMAIL);
      $(input).one('keyup', (e) => {
        this._validate();
      });
      return false;
    } else {
      return true;
    }
  }

  /**
   * For a given input, checks to see if its value is a valid Phone Number.
   * If not, displays an error message and sets an error class on the element.
   * @param {HTMLElement} input - The html form element for the component.
   * @return {boolean} - Valid Phone Number.
   */
  _validatePhoneNumber(input) {
    let valid = Utility.validatePhoneNumber(input.value);
    if (valid) return true;
    this._showError(ShareForm.Message.PHONE);
    return false;
  }

  /**
   * For a given input, checks to see if it has a value. If not, displays an
   * error message and sets an error class on the element.
   * @method
   * @param {HTMLElement} input
   * @return {boolean}
   */
  _validateRequired(input) {
    if ($(input).val()) {
      return true;
    } else {
      this._showError(ShareForm.Message.REQUIRED);
      $(input).one('keyup', (e) => {
        this._validate();
      });
      return false;
    }
  }

  /**
   * Displays an error message by appending a div to the form.
   * @param {string} msg - Error message to display.
   * @return {this} ShareForm
   */
  _showError(msg) {
    const $error = $(document.createElement('div'));
    $error.addClass(ShareForm.CssClass.ERROR_MSG).text(Utility.localize(msg));
    $(this._el).addClass(ShareForm.CssClass.ERROR).append($error);
    return this;
  }

  /**
   * Adds a "success" class.
   * @return {this} ShareForm
   */
  _showSuccess() {
    $(this._el).addClass(ShareForm.CssClass.SUCCESS);
    return this;
  }

  /**
   * Submits the form.
   * @return {jqXHR} deferred response object
   */
  _submit() {
    this._isBusy = true;
    let $tel = this._el.querySelector('input[type="tel"]'); // get phone number
    let $submit = this._el.querySelector('button[type="submit"]');
    let $spinner = this._el.querySelector(`.${ShareForm.CssClass.SPINNER}`);
    let $inputs = $(this._el).find('input');
    if ($tel) $tel.value = $tel.cleave.getRawValue(); // sanitize phone number
    const payload = $(this._el).serialize();
    $inputs.prop('disabled', true); // disable inputs
    if ($spinner) {
      $submit.style.cssText = 'display: none'; // hide submit button
      $spinner.style.cssText = ''; // show spinner
    }
    return $.post($(this._el).attr('action'), payload).done((response) => {
      if (response.success) {
        this._showSuccess();
        this._isDisabled = true;
        $(this._el).one('keyup', 'input', () => {
          $(this._el).removeClass(ShareForm.CssClass.SUCCESS);
          this._isDisabled = false;
        });
      } else {
        this._showError(ShareForm.Message.SERVER);
      }
    }).fail((response) => {
      this._showError(ShareForm.Message.SERVER);
    }).always(() => {
      $inputs.prop('disabled', false); // enable inputs
      if ($tel) $tel.cleave.setRawValue($tel.value); // reformat phone number
      if ($spinner) {
        $submit.style.cssText = ''; // show submit button
        $spinner.style.cssText = 'display: none'; // hide spinner;
      }
      this._isBusy = false;
    });
  }
}

/**
 * CSS classes used by this component.
 * @enum {string}
 */
ShareForm.CssClass = {
  ERROR: 'error',
  ERROR_MSG: 'error-message',
  FORM: 'js-share-form',
  HIDDEN: 'hidden',
  SUBMIT_BTN: 'btn-submit',
  SUCCESS: 'success',
  SPINNER: 'js-spinner'
};

/**
 * Localization labels of form messages.
 * @enum {string}
 */
ShareForm.Message = {
  EMAIL: 'ERROR_EMAIL',
  PHONE: 'ERROR_PHONE',
  REQUIRED: 'ERROR_REQUIRED',
  SERVER: 'ERROR_SERVER'
};

export default ShareForm;
