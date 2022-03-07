/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';

/**
 * Requires Documentation
 *
 * @class
 */
class Results {
  /**
   * @param {HTMLElement} el - The form element for the component.
   *
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;

    this._disabled = false;
  }

  /**
   * If this component has not yet been initialized, attaches event listeners.
   *
   * @method
   *
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

    // Finalize form
    $(Results.Selectors.FINAL_RESULTS).on('submit', this._finalResults);

    // Open links in new window
    $el.on('click', Results.Selectors.HYPERLINKS, this._targetBlank);

    // Remove programs
    $el.on('click', Results.Selectors.REMOVE_PROGRAM, event => {
      this._removeProgram(event);
    });

    this._initialized = true;

    return this;
  }

  /**
   * Open links in new window by adding target blank to them.
   *
   * @param  {event} event the onclick event
   */
  _targetBlank(event) {
    $(event.currentTarget)
      .attr('rel', 'noopener noreferrer')
      .attr('target', '_blank');
  }

  /**
   * Wrapper for ajax call
   *
   * @param {object}   data     - the data to send, should include programs,
   *                              categories, guid, and date.
   * @param {object}   headers  - headers to pass to the requests.
   * @param {function} callback - the callback function to execute when done.
   */
  _getUrl(data, headers, callback) {
    data['path'] = Results.SharePath;

    const action = {
      url: Results.ShareUrlEndpoint,
      type: 'get',
      data: data,
      headers: headers
    };

    $.ajax(action).done(data => {
      callback(data);
    });
  }

  /**
   * Bus for removing programs from results
   *
   * @param  {event} event the onclick event
   */
  _removeProgram(event) {
    event.preventDefault();

    const code = event.currentTarget.dataset.removeCode;
    const id = event.currentTarget.dataset.removeId;

    this._updateDOM(id);
    this._updateURL(code);
  }

  /**
   * Hide the program in the DOM
   *
   * @param  {string} code - the program code to update
   */
  _updateDOM(code) {
    const card = $(`[data-code="${code}"]`);
    const selected = card.closest(Results.Selectors.SELECTED_PROGRAMS);
    const additional = card.closest(Results.Selectors.ADDITIONAL_PROGRAMS);
    const parent = (selected.length) ? selected : additional;
    let length = parent.find(Results.Selectors.PROGRAMS_LIST).children();
    let total = $(Results.Selectors.PROGRAMS_LIST).children();

    // Hide the card
    card.attr('aria-hidden', true)
      .addClass('hidden hide-for-print')
      .hide();

    // Get updated length of list
    length = parent.find(Results.Selectors.PROGRAMS_LIST)
      .children().filter(':not(.hidden)').length;

    // Update the length in the parent bucket
    parent.find(Results.Selectors.PROGRAMS_LENGTH).html(length);

    // Update the total programs count
    total = $(Results.Selectors.PROGRAMS_LIST)
      .children().filter(':not(.hidden)').length;

    $(Results.Selectors.PROGRAMS_TOTAL).html(total);

    // Switch to singular text if only one program is left
    if (length === 1) {
      parent.find(Results.Selectors.PROGRAMS_SINGULAR)
        .attr('aria-hidden', false)
        .removeClass('hidden hide-for-print');
      parent.find(Results.Selectors.PROGRAMS_PLURAL)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print');
    // Hide title if list is empty
    } else if (length <= 0) {
      parent.find(Results.Selectors.PROGRAMS_TITLE)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print')
        .hide();
      parent.find(Results.Selectors.PROGRAMS_LIST)
        .attr('aria-hidden', true)
        .addClass('hidden hide-for-print')
        .hide();
    }
  }

