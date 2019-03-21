/* eslint-env browser */
// Core-js polyfills.
// Core-js is made available as a dependency of @babel/preset-env
import 'core-js/fn/promise';
import 'core-js/fn/array/for-each';
import 'core-js/fn/array/find';
import 'core-js/fn/array/includes';
import 'core-js/fn/object/assign';
import 'core-js/fn/object/values';
import 'core-js/fn/object/is-extensible';

// Fetch
import 'whatwg-fetch';

import jQuery from 'jquery';
import Vue from 'vue/dist/vue.common';

import Utility from 'modules/utility';
import CardVue from 'components/card/card.vue';
import FilterMultiVue from 'components/filter/filter-multi.vue';
import WpArchiveVue from 'modules/wp-archive-vue';

((window, Vue, $) => {
  'use strict';

  /**
   * Archive
   */
  if (document.querySelector('[data-js="programs"]')) {
    Vue.component('c-card', CardVue);
    Vue.component('c-filter-multi', FilterMultiVue);

    new WpArchiveVue(Vue, {
      el: '[data-js="programs"]',
      delimiters: ['v{', '}'],
      data: {
        type: 'programs',
        query: {
          per_page: 5,
          page: parseInt(
            document.querySelector('[data-js="programs"]').dataset.jsPage
          )
        },
        headers: {
          pages: 8,
          total: 40,
          link: 'rel="next";'
        },
        endpoints: {
          terms: '/wp-json/api/v1/terms',
          programs: '/wp-json/wp/v2/programs'
        },
        maps: function() {
          return {
            terms: terms => ({
              'active': false,
              'name': terms.labels.archives,
              'slug': terms.name,
              'filters': terms.terms.map(filters => ({
                'id': filters.term_id,
                'name': filters.name,
                'slug': filters.slug,
                'parent': terms.name
              })),
              'STRINGS': {
                'ALL': window.LOCALIZED_STRINGS
                  .find(e => e.slug === 'ALL').label || 'ALL'
              }
            }),
            programs: p => ({
              'title': p.acf.plain_language_program_name,
              'link': p.link,
              'subtitle': p.acf.program_name + ((p.acf.program_acronym) ?
                ' (' + p.acf.program_acronym + ')' : ''),
              'summary': p.acf.brief_excerpt,
              'category': {
                'slug':
                  (p.terms && p.terms.find(t => t.taxonomy === 'programs')
                  .slug.replace(new RegExp(`\\-${this.lang.code}$`), ''))
                    || 'PROGRAMS',
                'name':
                  (p.terms && p.terms.find(t => t.taxonomy === 'programs').name)
                    || 'NAME'
              },
              'STRINGS': {
                'LEARN_MORE': window.LOCALIZED_STRINGS
                  .find(e => e.slug === 'LEARN_MORE').label || 'LEARN_MORE',
                'CTA': window.LOCALIZED_STRINGS
                  .find(e => e.slug === 'APPLY').label || 'APPLY'
              }
            })
          };
        }
      },
      created: function() {
        // Initialize the first page request
        this.queue();
        // Get the terms
        this.fetch('terms')
          .catch(this.error);
      }
    });
  }

  /**
   * Single
   */

  // TODO: This function and the conditional afterwards should be refactored
  // and pulled out to its own program detail controller module. The main
  // unique thing about program details is that they use a ?step=x query
  // parameter in the URL to determine the visible section. It is still all
  // the same page. A hash would seem more appropriate, but there were
  // some supposed issues with WPML where the hash was being stripped when
  // switching between langauges. Because it is a single page, we don't need
  // to actually reload the browser, which is why history.pushState is used.
  /**
   * Advances Program Page Steps
   * @param {string} step - the kebab case identifier for the section
   */
  function showSection(step) {
    $('[data-js="program-detail-step"]')
       .removeClass('active').filter(`#${step}`).addClass('active');

    $('[data-js="program-nav"] a').removeClass('active')
       .filter(`#nav-link-${step}`).addClass('active');
  }

  if ($('[data-js="program-detail-content"]').length) {
    const isMobileView = () => $('[data-js="site-desktop-nav"]')
      .is(':hidden');

    $('[data-js*="program-nav-step-link"]').on('click', e => {
      if (!history.pushState) {
        return true;
      }
      e.preventDefault();

      const step = Utility.getUrlParameter('step', $(e.target).attr('href'));
      let linkType = '';

      window.history.pushState(null, null, '?step=' + step);

      if ($(e.target).hasClass('[data-js*="jump-to-anchor"]')) {
        linkType = 'buttonLink';
      } else {
        linkType = 'navLink';
      }
      $(window).trigger('popstate', linkType);
    });

    $(window).on('popstate', (e, linkType) => {
      const possibleSections = [
        'how-it-works',
        'how-to-apply',
        'determine-your-eligibility',
        'what-you-need-to-include'
      ];

      let sectionId = Utility.getUrlParameter('step');

      if (!sectionId || !$.inArray(sectionId, possibleSections)) {
        sectionId = 'how-it-works';
      }

      // If the page is in a mobile view, and the user has clicked a button
      // (as opposed to one of the table of content links) we want to scroll
      // the browser to the content body as opposed to the top of the page.
      if (isMobileView() && linkType === 'buttonLink') {
        $(document).scrollTop(
          $('[data-js="program-detail-content"]').offset().top
        );
      } else {
        $(document).scrollTop(0);
      }
      showSection(sectionId);
    }).trigger('popstate');
  }
  // END TODO
})(window, Vue, jQuery);


