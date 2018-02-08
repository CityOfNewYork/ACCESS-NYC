/* eslint-env browser */
'use strict';

import $ from 'jquery';
import _ from 'underscore';
import Cleave from 'cleave.js/dist/cleave.min';
import 'cleave.js/dist/addons/cleave-phone.us';

/**
 * Collection of utility functions.
 */
const Utility = {};

/**
 * Get SVG sprite file. See: https://css-tricks.com/ajaxing-svg-sprite/
 * @param  {object} data from get
 */
Utility.svgSprites = function(data) {
  const svgDiv = document.createElement('div');
  svgDiv.innerHTML =
      new XMLSerializer().serializeToString(data.documentElement);
  $(svgDiv).css('display', 'none').prependTo('body');
};

/**
 * Simple toggle that add/removes "active" and "hidden" classes, as well as
 * applying appropriate aria-hidden value to a specified target.
 *
 * Usage;
 *
 * import Utility from Utility;
 *
 * document.querySelector('[data-js*="toggle"]')
 *   .addEventlistener('click', Utility.simpleToggle);
 *
 * <a data-js="toggle" href="#target">Toggle</a>
 *
 * Optional params;
 * data-loc="hash"           Changes the window location hash to #hash.
 * data-hide="#selector"     Queries the selector and toggles them to hidden
 *                           state when the target element is toggled.
 * data-reverse="#selector"  Element to reverse the toggling state.
 *
 * @param  {event} event the onclick event
 */
Utility.simpleToggle = function(event) {
  let el = event.currentTarget;
  event.preventDefault();
  const $target = $(el).attr('href') ?
      $($(el).attr('href')) : $($(el).data('target'));

  $(el).toggleClass('active');
  $target.toggleClass('active hidden')
      .prop('aria-hidden', $target.hasClass('hidden'));

  // function to hide all elements
  if ($(el).data('hide')) {
    $($(el).data('hide')).not($target)
      .addClass('hidden')
      .removeClass('active')
      .prop('aria-hidden', true);
  }

  // Change the window hash if param set
  if ($(el).data('loc')) {
    window.location.hash = $(el).data('loc');
  }

  // Add the toggle event to the toggle reversal element
  if ($(el).data('reverse')) {
    $($(el).data('reverse')).on('click', (event) => {
      event.preventDefault();
      $(el).toggleClass('active');
      $target.toggleClass('active hidden')
        .prop('aria-hidden', $target.hasClass('hidden'));
      $($(el).data('reverse')).off('click');
    });
  }
};

/**
 * Boolean for debug mode
 * @return {boolean} wether or not the front-end is in debug mode.
 */
Utility.debug = () => (Utility.getUrlParameter('debug') === '1');

/**
 * Returns the value of a given key in a URL query string. If no URL query
 * string is provided, the current URL location is used.
 * @param {string} name - Key name.
 * @param {?string} queryString - Optional query string to check.
 * @return {?string} Query parameter value.
 */
Utility.getUrlParameter = (name, queryString) => {
  const query = queryString || window.location.search;
  const param = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
  const regex = new RegExp('[\\?&]' + param + '=([^&#]*)');
  const results = regex.exec(query);
  return results === null ? '' :
      decodeURIComponent(results[1].replace(/\+/g, ' '));
};

/**
 * Takes an object and deeply traverses it, returning an array of values for
 * matched properties identified by the key string.
 * @param {object} object to traverse.
 * @param {string} targetProp name to search for.
 * @return {array} property values.
 */
Utility.findValues = (object, targetProp) => {
  const results = [];

  /**
   * Recursive function for iterating over object keys.
   */
  (function traverseObject(obj) {
    for (let key in obj) {
      if (obj.hasOwnProperty(key)) {
        if (key === targetProp) {
          results.push(obj[key]);
        }
        if (typeof(obj[key]) === 'object') {
          traverseObject(obj[key]);
        }
      }
    }
  })(object);

  return results;
};

/**
 * Takes a string or number value and converts it to a dollar amount
 * as a string with two decimal points of percision.
 * @param {string|number} val - value to convert.
 * @return {string} stringified number to two decimal places.
 */
Utility.toDollarAmount = (val) =>
    (Math.abs(Math.round(parseFloat(val) * 100) / 100)).toFixed(2);

/**
 * For translating strings, there is a global LOCALIZED_STRINGS array that
 * is defined on the HTML template level so that those strings are exposed to
 * WPML translation. The LOCALIZED_STRINGS array is comosed of objects with a
 * `slug` key whose value is some constant, and a `label` value which is the
 * translated equivalent. This function takes a slug name and returns the
 * label.
 * @param {string} slugName
 * @return {string} localized value
 */
Utility.localize = function(slugName) {
  let text = slugName || '';
  const localizedStrings = window.LOCALIZED_STRINGS || [];
  const match = _.findWhere(localizedStrings, {
    slug: slugName
  });
  if (match) {
    text = match.label;
  }
  return text;
};

