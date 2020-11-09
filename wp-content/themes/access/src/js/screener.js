/* eslint-env browser */

// Core Modules
import Screener from 'modules/screener';
import ShareFormDisclaimer from 'modules/share-form-disclaimer';

// Patterns Framework
import Tooltips from 'utilities/tooltips/tooltips';

(function() {
  'use strict';

  // Initialize eligibility screener.
  let el = document.querySelector(Screener.Selectors.FORM);
  if (el) new Screener(el).init();

  /**
   * Initialize tooltips
   */
  (elements => {
    elements.forEach(element => new Tooltips(element));
  })(document.querySelectorAll(Tooltips.selector));

})();
