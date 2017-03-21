/* eslint-env browser */
'use strict';

import $ from 'jquery';
import Cookies from 'js-cookie';

/**
 * This controls the text sizer module at the top of page. A text-size-X class
 * is added to the html root element. X is an integer to indicate the scale of
 * text adjustment with 0 being neutral.
 * @class
 */
class TextSizer {
  /**
   * @param {HTMLElement} el - The html element for the component.
   * @constructor
   */
  constructor(el) {
    /** @private {HTMLElement} The component element. */
    this._el = el;

    /** @private {Number} The relative scale of text adjustment. */
    this._textSize = 0;

    /** @private {boolean} Whether the textSizer is displayed. */
    this._active = false;

    /** @private {boolean} Whether the map has been initialized. */
    this._initialized = false;
  }

  /**
   * Attaches event listeners to controller. Checks for textSize cookie and
   * sets the text size class appropriately.
   * @return {this} TextSizer
   */
  init() {
    if (this._initialized) {
      return this;
    }

    $(this._el).on('click', `.${TextSizer.CssClass.TOGGLE}`, (e) => {
      e.preventDefault();
      this.toggle();
    }).on('click', `.${TextSizer.CssClass.SMALLER}`, (e) => {
      e.preventDefault();
      const newSize = this._textSize - 1;
      if (newSize >= TextSizer.Size.MIN) {
        this._adjustSize(newSize);
      }
    }).on('click', `.${TextSizer.CssClass.LARGER}`, (e) => {
      e.preventDefault();
      const newSize = this._textSize + 1;
      if (newSize <= TextSizer.Size.MAX) {
        this._adjustSize(newSize);
      }
    });

    // If there is a text size cookie, set the textSize variable to the setting.
    // If not, textSize initial setting remains at zero and we toggle on the
    // text sizer/language controls and add a cookie.
    if (Cookies.get('textSize')) {
      const size = parseInt(Cookies.get('textSize'), 10);
      this._textSize = size;
      this._adjustSize(size);
    } else {
      $('html').addClass(`text-size-${this._textSize}`);
      this.show();
      this._setCookie();
    }

    this._initialized = true;

    return this;
  }

  /**
   * Show or hide the component based on this._active value.
   * @return {this} TextSizer
   */
  toggle() {
    if (this._active) {
      this.hide();
    } else {
      this.show();
    }
    return this;
  }

  /**
   * Shows the text sizer controls.
   * @return {this} TextSizer
   */
  show() {
    this._active = true;
    $(this._el).find(`.${TextSizer.CssClass.OPTIONS}`)
        .removeClass(TextSizer.CssClass.HIDDEN).end()
        .find(`.${TextSizer.CssClass.TOGGLE}`)
        .addClass(TextSizer.CssClass.HIDDEN);
    return this;
  }

  /**
   * Hides the text sizer controls.
   * @return {this} TextSizer
   */
  hide() {
    this._active = false;
    $(this._el).find(`.${TextSizer.CssClass.OPTIONS}`)
        .addClass(TextSizer.CssClass.HIDDEN).end()
        .find(`.${TextSizer.CssClass.TOGGLE}`)
        .removeClass(TextSizer.CssClass.HIDDEN);
    return this;
  }

  /**
   * Sets the `textSize` cookie to store the value of this._textSize. Expires
   * in 1 hour (1/24 of a day).
   * @return {this} TextSizer
   */
  _setCookie() {
    Cookies.set('textSize', this._textSize, {expires: (1/24)});
    return this;
  }

  /**
   * Sets the text-size-X class on the html root element. Updates the cookie
   * if necessary.
   * @param {Number} size - new size to set.
   * @return {this} TextSizer
   */
  _adjustSize(size) {
    const originalSize = this._textSize;

    if (size !== originalSize) {
      this._textSize = size;
      this._setCookie();
      $('html').removeClass(`text-size-${originalSize}`);
    }

    $('html').addClass(`text-size-${size}`);

    this._checkForMinMax();

    return this;
  }

  /**
   * Checks the current text size against the min and max. If the limits are
   * reached, disable the controls for going smaller/larger as appropriate.
   * @return {this} TextSizer
   */
  _checkForMinMax() {
    if (this._textSize <= TextSizer.Size.MIN) {
      this._textSize = TextSizer.Size.MIN;
      $(this._el).find(`.${TextSizer.CssClass.SMALLER}`)
          .attr('disabled', 'disabled');
    } else {
      $(this._el).find(`.${TextSizer.CssClass.SMALLER}`).removeAttr('disabled');
    }
    if (this._textSize >= TextSizer.Size.MAX) {
      this._textSize = TextSizer.Size.MAX;
      $(this._el).find(`.${TextSizer.CssClass.LARGER}`)
          .attr('disabled', 'disabled');
    } else {
      $(this._el).find(`.${TextSizer.CssClass.LARGER}`).removeAttr('disabled');
    }
    return this;
  }
}

TextSizer.Size = {
  MAX: 3,
  MIN: -3
};

TextSizer.CssClass = {
  CONTROLLER: 'js-text-controller',
  HIDDEN: 'hidden',
  LARGER: 'js-text-larger',
  OPTIONS: 'js-text-controller-options',
  SMALLER: 'js-text-smaller',
  TOGGLE: 'js-text-controller-toggle'
};

export default TextSizer;
