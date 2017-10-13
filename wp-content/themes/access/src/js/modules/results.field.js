/* eslint-env browser */
'use strict';

import $ from 'jquery';
import ShareForm from 'modules/share-form';
import Utility from 'modules/utility';

/**
 * Requires Documentation
 * @class
 */
class ResultsField {
  /**
   * @param {HTMLElement} el - The form element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;
  }

  /**
   * If this component has not yet been initialized, attaches event listeners.
   * @method
   * @return {this} OfficeMap
   */
  init() {
    if (this._initialized) {
      return this;
    }

    /**
     * DOM Event Listeners
     */

    let $el = $(this._el);

    // Initialize share by email/sms forms.
    $(`.${ShareForm.CssClass.FORM}`).each((i, el) => new ShareForm(el).init());

    // Open links in new window
    $el.on('click', ResultsField.Selectors.HYPERLINK, this._targetBlank);

    // Remove programs
    $el.on('click', ResultsField.Selectors.REMOVE_PROGRAM, this._removeProgram);

    this._initialized = true;
    return this;
  }

  /**
   * Open links in new window by adding target blank to them.
   * @param  {event} event the onclick event
   */
  _targetBlank(event) {
    $(event.currentTarget).attr('target', '_blank');
  }

  /**
   * Remove program from results, and trim the sharable url
   * @param  {event} event the onclick event
   */
  _removeProgram(event) {
    let categories = Utility.getUrlParameter('categories').split(',');
    let programs = Utility.getUrlParameter('programs').split(',');
    let guid = Utility.getUrlParameter('guid');
    let date = Utility.getUrlParameter('date');
    let removeCode = event.currentTarget.dataset.removeCode;
    let index = programs.indexOf(removeCode);
    let location = window.location;
    let shareUrl = [location.origin, location.pathname, '?'].join('');
    let card = $(`[data-code="${removeCode}"]`);
    let selected = card.closest(ResultsField.Selectors.SELECTED_PROGRAMS);
    let additional = card.closest(ResultsField.Selectors.ADDITIONAL_PROGRAMS);
    let parent = (selected.length) ? selected : additional;
    let length = parent.find(ResultsField.Selectors.PROGRAMS_LIST).children();

    event.preventDefault();

    // Hide the card
    card.attr('aria-hidden', true)
      .addClass('hidden hide-for-print')
      .hide();

    // Get updated length of list
    length = parent.find(ResultsField.Selectors.PROGRAMS_LIST)
      .children().filter(':not(.hidden)').length;

    // Update the length if available
    parent.find(ResultsField.Selectors.PROGRAMS_LENGTH).html(length);

    // Switch to singular text if only one program is left
    if (length === 1) {
      parent.find(ResultsField.Selectors.PROGRAMS_SINGULAR)
        .attr('aria-hidden', false)
        .removeClass('hidden hide-for-print');
      parent.find(ResultsField.Selectors.PROGRAMS_PLURAL)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print');
    // Hide title if list is empty
    } else if (length <= 0) {
      parent.find(ResultsField.Selectors.PROGRAMS_TITLE)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print')
        .hide();
      parent.find(ResultsField.Selectors.PROGRAMS_LIST)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print')
        .hide();
    }

    // Remove program from url list
    if (index > -1) programs.splice(index, 1);

    // Create updated share url
    shareUrl += [
      ['categories=', categories.join('%2C')].join(''),
      ['programs=', programs.join('%2C')].join(''),
      ['guid=', guid].join(''),
      ['date=', date].join('')
    ].join('&');

    // Update share url fields
    $(ResultsField.Selectors.SHARE_URLS).each((index, element) => {
      element.value = shareUrl;
    });

    return shareUrl;
  }

}

/**
 * Selectors for the results page
 * @type {Object}
 */
ResultsField.Selectors = {
  'ADDITIONAL_PROGRAMS': '[data-js="additional-programs"]',
  'DOM': '[data-js="results"]',
  'HYPERLINKS': 'a[href*]',
  'REMOVE_PROGRAM': '[data-js="remove-program"]',
  'SHARE_URLS': 'input[name="url"]',
  'SELECTED_PROGRAMS': '[data-js="selected-programs"]',
  'PROGRAMS_LENGTH': '[data-js="programs-length"]',
  'PROGRAMS_LIST': '[data-js="programs-list"]',
  'PROGRAMS_TITLE': '[data-js="programs-title"]',
  'PROGRAMS_SINGULAR': '[data-js="programs-singular"]',
  'PROGRAMS_PLURAL': '[data-js="programs-plural"]'
}

export default ResultsField;