/**
 * Takes a a string and returns whether or not the string is a valid email
 * by using native browser validation if available. Otherwise, does a simple
 * Regex test.
 * @param {string} email
 * @return {boolean}
 */
Utility.isValidEmail = function(email) {
  const input = document.createElement('input');
  input.type = 'email';
  input.value = email;

  return typeof input.checkValidity === 'function' ?
      input.checkValidity() : /\S+@\S+\.\S+/.test(email);
};

/**
 * For a given number, checks to see if its value is a valid Phone Number.
 * If not, displays an error message and sets an error class on the element.
 * @param {string} number The html form element for the component.
 * @return {boolean}      Valid Phone Number.
 */
Utility.validatePhoneNumber = function(number) {
  let num = Utility.parsePhoneNumber(number); // parse the number
  num = (num) ? num.join('') : 0; // if num is null, there are no numbers
  if (num.length === 10) {
    return true; // assume it is phone number
  }
  return false;
};

/**
 * Get just the phone number of a given value
 * @param  {string} value The string to get numbers from
 * @return {array}        An array with matched blocks
 */
Utility.parsePhoneNumber = function(value) {
  return value.match(/\d+/g); // get only digits
};

/**
 * Mask phone number and properly format it
 * @param  {HTMLElement} input the "tel" input to mask
 * @return {constructor}       the input mask 000-000-0000
 */
Utility.maskPhone = function(input) {
  let cleave = new Cleave(input, {
    phone: true,
    phoneRegionCode: 'us',
    delimiter: '-'
  });
  input.cleave = cleave;
  return input;
};

/**
 * Mask dollar inputs
 * @param  {HTMLElement} input the "float" input to mask
 * @return {constructor}       the input mask 0.00
 */
Utility.maskDollarFloat = function(input) {
  let cleave = new Cleave(input, {
    delimiter: '',
    numeral: true,
    numeralPositiveOnly: true
  });
  input.cleave = cleave;
  return input;
};

/**
 * Convert a camel case string into all caps with underscored spaces.
 * @param  {string} str the string to change, ex. myString
 * @return {string}     the converted string, ex. MY_STRING
 */
Utility.camelToUpper = function(str) {
  return str.replace(/([A-Z])/g, function($1) {
    return '_' + $1;
  }).toUpperCase();
};

/**
 * Tracking function wrapper
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.track = function(key, data) {
  // Set the path name based on the location if 'DCS.dcsuri' exists
  let dcsuri = _.pluck(data, 'DCS.dcsuri')[0];

  const d = (dcsuri) ? _.map(data, function(value) {
    if (value.hasOwnProperty('DCS.dcsuri')) {
      return {'DCS.dcsuri': `${window.location.pathname}${dcsuri}`};
    } return value;
  }) : data;

  /**
   * Webtrends
   */
  /* eslint-disable no-undef */
  if (typeof Webtrends !== 'undefined') {
    let wt = Webtrends;
    /* eslint-enable no-undef */
    let wtData = d;
    let prefix = {};

    prefix['WT.ti'] = key;
    wtData.unshift(prefix);

    // format data for Webtrends
    wtData = {
      argsa: _.flatten(_.map(wtData, function(value) {
        return _.pairs(value);
      }))
    };

    wt.multiTrack(wtData);
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug())
      console.dir([`track: '${key}'`, wtData]);
    /* eslint-enable no-console, no-debugger */
  }

  /**
   * Segment
   * Never use the identify method without consideration for PII
   */
  /* eslint-disable no-undef */
  if (typeof analytics !== 'undefined') {
    // format data for Segment
    let sData = _.reduce(data, (memo, num) => Object.assign(memo, num), {});
    analytics.track(key, sData);
    /* eslint-enable no-undef */
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug())
      console.dir([`track: '${key}'`, sData]);
    /* eslint-enable no-console, no-debugger */
  }
};

/**
 * Site constants.
 * @enum {string}
 */
Utility.CONFIG = {
  DEFAULT_LAT: 40.7128,
  DEFAULT_LNG: -74.0059,
  GOOGLE_API: 'AIzaSyBSjc_JN_p0-_VKyBvjCFqVAmAIWt7ClZc',
  GOOGLE_STATIC_API: 'AIzaSyCt0E7DX_YPFcUnlMP6WHv2zqAwyZE4qIw',
  GRECAPTCHA_SITE_KEY: '6Lf0tTgUAAAAACnS4fRKqbLll_oFxFzeaVfbQxyX',
  SCREENER_MAX_HOUSEHOLD: 8,
  URL_PIN_BLUE: '/wp-content/themes/access/assets/img/map-pin-blue.png',
  URL_PIN_BLUE_2X: '/wp-content/themes/access/assets/img/map-pin-blue-2x.png',
  URL_PIN_GREEN: '/wp-content/themes/access/assets/img/map-pin-green.png',
  URL_PIN_GREEN_2X: '/wp-content/themes/access/assets/img/map-pin-green-2x.png'
};

export default Utility;
