/* eslint-env browser */
'use strict';

import Vue from 'vue/dist/vue.common';
import _ from 'underscore';
import CardVue from 'components/card/card.vue';
import FilterMultiVue from 'components/filter/filter-multi.vue';

// const CardVue = {};
// const FilterMultiVue = {};

/**
 * [WpArchiveVue description]
 */
class WpArchiveVue {
  /**
   * @return  {object}  A new Vue App Instance.
   */
  constructor() {
    Object.isEqual = _.isEqual;

    Vue.component('c-card', CardVue);

    Vue.component('c-filter-multi', FilterMultiVue);

    return new Vue({
      el: '[data-js="programs"]',
      delimiters: ['v{', '}'],
      data: {
        terms: [],
        posts: [],
        query: {
          page: parseInt(
            document.querySelector('[data-js="programs"]').dataset.jsPage
          ),
          per_page: 5
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
        init: false,
        abort: new AbortController, // eslint-disable-line no-undef
        maps: function() {
          return {
            filters: filter => ({
              'active': false,
              'name': filter.labels.archives,
              'slug': filter.name,
              'filters': filter.terms.map(f => ({
                'id': f.term_id,
                'name': f.name,
                'slug': f.slug,
                'parent': filter.name
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
      computed: {
        loading: function() {
          if (!this.posts.length) return false;
          let page = this.posts[this.query.page];
          return this.init && !page.posts.length && page.show;
        },
        none: function() {
          return !this.headers.pages && !this.headers.total;
        },
        next: function() {
          let number = this.query.page;
          let total = this.headers.pages;
          if (!this.posts.length) return false;
          let page = this.posts[number];
          return (number < total) && (page.posts.length && page.show);
        },
        previous: function() {
          return (this.query.page > 1);
        },
        filtering: function() {
          if (!this.init) return false;
          return (this.terms.find(t => t.active)) ? true : false;
        },
        lang: () => {
          let lang = document.querySelector('html').lang;
          return (lang !== 'en') ? {
            code: lang,
            path: `/${lang}`
          } : {
            code: 'en',
            path: ''
          };
        }
      },
      methods: {
        fetch: WpArchiveVue.fetch,
        click: WpArchiveVue.click,
        filter: WpArchiveVue.filter,
        filterAll: WpArchiveVue.filterAll,
        updateQuery: WpArchiveVue.updateQuery,
        reset: WpArchiveVue.reset,
        paginate: WpArchiveVue.paginate,
        wp: WpArchiveVue.wp,
        queue: WpArchiveVue.queue,
        wpQuery: WpArchiveVue.wpQuery,
        response: WpArchiveVue.response,
        process: WpArchiveVue.process,
        error: WpArchiveVue.error
      },
      created: function() {
        this.queue();
        this.fetch('terms')
          .catch(this.error);
      }
    });
  }
}

WpArchiveVue.fetch = function(data = false) {
  if (!data) return data;

  return (this[data].length) ? this[data] :
    fetch(this.lang.path + this.endpoints[data])
      .then(response => response.json())
      .then(d => {
        Vue.set(this, data, d.map(this.maps().filters));
      });
};

WpArchiveVue.click = function(event) {
  let taxonomy = event.data.parent;
  let term = event.data.id || false;

  if (term) {
    this.filter(taxonomy, term);
  } else {
    this.filterAll(taxonomy);
  }
};

WpArchiveVue.filter = function(taxonomy, term) {
  let terms = (this.query.hasOwnProperty(taxonomy)) ?
    this.query[taxonomy] : [term]; // get other query or initialize.

  // Toggle, if the taxonomy exists, filter it out, otherwise add it.
  if (this.query.hasOwnProperty(taxonomy))
    terms = (terms.includes(term)) ?
      terms.filter(el => el !== term) : terms.concat([term]);

  this.updateQuery(taxonomy, terms);
};

WpArchiveVue.filterAll = function(taxonomy) {
  let tax = this.terms.find(t => t.slug === taxonomy);
  let checked = !(tax.checked);

  Vue.set(tax, 'checked', checked);

  let terms = tax.filters.map(term => {
      Vue.set(term, 'checked', checked);
      return term.id;
    });

  this.updateQuery(taxonomy, (checked) ? terms : []);
};

WpArchiveVue.updateQuery = function(taxonomy, terms) {
  return new Promise((resolve, reject) => { // eslint-disable-line no-undef
    Vue.set(this.query, taxonomy, terms);
    Vue.set(this.query, 'page', 1);

    this.posts.map((value, index) => {
      if (value) Vue.set(this.posts[index], 'show', false);
      return value;
    });
    resolve();
  })
  .then(this.wp)
  .catch(message => {
    // console.dir(message);
  });
};

WpArchiveVue.reset = function(event) {
  return new Promise(resolve => { // eslint-disable-line no-undef
    let taxonomy = event.data.slug;
    if (this.query.hasOwnProperty(taxonomy)) {
      Vue.set(this.query, taxonomy, []);
      resolve();
    }
  });
};

WpArchiveVue.paginate = function(event) {
  event.preventDefault();

  // The change is the next page as well as an indication of what
  // direction we are moving in for the queue.
  let change = parseInt(event.target.dataset.amount);
  let page = this.query.page + change;

  return new Promise(resolve => { // eslint-disable-line no-undef
    Vue.set(this.query, 'page', page);
    Vue.set(this.posts[this.query.page], 'show', true);

    this.queue([0, change]);
    resolve();
  });
};

WpArchiveVue.wp = function() {
  return this.queue();
};

WpArchiveVue.queue = function(queries = [0, 1]) {
  // Set a benchmark query to compare the upcomming query to.
  let Obj1 = Object.assign({}, this.query); // create copy of object.
  delete Obj1.page; // delete the page attribute because it will be different.
  Object.freeze(Obj1); // prevent changes to our comparison.

  // The function is async because we want to wait until each promise
  // is query is finished before we run the next. This is because we
  // don't want to bother sending a request if there are no previous or
  // next pages. The way we find out if there are previous or next pages
  // relative to the current page query is through the headers of the
  // response provided by the WP REST API.
  (async () => {
    for (let i = 0; i < queries.length; i++) {
      let query = Object.assign({}, this.query);
      // eslint-disable-next-line no-undef
      let promise = new Promise(resolve => resolve());
      let pages = this.headers.pages;
      let page = this.query.page;
      let current = false;
      let next = false;
      let previous = false;

      // Build the query and set its page number.
      Object.defineProperty(query, 'page', {
        value: page + queries[i],
        enumerable: true
      });

      // There will never be a page 0 or below, so skip this query.
      if (query.page <= 0) continue;

      // Check to see if we have the page that we are going to queued
      // and the query structure of that page matches the current query
      // structure (other than the page, which will obviously be different).
      // This will help us determine if we need to make a new request.
      let havePage = (this.posts[query.page]) ? true : false;
      let pageQueryMatches = false;

      if (havePage) {
        let Obj2 = Object.assign({}, this.posts[query.page].query);
        delete Obj2.page;
        pageQueryMatches = Object.isEqual(Obj1, Obj2);
      }

      if (havePage && pageQueryMatches) continue;

      // If this is the current page we want the query to go through.
      current = (query.page === page);

      // If there is a next or previous page, we'll prefetch them.
      // We'll know there's a next or previous page based on the
      // headers sent by the current page query.
      next = (page < pages && query.page > page);
      previous = (page > 1 && query.page < page);

      if (current || next || previous)
        await promise.then(() => {
          return this.wpQuery(query);
        })
        .then(this.response)
        .then(data => {
          let headers = Object.assign({}, this.headers);
          this.process(data, query, headers);
        }).catch(this.error);
    }
  })();
};

WpArchiveVue.wpQuery = function(query) {
  Vue.set(this, 'abort', (new AbortController)); // eslint-disable-line no-undef
  let signal = this.abort.signal;
  let url = `${this.lang.path}${this.endpoints.programs}`;

  // Build the url query.
  url = `${url}?` + Object.keys(query)
    .map(k => {
      if (Array.isArray(query[k]))
        return query[k].map(a => `${k}[]=${a}`).join('&');
      return `${k}=${query[k]}`;
    }).join('&');

  // Set posts and store a copy of the query for reference.
  Vue.set(this.posts, query.page, {
    posts: [],
    query: Object.freeze(query),
    show: (this.query.page >= query.page)
  });

  return fetch(url, {signal: signal});
};

WpArchiveVue.response = function(response) {
  let headers = {
    total: 'X-WP-Total',
    pages: 'X-WP-TotalPages',
    link: 'Link'
  };

  if (response.ok) {
    let keys = Object.keys(headers);

    for (let i = 0; i < keys.length; i++) {
      let header = response.headers.get(headers[keys[i]]);
      let value = (isNaN(header)) ? header : (parseInt(header) || 0);
      headers[keys[i]] = value;
    }

    Vue.set(this, 'headers', headers);
  }

  return response.json();
};

WpArchiveVue.process = function(data, query, headers) {
  // If there are posts for this query, map them to the template.
  let posts = (Array.isArray(data)) ?
    data.map(this.maps().programs) : false;

  // Set posts and store a copy of the query for reference.
  Vue.set(this.posts[query.page], 'posts', posts);
  Vue.set(this.posts[query.page], 'headers', Object.freeze(headers));

  // If there are no posts, pass along to the error handler.
  if (!Array.isArray(data))
    this.error({error: data, query: query});

  Vue.set(this, 'init', true);
};

WpArchiveVue.error = function(response) {
  // console.dir(response);
};

export default WpArchiveVue;
