/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/features/url-search-params';

// Patterns Framework
import Toggle from 'utilities/toggle/toggle';

/**
* Programs Detail
*/
(() => {
  'use strict';

  const query = new URLSearchParams(window.location.search);

  // if there is a query, and an existing section,
  //   show appropriate section

  new Toggle({
    selector: '[data-js*="pager"]',
    before: () => {
      // hide all sections
    },
    after: () => {
      // scroll to the top of section parent
      // push state to history api
    }
  });
})();

