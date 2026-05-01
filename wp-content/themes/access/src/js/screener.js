/* eslint-env browser */

// Core Modules
import Screener from 'modules/screener';
import 'modules/share-form';

// Patterns Framework
import Tooltips from 'utilities/tooltips/tooltips';

(function() {
  'use strict';

// When the focused element is a numeric input and user is trying to scroll, 
// unfocus the current input so scroll happens on the page level and not the field level
document.addEventListener('wheel', (e) => {
  const el = document.activeElement;
  if (el && el.matches('input[type="number"]')) {
    el.blur();
  }
}, { passive: true });

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
