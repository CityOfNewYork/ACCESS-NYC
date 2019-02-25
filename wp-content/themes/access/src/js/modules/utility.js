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
  svgDiv.innerHTML = new XMLSerializer()
    .serializeToString(data.documentElement);
  svgDiv.setAttribute('aria-hidden', true);
  svgDiv.setAttribute('style', 'display:none;');
  $(svgDiv).prependTo('body');
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
 * WPML translation. The LOCALIZED_STRINGS array is composed of objects with a
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
 * @return {object}          The final data object
 */
Utility.track = function(key, data) {
  // Set the path name based on the location if 'DCS.dcsuri' exists
  let dcsuri = _.pluck(data, 'DCS.dcsuri')[0];

  const d = (dcsuri) ? _.map(data, function(value) {
      if (value.hasOwnProperty('DCS.dcsuri')) {
        return {'DCS.dcsuri': `${window.location.pathname}${dcsuri}`};
      } return value;
    }) : data;

  /* eslint-disable no-undef */
  /** Webtrends */
  if (typeof Webtrends !== 'undefined')
    Utility.webtrends(key, d);
  /** Google Analytics */
  if (typeof gtag !== 'undefined')
    Utility.gtagClick(key, d);
  /* eslint-enable no-undef */

  return d;
};

/**
 * Data bus for tracking views in Webtrends and Google Analytics
 * @param  {string}     app  The name of the Single Page Application to track
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.trackView = function(app, key, data) {
  /* eslint-disable no-undef */
  /** Webtrends */
  if (typeof Webtrends !== 'undefined')
    Utility.webtrends(key, data);
  /** Google Analytics */
  if (typeof gtag !== 'undefined')
    Utility.gtagView(app, key, data);
  /* eslint-enable no-undef */
};

/**
 * Push Events to Webtrends
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.webtrends = function(key, data) {
  /* eslint-disable no-undef, no-console, no-debugger */
  if (typeof Webtrends === 'undefined') return;
  let prefix = {};
  prefix['WT.ti'] = key;
  data.unshift(prefix);
  // format data for Webtrends
  data = {
    argsa: _.flatten(_.map(data, function(value) {
      return _.pairs(value);
    }))
  };
  Webtrends.multiTrack(data);
  if (Utility.debug())
    console.dir([`webtrends: multiTrack`, data]);
  /* eslint-disable no-undef, no-console, no-debugger */
};

/**
 * Push Click Events to Google Analytics
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.gtagClick = function(key, data) {
  let uri = _.find(data, (value) => (value.hasOwnProperty('DCS.dcsuri')));
  if (typeof uri === 'undefined') {
    /* eslint-disable no-console, no-debugger */
    if (Utility.debug()) {
      console.warn([
        'Click tracking for Webtrends and Google Analytics requires setting',
        'the DCS.dcsuri parameter: {"DCS.dcsuri": "category/action"}'
      ].join(' '));
    }
    /* eslint-enable no-console, no-debugger */
    return;
  }
  let event = {
    'event_category': key
  };
  /* eslint-disable no-undef */
  gtag('event', uri['DCS.dcsuri'], event);
  /* eslint-enable no-undef */
  /* eslint-disable no-console, no-debugger */
  if (Utility.debug())
    console.dir([`gtag: event, ${uri['DCS.dcsuri']}`, event]);
  /* eslint-enable no-console, no-debugger */
};

/**
 * Push Screen View Events to Google Analytics
 * @param  {string}     app  The name of the application
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.gtagView = function(app, key, data) {
  let view = {
    app_name: app,
    screen_name: key
  };
  /* eslint-disable no-undef */
  gtag('event', 'screen_view', view);
  /* eslint-enable no-undef */
  /* eslint-disable no-console, no-debugger */
  if (Utility.debug())
    console.dir([`gtag: event, screen_view`, view]);
  /* eslint-enable no-console, no-debugger */
};

/**
 * Warnings to show for the environment
 */
Utility.warnings = function() {
  /* eslint-disable no-console, no-debugger */
  if (typeof Webtrends === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_WT_NONCONFIG);

  /** Google Analytics */
  if (typeof gtag === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_GA_NONCONFIG);

  /** Rollbar */
  if (typeof Rollbar === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_ROLLBAR_NONCONFIG);
  /* eslint-enable no-console, no-debugger */
};

/**
 * Set a timer based on user interaction
 * @param  {number}   time     The timing of the timeout
 * @param  {Function} callback The timer callback function
 */
Utility.sessionTimeout = function(time, callback) {
  const key = Utility.CONFIG.IDLE_SESSION_TIMEOUT_KEY;
  if (Utility.getUrlParameter('timeout') && Utility.debug()) {
    time = parseInt(Utility.getUrlParameter('timeout'));
  } else if (Utility.debug()) {
    return;
  }

  window[key] = {
    int: 0
  };

  window[key].reset = function() {
    if (window[key].timeout)
      clearTimeout(window[key].timeout);
    window[key].timeout = setTimeout(() => {
        callback(window[key]);
      }, time);
    window[key].int++;
  };

  window.addEventListener('mousemove', window[key].reset);
  window.addEventListener('mousedown', window[key].reset);
  window.addEventListener('touchstart', window[key].reset);
  window.addEventListener('keypress', window[key].reset);
  window.addEventListener('scroll', window[key].reset);
  window.addEventListener('click', window[key].reset);
};

/**
 * Sends the configuration object to Rollbar, the most important config is
 * the code_version which maps to the source maps version.
 * @param  {object} window The initial window object.
 * @return {object}        The configured Rollbar method.
 */
Utility.configErrorTracking = function(window) {
  if (typeof Rollbar === 'undefined') return false;

  let scripts = document.getElementsByTagName('script');
  let source = scripts[scripts.length - 1].src;
  let path = source.split('/');
  let basename = path[path.length - 1];
  let hash = basename.split('.')[1];

  let config = {
    payload: {
      client: {
        javascript: {
          // This is will be true by default if you have enabled
          // this in settings.
          source_map_enabled: true,
          // This is transformed via envify in the scripts task.
          code_version: hash,
          // Optionally guess which frames the error was thrown from
          // when the browser does not provide line and column numbers.
          guess_uncaught_frames: true
        }
      }
    }
  };

  $(window).on('load', () => {
    let rollbarConfigure = Rollbar.configure(config);
    let msg = `Configured Rollbar with ${hash}`;

    if (Utility.debug()) {
      console.dir({
        init: msg,
        settings: rollbarConfigure
      }); // eslint-disable-line no-console
      Rollbar.debug(msg); // eslint-disable-line no-undef
    }
  });
};

/**
 * Add "noopener" to relationship if it doesn't exist
 * @param  {number} i  Index of element
 * @param  {object} el DOM element
 */
Utility.noopener = function(i, el) {
  let rel = $(el).attr('rel');
  rel = (typeof rel === 'undefined') ? '' : `${rel} `;
  if (rel.indexOf('noopener') === -1) {
    $(el).attr('rel', `${rel}noopener`);
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
  URL_PIN_GREEN_2X: '/wp-content/themes/access/assets/img/map-pin-green-2x.png',
  MSG_WT_NONCONFIG: 'Webtrends is not configured for this environment',
  MSG_GA_NONCONFIG: 'Google Analytics is not configured for this environment',
  MSG_ROLLBAR_NONCONFIG: 'Rollbar is not configured for this environment',
  IDLE_SESSION_TIMEOUT_KEY: 'IDLE_SESSION_TIMEOUT'
};

export default Utility;
