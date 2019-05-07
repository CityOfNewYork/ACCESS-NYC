/* eslint-env browser */
'use strict';

import Utility from 'modules/utility';
import _ from 'underscore';

/* eslint-disable no-undef */

/**
 * OfficeLocation objects are used by the OfficeMap and help normalize the
 * JSON data that is passed from the WP API.
 * @class
 */
class OfficeLocation {
  /**
   * @param {object} obj - a JSON object from the WP api.
   * @constructor
   */
  constructor(obj) {
    // If this is the first time an Office Location is instantiated, define
    // the marker icon element. Blue markers are used by Government Offices.
    // Green is used for all others.
    if (!OfficeLocation.Marker) {
      OfficeLocation.Marker = {
        BLUE: {
          url: Utility.CONFIG.URL_PIN_BLUE_2X,
          size: new google.maps.Size(65, 80),
          origin: new google.maps.Point(0, 0),
          anchor: new google.maps.Point(16, 40),
          scaledSize: new google.maps.Size(33, 40)
        },
        GREEN: {
          url: Utility.CONFIG.URL_PIN_GREEN_2X,
          size: new google.maps.Size(65, 80),
          origin: new google.maps.Point(0, 0),
          anchor: new google.maps.Point(16, 40),
          scaledSize: new google.maps.Size(33, 40)
        }
      };
    }

    /** {Number} The WordPress ID */
    this.id = obj.id || 0;

    /** {string} The WordPress slug. */
    this.link = obj.link || '';

    /** {string} The name of the office. */
    this.name = obj.title || '';

    /** {string} The office type. */
    this.type = obj.type || '';

    /** {Boolean} If this is a government office. */
    // We won't need to localize this until we translate locations - DH
    // this.isGovtOffice = Utility.localize('GOVERNMENT_OFFICE') === this.type;
    this.isGovtOffice = 'Government Office' === this.type;

    /** {object} The office location. */
    this.address = {
      street: obj.address.street || '',
      location:
          new google.maps.LatLng(obj.address.lat, obj.address.lng)
    };

    /** {array<Number> A collection of program data. */
    this.programs = obj.programs || [];

    /** {google.maps.Marker} The google marker associated with this office. */
    this.marker = new google.maps.Marker({
      position: this.address.location,
      icon: this.isGovtOffice ? OfficeLocation.Marker.BLUE :
          OfficeLocation.Marker.GREEN,
      title: this.name,
      id: this.id
    });
  }

  /**
   * Returns true if office has any of the passed programs, identified by ID.
   * Also returns true if an empty array is passed.
   * @param {Array<Number>|Number} programIds
   * @return {boolean} Whether a program has been matched.
   */
  hasProgram(programIds) {
    let result = false;
    let programs = [];
    if (_.isNumber(programIds)) {
      programs.push(programIds);
    } else if (_.isArray(programIds)) {
      programs = programIds;
    }
    if (programs.length === 0) {
      result = true;
    } else {
      for (let i = 0; i < this.programs.length; i++) {
        if (_.contains(programs, this.programs[i])) {
          result = true;
          break;
        }
      }
    }
    return result;
  }
}

/* eslint-enable no-undef */

OfficeLocation.Marker = null;

export default OfficeLocation;
