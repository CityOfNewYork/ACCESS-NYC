/* eslint-env browser */
import jQuery from 'jquery';
import Screener from 'modules/screener';

(function(window, $) {
  'use strict';

  // Initialize eligibility screener.
  $(`.${Screener.CssClass.FORM}`).each((i, el) => {
    const screener = new Screener(el);
    screener.init();
  });
})(window, jQuery);
