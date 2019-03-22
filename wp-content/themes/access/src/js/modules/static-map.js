/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Utility from 'modules/utility';
import _ from 'underscore';

/**
 * This component takes an html element and creates a static map image using
 * the Google static map API. Unless width and height data attributes are
 * present, the image is sized to fit the html element passed, and a window
 * resize handler is used to replace the image whenever
 * the element's dimensions change.
 * @class
 */
class StaticMap {
  /**
   * @param {HTMLElement} el - The html element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {string} Marker position. */
    this._marker = $(el).data('marker') ||
        `${Utility.CONFIG.DEFAULT_LAT},${Utility.CONFIG.DEFAULT_LNG}`;

    this._markerImg = $(el).data('govtOffice') ?
        Utility.CONFIG.URL_PIN_BLUE : Utility.CONFIG.URL_PIN_GREEN;

    /** @private {?number} Fixed element width. */
    this._fixedWidth = $(el).data('width') ?
        parseInt($(this._el).data('width'), 10) : null;

    /** @private {?number} Fixed element height. */
    this._fixedHeight = $(el).data('height') ?
        parseInt($(this._el).data('height'), 10) : null;

    /** @private {number} Element width. */
    this._width = this._fixedWidth || 0;

    /** @private {number} Element height. */
    this._height = this._fixedHeight || 0;

    /** @private {?string} URL to which image links. */
    this._link = $(el).data('link') || null;

    /** @type {string} The alt description for the map image */
    this._alt = $(el).data('alt') || null;

    /** @private {boolean} Whether this component has been initialized. */
    this._initialized = false;

    /** @type {String} The name of the click event to track on element */
    this._trackKey = 'Get Directions';

    /** @type {Collections} The data to track */
    this._trackData = [{'DCS.dcsuri': 'get-directions'}];
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

    const size = this.getContainerSize();

    this._height = size.height;
    this._width = size.width;

    this.renderImage();

    return this;
  }

  /**
   * Returns an object with width/height integer values.
   * @method
   * @return {object}
   */
  getContainerSize() {
    const dimensions = {};

    $(this._el).find('img').hide();

    dimensions.width = this._fixedWidth || parseInt($(this._el).width(), 10);
    dimensions.height = this._fixedHeight || parseInt($(this._el).height(), 10);

    $(this._el).find('img').show();

    return dimensions;
  }

  /**
   * Empties the container element and replaces its contents with a new
   * Google static map image.
   * @method
   * @return {this} StaticMap
   */
  renderImage() {
    const img = new Image();
    const parameters = {
      center: this._marker,
      zoom: 15,
      size: `${this._width}x${this._height}`,
      scale: 2,
      markers: `anchor:16,40|icon:https://access.nyc.gov` +
        `${this._markerImg}|shadow:false|${this._marker}`,
      key: Utility.CONFIG.GOOGLE_STATIC_API
    };

    img.onload = () => {
      $(this._el).empty;
      const $img = this._link ?
        $(`<a href="${this._link}"></a>`)
          .append(img) : $(img);
      $img.attr('target', '_blank');
      $img.attr('itemprop', 'hasMap');
      $img.addClass('block');

      $img.on('click', event => {
        Utility.track(this._trackKey, this._trackData);
      });

      $(this._el).html($img);
    };

    img.src = `https://maps.googleapis.com/maps/api/staticmap?` +
      `${$.param(parameters)}`;

    $(img).addClass('block animated fadeIn');
    $(img).attr('alt', this._alt);

    return this;
  }
}

export default StaticMap;
