/* eslint-env browser */
'use strict';

import $ from 'jquery';
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
    $($(el).data('reverse')).on('click', event => {
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
Utility.toDollarAmount = val =>
    (Math.abs(Math.round(parseFloat(val) * 100) / 100)).toFixed(2);

/**
 * For translating strings, there is a global LOCALIZED_STRINGS array that
 * is defined on the HTML template level so that those strings are exposed to
 * WPML translation. The LOCALIZED_STRINGS array is composed of objects with a
 * `slug` key whose value is some constant, and a `label` value which is the
 * translated equivalent. This function takes a slug name and returns the
 * label.
 * @param  {string} slug
 * @return {string} localized value
 */
Utility.localize = function(slug) {
  let text = slug || '';
  let strings = window.LOCALIZED_STRINGS || [];
  let match = strings.filter(
    s => (s.hasOwnProperty('slug') && s['slug'] === slug) ? s : false);

  return (match[0] && match[0].hasOwnProperty('label'))
    ? match[0].label : text;
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
 * @param  {string}     key   The key or event of the data
 * @param  {collection} data  The data to track
 * @param  {object}     event The original click event
 * @return {object}           The final data object
 */
Utility.track = function(key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends !== 'undefined') Utility.webtrends(key, data);

  return data;
};

/**
 * Data bus for tracking views in Webtrends and Google Analytics
 * @param  {string}     app  The name of the Single Page Application to track
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.trackView = function(app, key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends !== 'undefined') Utility.webtrends(key, data);

  // eslint-disable-next-line no-undef
  if (typeof gtag !== 'undefined') Utility.gtagView(app, key, data);
};

/**
 * Push Events to Webtrends
 * @param  {string}     key   The key or event of the data
 * @param  {collection} data  The data to track
 */
Utility.webtrends = function(key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends !== 'undefined' || typeof data !== 'undefined')
    Utility.webtrends(key, data);


  let prefix = {};
  prefix['WT.ti'] = key;
  data.unshift(prefix);

  // Format data for Webtrends
  data = {
    argsa: data.flatMap(value => {
      return Object.entries(value);
    }).flat()
  };

  // If 'action' is used as the key (for gtag.js), switch it to Webtrends
  let action = data.argsa.indexOf('action');
  if (action) data.argsa[action] = 'DCS.dcsuri';

  // Webtrends doesn't send the page view for MultiTrack, add path to url
  let dcsuri = data.argsa.indexOf('DCS.dcsuri');
  if (dcsuri)
    data.argsa[dcsuri + 1] = window.location.pathname + data.argsa[dcsuri + 1];

  Webtrends.multiTrack(data); // eslint-disable-line no-undef

  // eslint-disable-next-line no-console, no-debugger
  if (Utility.debug()) console.dir(data);
};

/**
 * Push Click Events to Google Analytics
 * @param  {string}     key  The key or event of the data
 * @param  {collection} data The data to track
 */
Utility.gtagClick = function(key, data) {
  let params = {
    'event_category': key
  };

  let google = data.find(value => value.hasOwnProperty('action'));
  let webtrends = data.find(value => value.hasOwnProperty('DCS.dcsuri'));
  let action = false;

  if (typeof webtrends != 'undefined')
    action = webtrends['DCS.dcsuri'];

  if (typeof google != 'undefined')
    action = google['action'];

  if (!action) return;

  gtag('event', action, params); // eslint-disable-line no-undef

  // eslint-disable-next-line no-console, no-debugger
  if (Utility.debug()) console.dir(['event', action, params]);
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

  gtag('event', 'screen_view', view); // eslint-disable-line no-undef

  // eslint-disable-next-line no-console, no-debugger
  if (Utility.debug()) console.dir([`gtag: event, screen_view`, view]);
};

/**
 * Warnings to show for the environment
 */
Utility.warnings = function() {
  /* eslint-disable no-undef, no-console, no-debugger */
  if (typeof Webtrends === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_WT_NONCONFIG);

  /** Google Analytics */
  if (typeof gtag === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_GA_NONCONFIG);

  /** Rollbar */
  if (typeof Rollbar === 'undefined' && Utility.debug())
    console.warn(Utility.CONFIG.MSG_ROLLBAR_NONCONFIG);
  /* eslint-enable no-undef, no-console, no-debugger */
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
  // eslint-disable-next-line no-undef
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
    // eslint-disable-next-line no-undef
    let rollbarConfigure = Rollbar.configure(config);
    let msg = `Configured Rollbar with ${hash}`;

    if (Utility.debug()) {
      // eslint-disable-next-line no-console
      console.dir({
        init: msg,
        settings: rollbarConfigure
      });

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

/**
 * Valid zip codes in New York City. Source:
 * https://data.cityofnewyork.us/City-Government/Zip-code-breakdowns/6bic-qvek
 * @type {array<String>}
 */
Utility.NYC_ZIPS = [
  '10451', '10452', '10453', '10454', '10455', '10456',
  '10457', '10458', '10459', '10460', '10461', '10462', '10463',
  '10464', '10465', '10466', '10467', '10468', '10469', '10470',
  '10471', '10472', '10473', '10474', '10475', '10499', '11201',
  '11202', '11203', '11204', '11205', '11206', '11207', '11208',
  '11209', '11210', '11211', '11212', '11213', '11214', '11215',
  '11216', '11217', '11218', '11219', '11220', '11221', '11222',
  '11223', '11224', '11225', '11226', '11228', '11229', '11230',
  '11231', '11232', '11233', '11234', '11235', '11236', '11237',
  '11238', '11239', '11240', '11241', '11242', '11243', '11244',
  '11245', '11247', '11248', '11249', '11251', '11252', '11254',
  '11255', '11256', '10001', '10002', '10003', '10004', '10005',
  '10006', '10007', '10008', '10009', '10010', '10011', '10012',
  '10013', '10014', '10015', '10016', '10017', '10018', '10019',
  '10020', '10021', '10022', '10023', '10024', '10025', '10026',
  '10027', '10028', '10029', '10030', '10031', '10032', '10033',
  '10034', '10035', '10036', '10037', '10038', '10039', '10040',
  '10041', '10043', '10044', '10045', '10046', '10047', '10048',
  '10055', '10060', '10065', '10069', '10072', '10075', '10079',
  '10080', '10081', '10082', '10087', '10090', '10094', '10095',
  '10096', '10098', '10099', '10101', '10102', '10103', '10104',
  '10105', '10106', '10107', '10108', '10109', '10110', '10111',
  '10112', '10113', '10114', '10115', '10116', '10117', '10118',
  '10119', '10120', '10121', '10122', '10123', '10124', '10125',
  '10126', '10128', '10129', '10130', '10131', '10132', '10133',
  '10138', '10149', '10150', '10151', '10152', '10153', '10154',
  '10155', '10156', '10157', '10158', '10159', '10160', '10161',
  '10162', '10163', '10164', '10165', '10166', '10167', '10168',
  '10169', '10170', '10171', '10172', '10173', '10174', '10175',
  '10176', '10177', '10178', '10179', '10184', '10185', '10196',
  '10197', '10199', '10203', '10211', '10212', '10213', '10242',
  '10249', '10256', '10257', '10258', '10259', '10260', '10261',
  '10265', '10268', '10269', '10270', '10271', '10272', '10273',
  '10274', '10275', '10276', '10277', '10278', '10279', '10280',
  '10281', '10282', '10285', '10286', '11001', '11004', '11005',
  '11040', '11096', '11101', '11102', '11103', '11104', '11105',
  '11106', '11109', '11120', '11351', '11352', '11354', '11355',
  '11356', '11357', '11358', '11359', '11360', '11361', '11362',
  '11363', '11364', '11365', '11366', '11367', '11368', '11369',
  '11370', '11371', '11372', '11373', '11374', '11375', '11377',
  '11378', '11379', '11380', '11381', '11385', '11386', '11390',
  '11405', '11411', '11412', '11413', '11414', '11415', '11416',
  '11417', '11418', '11419', '11420', '11421', '11422', '11423',
  '11424', '11425', '11426', '11427', '11428', '11429', '11430',
  '11431', '11432', '11433', '11434', '11435', '11436', '11439',
  '11451', '11499', '11690', '11691', '11692', '11693', '11694',
  '11695', '11697', '10292', '10301', '10302', '10303', '10304',
  '10305', '10306', '10307', '10308', '10309', '10310', '10311',
  '10312', '10313', '10314', '10097', '10514', '10543', '10553',
  '10573', '10701', '10705', '10911', '10965', '10977', '11021',
  '11050', '11111', '11112', '11471', '11510', '11548', '11566',
  '11577', '11580', '11598', '11629', '11731', '11798', '11968',
  '12423', '12428', '12435', '12458', '12466', '12473', '12528',
  '12701', '12733', '12734', '12737', '12750', '12751', '12754',
  '12758', '12759', '12763', '12764', '12768', '12779', '12783',
  '12786', '12788', '12789', '13731', '16091', '20459'];

export default Utility;
