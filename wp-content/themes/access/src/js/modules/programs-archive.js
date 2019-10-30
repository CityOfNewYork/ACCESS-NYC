'use strict';

/**
 * This script handles configuration for the Archive.vue component. It
 * is scoped to set the post type, initial query, intial headers, enpoints,
 * for the post type, and template mappings for filter and post content.
 * Template mappings are passed to the Vue Components of the application.
 *
 * To modify the request handling of the WP REST API, the archive.vue must
 * be modified if it cannot be configured by this file.
 *
 * To modify the configuration of the markup, the views/programs/archive.vue
 * must be modified. That file is a Vue Component that imports this script.
 */

import Archive from './archive.vue';

export default {
  extends: Archive,
  props: {
    perPage: {
      type: Number,
      default: 1
    },
    page: {
      type: Number,
      default: 5
    },
    pages: {
      type: Number,
      default: 0
    },
    total: {
      type: Number,
      default: 0
    },
    paginationNextLink: {type: String},
    strings: {type: Object}
  },
  data: function() {
    return {
      type: 'programs',
      query: {
        per_page: this.perPage,
        page: this.page
      },
      headers: {
        pages: this.pages,
        total: this.total,
        link: 'rel="next";'
      },
      endpoints: {
        terms: '/wp-json/api/v1/terms',
        programs: '/wp-json/wp/v2/programs'
      },
      history: {
        omit: ['page', 'per_page'],
        params: {
          'programs': 'categories',
          'populations-served': 'served'
        }
      },
      maps: function() {
        return {
          terms: terms => ({
            'active': false,
            'name': terms.labels.archives,
            'slug': terms.name,
            'checkbox': false,
            'toggle': true,
            'filters': terms.terms.map(filters => ({
              'id': filters.term_id,
              'name': filters.name,
              'slug': filters.slug,
              'parent': terms.name,
              'active': (
                  this.query.hasOwnProperty(terms.name) &&
                  this.query[terms.name].includes(filters.term_id)
                ),
              'checked': (
                  this.query.hasOwnProperty(terms.name) &&
                  this.query[terms.name].includes(filters.term_id)
                )
            }))
          }),
          programs: p => ({
            'title': p.acf.plain_language_program_name,
            'link': p.link,
            'subtitle': p.acf.program_name + ((p.acf.program_acronym) ?
              ' (' + p.acf.program_acronym + ')' : ''),
            'summary': p.acf.brief_excerpt,
            'category': {
              'slug':
                (
                  p.terms && p.terms.find(t => t.taxonomy === 'programs')
                    .slug.replace(new RegExp(`\\-${this.lang.code}$`), '')
                ) || 'PROGRAMS',
              'name':
                (
                  p.terms &&
                  p.terms.find(t => t.taxonomy === 'programs').name
                ) || 'NAME'
            }
          })
        };
      }
    };
  },
  computed: {
    /**
     * Inserting this computed property into the template hides the server
     * rendered content and shows the Vue app content.
     */
    initialized: function() {
      if (this.init) {
        let main = document.querySelector('[data-js="loaded"]');

        document.querySelector('[data-js="preload"]').remove();

        main.classList.remove('hidden');
        main.removeAttribute('aria-hidden');
      }
    },
    categories: function() {
      return [this.terms.map(
        t => t.filters
          .filter(f => f.checked)
          .map(f => f.name)
      )].flat(2);
    }
  },
  created: function() {
    this.getState() // Get window.location.search (filter history)
      .queue() // Initialize the first page request
      .fetch('terms') // Get the terms from the 'terms' endpoint
      .catch(this.error);
  }
};
