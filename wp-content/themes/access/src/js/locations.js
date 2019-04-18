/* eslint-env browser */
import jQuery from 'jquery';
import Utility from 'modules/utility';
import OfficeMap from 'modules/office-map';
import StaticMap from 'modules/static-map';

(function(window, $) {
  'use strict';

  const google = window.google;

  // Initialize maps if present.
  const $maps = $('.js-map');

  /**
   * Callback function for loading the Google maps library.
   */
  function initializeMaps() {
    $maps.each((i, el) => {
      const map = new OfficeMap(el);
      map.init();
    });
  }

  if ($maps.length > 0) {
    const options = {
      key: Utility.CONFIG.GOOGLE_API,
      libraries: 'geometry,places'
    };

    google.load('maps', '3', {
      /* eslint-disable camelcase */
      other_params: $.param(options),
      /* eslint-enable camelcase */
      callback: initializeMaps
    });
  }

  // Initialize simple maps.
  $('.js-static-map').each((i, el) => {
    const staticMap = new StaticMap(el);
    staticMap.init();
  });

  // For location detail pages, this overwrites the link to the "back to map"
  // button if the previous page was the map. We want the user to return to
  // the previous state of the map (via the same URL) rather than simply going
  // back to the default map.
  $('.js-location-back').each((i, el) => {
    if (window.document.referrer.indexOf('/locations/?') >= 0) {
      $(el).attr('href', window.document.referrer);
    }
  });
})(window, jQuery);