  /**
   * Trim the program from the url, and retrieve a new hash for the updated url.
   *
   * @param  {string} code - the code to remove from the share string
   *
   * @return {null}
   */
  _updateURL(code) {
    const shareUrl = $(Results.Selectors.SHARE_URLS)[0].value;
    const hash = $(Results.Selectors.SHARE_HASH)[0].value;

    if (shareUrl === '' && hash === '') return false;

    const categories = Utility.getUrlParameter('categories', shareUrl);
    const guid = Utility.getUrlParameter('guid', shareUrl);
    const date = Utility.getUrlParameter('date', shareUrl);

    let programs = Utility.getUrlParameter('programs', shareUrl).split(',');
    let request = {};
    let headers = {};

    const index = programs.indexOf(code);

    // Remove program from url list
    if (index > -1) programs.splice(index, 1);

    headers['x-bsd-smnyc-token'] = hash;
    request['url'] = shareUrl;

    if (programs[0] != '') request['programs'] = programs.join('%2C');
    if (categories[0] != '') request['categories'] = categories;
    if (guid != '') request['guid'] = guid;
    if (date != '') request['date'] = date;

    // Get updated share url
    this._getUrl(request, headers, data => {
      // Update programs list
      $(Results.Selectors.SHARE_PROGRAMS).each((index, element) => {
        element.value = programs;
      });

      // Update share url fields
      $(Results.Selectors.SHARE_URLS).each((index, element) => {
        element.value = data['url'];
      });

      // Udate the hash fields
      $(Results.Selectors.SHARE_HASH).each((index, element) => {
        element.value = data['hash'];
      });
    });
  }

  /**
   * Submit the final url to the database and disable the ability to
   * modify the url any further.
   *
   * @param  {object} event The finalize results form submission event.
   */
  _finalResults(event) {
    const action = $(event.currentTarget).attr('action');
    const data = $(event.currentTarget).serializeArray();

    let $buttons = $(event.currentTarget).find('button');
    let $spinner = $(event.currentTarget).find(Results.Selectors.SPINNER);

    event.preventDefault();

    if (this._disabled) return;

    this._disabled = true;

    $buttons.hide();
    $spinner.show();

    let payload = {};

    payload['action'] = 'response_update';

    for (let i = 0; i < data.length; i++) {
      payload[data[i].name] = data[i].value;
    }

    $.post(action, payload).done(response => {
      $(this).remove();
      $(Results.Selectors.REMOVE_CONTAINER).remove();
      $(Results.Selectors.SHARE_RESULTS)
        .toggleClass('hidden')
        .prop('aria-hidden', false);
    }).fail(response => {
      this._disabled = false;
      $buttons.show();
      $spinner.hide();

      alert('Something went wrong. Please try again later.');
      /* eslint-disable */
      if (Utility.debug()) console.log(response);
      /* eslint-enable*/
    }).always(() => {
      /* eslint-disable */
      if (Utility.debug()) console.log('Submission complete.');
      /* eslint-enable*/
    });
  }
}

/**
 * Selectors for the results page
 * @type {Object}
 */
Results.Selectors = {
  'ADDITIONAL_PROGRAMS': '[data-js="additional-programs"]',
  'DOM': '[data-js="results"]',
  'FINAL_RESULTS': '[data-js="final-results"]',
  'HYPERLINKS': 'a[href*=""]',
  'REMOVE_CONTAINER': '[id*="remove-"]',
  'REMOVE_PROGRAM': '[data-js*="remove-program"]',
  'SHARE_URLS': 'input[name="url"]',
  'SHARE_HASH': 'input[name="hash"]',
  'SHARE_PROGRAMS': 'input[name="programs"]',
  'SHARE_RESULTS': '[data-js="share-results"]',
  'SPINNER': '[data-js="spinner"]',
  'SELECTED_PROGRAMS': '[data-js="selected-programs"]',
  'PROGRAMS_TOTAL': '[data-js="programs-total"]',
  'PROGRAMS_LENGTH': '[data-js="programs-length"]',
  'PROGRAMS_LIST': '[data-js="programs-list"]',
  'PROGRAMS_TITLE': '[data-js="programs-title"]',
  'PROGRAMS_SINGULAR': '[data-js="programs-singular"]',
  'PROGRAMS_PLURAL': '[data-js="programs-plural"]'
};

/**
 * The endpoint for retrieving a new share URL
 * @type {String}
 */
Results.ShareUrlEndpoint = '/wp-json/api/v1/shareurl/';

/**
 * The base path for the URL
 * @type {String}
 */
Results.SharePath = '/eligibility/results/';

export default Results;
