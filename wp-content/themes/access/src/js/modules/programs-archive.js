'use strict';

/**
 * This script handles configuration for the Archive.vue component. It
 * is scoped to set the post type, initial query, initial headers, endpoints,
 * for the post type, and template mappings for filter and post content.
 * Template mappings are passed to the Vue Components of the application.
 *
 * To modify the request handling of the WP REST API, the archive.vue must
 * be modified if it cannot be configured by this file.
 *
 * To modify the configuration of the markup, the views/programs/archive.vue
 * must be modified. That file is a Vue Component that imports this script.
 */

import Archive from '@nycopportunity/wp-archive-vue/src/archive.vue';

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
      /**
       * This is our custom post type to query
       *
       * @type {String}
       */
      type: 'programs',

      /**
       * Setting this sets the initial app query
       *
       * @type {Object}
       */
      query: {
        per_page: this.perPage,
        page: this.page
      },

      /**
       * Setting this sets the initial headers of the app's query
       *
       * @type {Object}
       */
      headers: {
        pages: this.pages,
        total: this.total,
        link: 'rel="next";'
      },

      /**
       * This is the endpoint list for terms and post requests
       *
       * @type  {Object}
       *
       * @param {String} terms     A required endpoint for the list of filters
       * @param {String} programs  This is based on the 'type' setting above
       */
      endpoints: {
        terms: '/wp-json/api/v1/terms',
        programs: '/wp-json/wp/v2/programs'
      },

      /**
       * Each endpoint above will access a map to take the data from the request
       * and transform it for the app's display purposes
       *
       * @type   {Function}
       *
       * @return {Object}    Object with a mapping function for each endpoint
       */
      maps: function() {
        return {
          terms: terms => ({
            active: false,
            name: terms.labels.archives,
            slug: terms.name,
            checkbox: false,
            toggle: true,
            filters: terms.terms.map(filters => ({
              id: filters.term_id,
              name: filters.name,
              slug: filters.slug,
              parent: terms.name,
              active: (
                  this.query.hasOwnProperty(terms.name) &&
                  this.query[terms.name].includes(filters.term_id)
                ),
              checked: (
                  this.query.hasOwnProperty(terms.name) &&
                  this.query[terms.name].includes(filters.term_id)
                )
            }))
          }),
          programs: p => ({
            title: p.acf.plain_language_program_name,
            link: p.link,
            subtitle: p.acf.program_name + ((p.acf.program_acronym) ?
              ' (' + p.acf.program_acronym + ')' : ''),
            summary: p.acf.brief_excerpt,
            category: {
              slug: p.timber.category.slug || 'PROGRAMS',
              name: p.timber.category.name || 'NAME'
            },
            icon: p.timber.icon,
            status: p.timber.status
          })
        };
      }
    };
  },
  computed: {
    /**
     * Inserting this computed property into the template hides the server
     * rendered content and shows the Vue app content
     *
     * @type {Function}
     */
    initialized: function() {
      if (this.init) {
        let main = document.querySelector('[data-js="loaded"]');

        document.querySelector('[data-js="preload"]').remove();

        main.classList.remove('hidden');
        main.removeAttribute('aria-hidden');
      }
    },

    /**
     * @type {Function}
     */
    categories: function() {
      return [this.terms.map(
        t => t.filters
          .filter(f => f.checked)
          .map(f => f.name)
      )].flat(2);
    }
  },

  /**
   * The created function starts the application
   *
   * @type {Function}
   */
  created: function() {
    let taxonomies = {
      'programs': 'categories',
      'populations-served': 'served'
    };

    // Add map of WP Query terms < to > Window history state
    this.$set(this.history, 'map', taxonomies);

    // Add custom taxonomy queries to the list of safe params
    Object.keys(taxonomies).map(p => {
      this.params.push(p);
    });

    this.getState()       // Get window.location.search (filter history)
      .queue()            // Initialize the first page request
      .fetch('terms')     // Get the terms from the 'terms' endpoint
      .catch(this.error);
  }
};
