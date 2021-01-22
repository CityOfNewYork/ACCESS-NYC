/* eslint-env browser */

import OfficeMap from 'modules/office-map';

(function() {
  'use strict';

  /**
   * Main Locations Map
   */

  // Initialize maps if present.
  let googleMapsEmbed = document
    .querySelector(OfficeMap.Selectors.MAIN);

  // Initialize maps if present.
  if (googleMapsEmbed && googleMapsEmbed.dataset.key) {
    let callback = 'initializeMaps';
    let script = document.createElement('script');

    // Loading the Google maps library.
    window[callback] = () => {
      new OfficeMap(googleMapsEmbed).init();
    };

    script.type = 'text/javascript';
    script.src = [
        'https://maps.googleapis.com/maps/api/js',
        '?key=' + googleMapsEmbed.dataset.key,
        '&callback=window.' + callback,
        '&libraries=geometry,places&v=quarterly'
      ].join('');

    document.body.appendChild(script);
  }

  // For location detail pages, this overwrites the link to the "back to map"
  // button if the previous page was the map. We want the user to return to
  // the previous state of the map (via the same URL) rather than simply going
  // back to the default map.
  // $('.js-location-back').each((i, el) => {
  //   if (window.document.referrer.indexOf('/locations/?') >= 0) {
  //     $(el).attr('href', window.document.referrer);
  //   }
  // });
})();
