/* eslint-env browser */
'use strict';

/**
 * Collection of track functions
 */

const Track = {};

/**
 * Tracking function wrapper
 *
 * @param   {String}  key    The key or event of the data
 * @param   {Object}  data   The data to track
 * @param   {Object}  event  The original click event
 *
 * @return  {Object}         The final data object
 */
Track.event = function(key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends !== 'undefined')
    Track.webtrends(key, data);

  return data;
};

/**
 * Data bus for tracking views in Webtrends and Google Analytics
 *
 * @param  {string}  app   The name of the Single Page Application to track
 * @param  {string}  key   The key or event of the data
 * @param  {Object}  data  The data to track
 */
Track.view = function(app, key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends !== 'undefined') Track.webtrends(key, data);

  // eslint-disable-next-line no-undef
  if (typeof gtag !== 'undefined') Track.gtagView(app, key);
};

/**
 * Push Events to Webtrends
 *
 * @param  {String}  key   The key or event of the data
 * @param  {Object}  data  The data to track
 */
Track.webtrends = function(key, data) {
  // eslint-disable-next-line no-undef
  if (typeof Webtrends === 'undefined' || typeof data === 'undefined') return;

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

  if (dcsuri) {
    data.argsa[dcsuri + 1] = window.location.pathname + data.argsa[dcsuri + 1];
  }

  Webtrends.multiTrack(data); // eslint-disable-line no-undef

  // eslint-disable-next-line no-console, no-debugger
  if (process.env.NODE_ENV === 'development') {
    // eslint-disable-next-line no-console, no-debugger
    console.dir(data);
  }
};

/**
 * Push Click Events to Google Analytics
 *
 * @param  {String}      key   The key or event of the data
 * @param  {Object}  data  The data to track
 */
Track.gtag = function(key, data) {
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

  if (process.env.NODE_ENV === 'development') {
    // eslint-disable-next-line no-console, no-debugger
    console.dir(['event', action, params]);
  }
};

/**
 * Push Screen View Events to Google Analytics
 *
 * @param  {String}  app   The name of the application
 * @param  {String}  key   The key or event of the data
 */
Track.gtagView = function(app, key) {
  let view = {
    'app_name': app,
    'screen_name': key
  };

  gtag('event', 'screen_view', view); // eslint-disable-line no-undef

  if (process.env.NODE_ENV === 'development') {
    // eslint-disable-next-line no-console, no-debugger
    console.dir([`gtag: event, screen_view`, view]);
  }
};

export default Track;
