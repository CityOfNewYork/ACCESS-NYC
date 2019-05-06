/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/features/array/includes';

import Screener from 'modules/screener';

(function() {
  'use strict';

  // Initialize eligibility screener.
  let el = document.querySelector(Screener.Selectors.FORM);
  if (el) new Screener(el).init();
})();
