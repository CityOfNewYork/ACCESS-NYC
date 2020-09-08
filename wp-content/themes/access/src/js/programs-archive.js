/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/features/promise';
import 'core-js/features/array/for-each';
import 'core-js/features/array/find';
import 'core-js/features/array/includes';
import 'core-js/features/array/flat';
import 'core-js/features/object/keys';
import 'core-js/features/object/assign';
import 'core-js/features/object/values';
import 'core-js/features/object/is-extensible';
import 'core-js/features/url-search-params';

// Fetch
import 'whatwg-fetch';

// Librarires
import Vue from 'vue/dist/vue.runtime.min';

// Core Modules
import CardVue from 'components/card/card.vue';
import FilterMultiVue from 'components/filter/filter-multi.vue';

import ProgramsArchive from '../../views/programs/archive.vue';

// Patterns Framework
import localize from 'utilities/localize/localize';

import 'main';

((window, Vue) => {
  'use strict';

  /**
   * Programs Archive
   */

  (element => {
    if (element) {
      /**
       * Redirect old filtering method to WP Archive Vue filtering
       */

      let query = [];
      let params = {
        'categories': 'categories[]',
        'served': 'served[]'
      };

      Object.keys(params).forEach(key => {
        let datum = element.dataset;
        if (datum.hasOwnProperty(key) && datum[key] != '') {
          query.push(params[key] + '=' + datum[key]);
        }
      });

      if (query.length) window.history.replaceState(null, null, [
          window.location.pathname, '?', query.join('')
        ].join(''));

      /**
       * Get localized strings from template
       */

      let strings = Object.fromEntries([
        'ARCHIVE_TOGGLE_ALL', 'ARCHIVE_LEARN_MORE', 'ARCHIVE_APPLY',
        'ARCHIVE_ALL', 'ARCHIVE_PROGRAMS', 'ARCHIVE_FILTER_PROGRAMS',
        'ARCHIVE_NO_RESULTS', 'ARCHIVE_SEE_PROGRAMS', 'ARCHIVE_LOADING',
        'ARCHIVE_NO_RESULTS_INSTRUCTIONS', 'ARCHIVE_MORE_RESULTS'
      ].map(i => [
        i.replace('ARCHIVE_', ''),
        localize(i)
      ]));

      /**
       * Add Vue components to the vue instance
       */

      Vue.component('c-card', CardVue);
      Vue.component('c-filter-multi', FilterMultiVue);

      /**
       * Pass our configuration options to the Archive method (including Vue)
       */

      new Vue({
        render: createElement => createElement(ProgramsArchive, {
          props: {
            perPage: parseInt(element.dataset.perPage),
            page: parseInt(element.dataset.page),
            pages: parseInt(element.dataset.pages),
            total: parseInt(element.dataset.count),
            paginationNextLink: element.dataset.paginationNextLink,
            strings: strings
          }
        })
      }).$mount(`[data-js="${element.dataset.js}"]`);
    }

  })(document.querySelector('[data-js="programs"]'));

})(window, Vue);


